#!/bin/bash
kill $(ps aux | grep '[a]sterisk-web-voicemail-server.php' | awk 'NR==1{print $2}')
sleep 2
/usr/bin/php /usr/src/asterisk-web-voicemail/asterisk-web-voicemail-server.php
