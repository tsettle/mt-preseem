# MikroTik API to Preseem

Don't do it.  Just don't.  I'm neither proud or happy with this, but it's getting us going, so whatever...
I'm pulling information from 3 places

 - /ip/dhcp-server/leases
 - /ip/arp
 - /ip/firewall/address-list

## ARP
Again, not really necessary, but I'm using it to build an associative array so I can get the mac-address for the IP addresses in the address-list.

## Address-List

First, I'm looking for comments with a specific format.  These will get added to the devices list.
Then, I'm looking for customer addresses and adding them to the clients list.

I'm pulling all static entries from the address list.  If the entry is for a user, I add stuff to the Clients.csv file.  Otherwise, I'm looking for a comment with a specific format 

## DHCP Leases
If there's a comment, I'm using that, otherwise, I'm using the host-name.  If neither, then I just do a 'NO INFO' and leave it alone (for now).  The address-lists item is broken down in a pair of arrays for the download and upload speeds.  In the end, I end up with the following fields in my CSV:
 - address
 - mac-address
 - address-lists
 - download speed
 - upload speed
 - server
 - comment/name/whatever

# DO NOT USE THIS!
This is a quick and dirty piece of crap, just don't do it.  If you're gonna do Preseem, integrate it with your billing system.  Ours is a PITA and will take some time to get done.  Hopefully, sooner rather than later.


