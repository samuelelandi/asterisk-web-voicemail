# asterisk-web-voicemail
Web interface for the  management of Asterisk Voice Mail

This package allows the asterisk user to login from a web interface, view
the voicemail received ordered by date, listen to them, delete and move in
different folders.

## Requirements:

PHP 7.x
you can install it on modern Linux Debian/Ubuntu by:

```sh
apt-get install php
```

## Installation:

Change the parameters on top of the file: 

asterisk-web-voicemail-server.php


```php
// **** PARAMETERS TO CUSTOMIZE FOR YOUR ASTERISK INSTALLATION *******************
$MAILBOXPATH ="/var/spool/asterisk/voicemail/default/";
$PASSWORDFILE  ="/etc/asterisk/asterisk-web-mailbox.pwd";
//**** very IMPORTANT - CHANGE THIS SECRET SEED 
$SECRETSEED = "5ac3162036c96541c8c2336c2689ee572f71ebdad2a67c98ab82ee1725436a56"; 
//********************END PARAMETERS *********************************************
```


##Running

Start the socket server as daemon:

```sh
php asterisk-web-voicemail-server.php >/dev/null &
```sh

or in foreground for debugging:

```sh
php asterisk-web-voicemail-server.php >/dev/null &
```



