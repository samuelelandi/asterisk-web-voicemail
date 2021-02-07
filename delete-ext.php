<?php
// UTILITY TO DELETE AN EXTENSION from asterisk-web-mailbox
$PASSWORDFILE  ="/etc/asterisk/asterisk-web-mailbox.pwd";

//check parameters
if($argc<2){
    echo "This program allows to DELETE an extension from asterisk-web-mailbox\n";
    echo "You should pass on the command line the following parameters:\n";
    echo "- extension number \n\n";
    echo "For example: php deleteuser.php 101\n";
    exit(0);
}
$fd=false;
// check if the extension is already present
$nf=array();
if(file_exists($PASSWORDFILE)){
    $uv=file($PASSWORDFILE);
    foreach($uv as $u){
        $ue=explode("#",$u);
        if($ue[0]==$argv[1]){
            $fd=true;
            continue;
        }
        $nf[]=$u;
    }
}
if($fd){
    // update password file
    file_put_contents($PASSWORDFILE,$nf);
    echo "extension: ".$argv[1]." has been DELETED from ".$PASSWORDFILE."\n";
}
else{
    echo "extension: ".$argv[1]." not found\n";
}

exit(0);
?>