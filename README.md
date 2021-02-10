# asterisk-web-voicemail
Web interface for the  management of Asterisk Voice Mail.

This package is an add-on to [Asterisk Sip Server ver.18.x](https://www.asterisk.org).

It allows the SIP user to login to a web interface, view
the voice mails received ordered by date, listen the recording, delete and move in
different folders (New and Archived).

## Requirements:

PHP 7.x
apache 2.x

you can install it on modern Linux Debian/Ubuntu by:

```sh
apt-get -y install apache2 php
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
cp www/* /var/www/html/

Configure the extension and their password using:

```sh
php add-ext.php xxxextensionxxxxx yyyypaswordyyyyy
```

where xxxextensionxxxxx is the extension number, for example 101 and yyyypaswordyyyyy is the password for web voice mail login (different
from the sip account password).

you can change a pwd if you need:

```sh
php changepwd-ext.php xxxextensionxxxxx yyyypaswordyyyyy
```

you can remove an extension with:

```sh
php delete-ext.php xxxextensionxxxxx yyyypaswordyyyyy
```

## Running

Start the socket server as daemon:

```sh
php asterisk-web-voicemail-server.php >/dev/null &
```

or in foreground for debugging:

```sh
php asterisk-web-voicemail-server.php 
```

connect to the web server with your browser to login.


## Security:

The users passwords are safely stored as a strong hash with [Argon2](https://en.wikipedia.org/wiki/Argon2)

The session token is derived from a 3 layers encryption (AES-256,CHACHA20 and CAMELLIA-256)
and the $SECRETSEED above. 

ATTENTION: It's very important to change the SECRETSEED.
