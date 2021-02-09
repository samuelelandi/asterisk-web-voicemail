#!/bin/bash
kill $(ps aux | grep '[a]sterisk-web-voicemail-server.php' | awk 'NR==1{print $2}')
