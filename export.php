#!/bin/php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

error_reporting(E_ALL);

use \RouterOS\Client;
use \RouterOS\Query;

$types = array(
    "AP" => "Access Point",
    "BH" => "Backhaul",
    "SW" => "Switch",
    "ENB" => "Baicells",
    "EP" => "Edgepoint",
    "PTP" => "Point to Point"
);  

$up = array(
    "50x50" => 1024*50,
    "50x10" => 1024*10,
    "25x5" => 1024*5,
    "10x10" => 1024*10,
    "10x2" => 1024*2,
    "Gold" => 1024,
    "Silver" => 512,
    "Bronze" => 384,
    "Bridge" => 1024*2,
    "10x10" => 1024*10
);

$down = array(
    "50x50" => 1024*50,
    "50x10" => 1024*50,
    "25x5" => 1024*25,
    "10x10" => 1024*10,
    "10x2" => 1024*10,
    "Gold" => 1024*6,
    "Silver" => 1024*3,
    "Bronze" => 1536,
    "Bridge" => 1024*25,
    "10x10" => 1024*10
);

// Establish SSH connection to preseem

$connection = ssh2_connect('172.23.6.20', 22, array('hostkey'=>'ssh-rsa'));

if (ssh2_auth_pubkey_file($connection, $REMOTE_USER,
        '/home/preseem/.ssh/id_rsa.pub',
        '/home/preseem/.ssh/id_rsa'))
{
//        echo "Public Key Authentication Successful\n";
        $sftp = ssh2_sftp($connection);

        $fileDevices = fopen('ssh2.sftp://' . intval($sftp) . "$REMOTE_PATH/Devices", 'w');
        $fileClients = fopen('ssh2.sftp://' . intval($sftp) . "$REMOTE_PATH/Clients", 'w');
  
        fputcsv($fileDevices,array(
            "address","mac-address","site","type","name"
        ));
        
        fputcsv($fileClients,array(
            "address","mac-address","package","download","upload","network","customer"
        ));
        
} else {
        die('Something went wrong with the SSH connection');
}


// Open files for writing

//$fileDevices = fopen("Devices.csv","w");
//$fileClients = fopen("Clients.csv","w");


// Initiate client with config object

$client = new Client([
    'timeout' => 1,
    'host'    => $ROUTER_IP,
    'user'    => $ROUTER_USER,
    'pass'    => $ROUTER_PASS
]);

// Get the ARP table

$query = 
  (new Query('/ip/arp/print'))
    ->where('complete', 'yes');

$response = $client->query($query)->readAsIterator();

for ($response->rewind(); $response->valid(); $response->next()) {
    try {
        $value = $response->current();
        $mac[$value['address']] = $value['mac-address'];
    } catch (Exception $exception) {
        continue;
    }
}

// Get the address list

$query =
  (new Query('/ip/firewall/address-list/print'))
    ->where('dynamic','no');

$response = $client->query($query)->readAsIterator();

for ($response->rewind(); $response->valid(); $response->next()) {
    try {
        $value = $response->current();
        if(array_key_exists('comment',$value)) {
          list($group,$type) = array_pad(explode('.',$value['comment'], 3),3,NULL);
          if(array_key_exists($type,$types))
          {
//              $device[$value['address']] = $value['comment'];
              fputcsv($fileDevices,array(
                $value['address'],
                array_key_exists($value['address'],$mac) ? $mac[$value['address']] : '',
                $group,
                $type,
                preg_replace('/(\s*\(.*\))/', '', $value['comment'])
            ));
              $skip[$value['address']] = true;
          }
          
          if(array_key_exists($value['list'],$down) && $value['list'] != "Bridge") {
              fputcsv($fileClients,array(
                  $value['address'],
                  array_key_exists($value['address'],$mac) ? $mac[$value['address']] : '',
                  $value["list"],
                  $down[$value["list"]],
                  $up[$value["list"]],
                  '',
                  preg_replace('/.*\((.*)\).*/', '$1', $value['comment'])
                ));
          }
        }
    } catch (Exception $exception) {
        print_r($value);
        continue;
    }
  }
  
// Get the leases table

$query = 
    (new Query('/ip/dhcp-server/lease/print'))
        ->where('status', 'bound')
        ->where('address-lists','-','');


$leases = $client->query($query)->readAsIterator();

for ($leases->rewind(); $leases->valid(); $leases->next())
{
    try {
        $value = $leases->current();
        if(array_key_exists($value['active-address'],$skip)) {
          print_r ($value);
          continue;
        }

        if(array_key_exists('comment',$value)) {
            $comment = preg_replace('/(\s*\(.*\))/', '', $value['comment']);
        } elseif (array_key_exists('host-name',$value)) {
            $comment = $value["host-name"];
        } else {
            $comment = "NO INFO";
        }

        if(array_key_exists($value['address-lists'],$down))
        {
            fputcsv($fileClients,array(
                $value["active-address"],
                $value["mac-address"],
                $value["address-lists"],
                $down[$value["address-lists"]],
                $up[$value["address-lists"]],
                $value["server"],
                $comment
            ));
        }
    } catch (Exception $exception) {
        continue;
    }
}
  fclose($fileClients);
  fclose($fileDevices);

  ssh2_exec($connection, "/usr/bin/mv $REMOTE_PATH/Devices{,.csv}");
  ssh2_exec($connection, "/usr/bin/mv $REMOTE_PATH/Clients{,.csv}");


