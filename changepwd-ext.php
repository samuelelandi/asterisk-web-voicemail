<?php
// UTILITY TO CHANGE PASSWORD OF AN EXTENSION from asterisk-web-mailbox
$PASSWORDFILE  ="/etc/asterisk/asterisk-web-mailbox.pwd";

//check parameters
if($argc<2){
    echo "This program allows to CHANGE PASSWORD of an extension fom asterisk-web-mailbox\n";
    echo "You should pass on the command line the following parameters:\n";
    echo "- extension number \n\n";
    echo "- password (make something strong!\n\n";
    echo "for example: php changepwd-ext.php 101 zxasqw1267\n";
    echo "to use special char like :.& you should embed the password between '', for example:\n";
    echo "php changepwd-ext.php 101 'zxas&qw1267'\n";
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
            // generate hash
            $h=password_hash($argv[2],PASSWORD_ARGON2ID);
            $p=$argv[1]."#".$h."\n";
            $nf[]=$p;
            $fd=true;
            continue;
        }
        $nf[]=$u;
    }
}
if($fd){
    // update password file
    file_put_contents($PASSWORDFILE,$nf);
    echo "The password for extension: ".$argv[1]." has been CHANGED on ".$PASSWORDFILE."\n";
}
else{
    echo "extension: ".$argv[1]." not found\n";
}

exit(0);
?>