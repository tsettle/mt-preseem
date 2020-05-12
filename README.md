# MikroTik API to Preseem

Don't do it.  Just don't.  I'm neither proud or happy with this, but it's getting us going, so whatever...
I'm pulling information from 3 places
 - /ip/dhcp-server/leases
 - /ip/arp
 - /ip/firewall/address-list

## ARP
Not really necessary, but I'm using it to build an associative array so I can get the mac-address for the IP addresses in the address-list.

## Address-List

First, I'm looking for comments with a specific format.  These will get added to the devices list.
Then, I'm looking for customer addresses and adding them to the clients list.

I started naming our stuff in a certain way that makes it easy to parse out various information.  For example, a cell might look like this:
| list | address | comment |
|--|--|--|
| equipment | 172.28.16.20 | SITE.BH.16.20 (comments about the site)|
| equipment | 172.28.16.21 | SITE.SW.16.21 (Netonix WS-12-250-DC)|
| equipment | 172.28.16.22 | SITE.AP.16.22 |
| equipment | 172.28.16.23 | SITE.AP.16.23 |

From that, generating a list of APs for Preseem to monitor is pretty easy.
I'm also looking for entries for users who do not have a DHCP lease.  Fairly simple process.

## DHCP Leases
If there's a comment, I'm using that, otherwise, I'm using the host-name.  If neither, then I just do a 'NO INFO' and leave it alone (for now).  The address-lists item is broken down in a pair of arrays for the download and upload speeds.  In the end, I end up with the following fields in my CSV:
 - address
 - mac-address
 - address-lists
 - download speed
 - upload speed
 - server
 - comment/name/whatever

# DO NOT USE THIS SCRIPT!
This is a quick and dirty piece of crap, just don't do it.  If you're gonna do Preseem, integrate it with your billing system.  Ours is a PITA and will take some time to get done.  Hopefully, sooner rather than later.

## Then again...

I am considering building a UI to manage this information and packing the comments with serialized information (or maybe JSON?).  ROS allows for some stupid big comments, and while it's not easy to do from Winbox, it will handle it just fine, even properly escaping special characters in an export.

For that, I'm considering a pure javascript app, perhaps using the Quasar Framework, which is really quite beautiful.  Just need to find a javascript implementation of the (secure) MT API.
