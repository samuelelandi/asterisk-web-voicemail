<?php
// function to connect to the  asterisk-web-voicemail-server (127.0.0.1:4444)
function connect_to_server(){
    $address="127.0.0.1";
    $port=4444;
    //connection to asterisk-web-voicemail-server on 
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
        exit(1);
    }
    $result = socket_connect($socket, $address, $port);
    if ($result === false) {
        echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
        exit(1);
    }
    return($socket);    
}
?>