<?php
// UTILITY TO ADD A NEW extension to asterisk-web-mailbox
$PASSWORDFILE  ="/etc/asterisk/asterisk-web-mailbox.pwd";

//check parameters
if($argc<3){
    echo "This program allows to add a new extensio to asterisk-web-mailbox\n";
    echo "You should pass on the command line the following parameters:\n";
    echo "- extension number (for example 101)\n";
    echo "- password (make something strong!\n\n";
    echo "for example: php add-ext.php 101 zxasqw1267\n";
    echo "to use special char like :.& you should embed the password between '', for example:\n";
    echo "php add-ext.php 101 'zxas&qw1267'\n";
    exit(0);
}
// check if the user is already present
if(file_exists($PASSWORDFILE)){
    $uv=file($PASSWORDFILE);
    foreach($uv as $u){
        $ue=explode("#",$u);
        if($ue[0]==$argv[1]){
            echo "extensions ".$argv[1]." is already present, you can change the password using changepwd-ext.php\n";
            exit(0);
        }
    }
}
// generate hash
$h=password_hash($argv[2],PASSWORD_ARGON2ID);
$p=$argv[1]."#".$h."\n";
echo $p;
// update password file
file_put_contents($PASSWORDFILE,$p,FILE_APPEND);
echo "extension: ".$argv[1]." has been added to ".$PASSWORDFILE."\n";
exit(0);
?>