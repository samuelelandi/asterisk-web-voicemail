<?php
    $error="";
    session_start();
    // if the token is not set returns to login
    if(!isset($_SESSION['token'])){
        header("Location: index.php");
        exit(0);
    }
    // set folder description
    $folder=$_SESSION['folder'];
    if($folder=="INBOX")
        $folderdesc="New";
    if($folder=="Old")
        $folderdesc="Archived";
    if($folder=="Urgent")
        $folderdesc="Urgent";
    // process archive request
    if(isset($_REQUEST['archive'])){
        $socket=connect_to_server();
        $m="/archive?folder=".$folder."&filename=".urlencode($_REQUEST['archive'])."&token=".urlencode($_SESSION['token']);
        socket_write($socket, $m, strlen($m));
        socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0));
        $a=socket_read($socket,2048);    
        socket_close($socket);
        $j=json_decode($a);
        if($j->answer=="KO"){
            $error=$j->message;
        }
        else{
            header("Location: dashboard.php");
            exit(0);
        }
    }   
    // process unarchive request
    if(isset($_REQUEST['unarchive'])){
        $socket=connect_to_server();
        $m="/unarchive?folder=".$folder."&filename=".urlencode($_REQUEST['unarchive'])."&token=".urlencode($_SESSION['token']);
        socket_write($socket, $m, strlen($m));
        socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0));
        $a=socket_read($socket,2048);    
        socket_close($socket);
        $j=json_decode($a);
        if($j->answer=="KO"){
            $error=$j->message;
        }
        else{
            header("Location: dashboard.php");
            exit(0);
        }
    }   
    // process delete request
    if(isset($_REQUEST['delete'])){
        $socket=connect_to_server();
        $m="/rmfile?folder=".$_REQUEST['folder']."&filename=".urlencode($_REQUEST['delete'])."&token=".urlencode($_SESSION['token']);
        socket_write($socket, $m, strlen($m));
        socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0));
        $a=socket_read($socket,2048);    
        socket_close($socket);
        $j=json_decode($a);
        if($j->answer=="KO"){
            $error=$j->message;
        }
        else{
            header("Location: dashboard.php");
            exit(0);
        }
    }   
    // process delete all request
    if(isset($_REQUEST['deleteall'])){
        $socket=connect_to_server();
        $m="/rmfileall?folder=".$_REQUEST['folder']."&token=".urlencode($_SESSION['token']);
        socket_write($socket, $m, strlen($m));
        socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0));
        $a=socket_read($socket,2048);    
        socket_close($socket);
        $j=json_decode($a);
        if($j->answer=="KO"){
            $error=$j->message;
        }
        else{
            header("Location: dashboard.php");
            exit(0);
        }
    }   
    // swith to archived folder (Old)
    if(isset($_REQUEST['archived'])){
        $_SESSION['folder']="Old";
        $folder="Old";
        $folderdesc="Archived";
    }
    // swith to new folder (INBOX)
    if(isset($_REQUEST['new'])){
        $_SESSION['folder']="INBOX";
        $folder="INBOX";
        $folderdesc="New";
    }
    // Logout)
    if(isset($_REQUEST['logout'])){
        $_SESSION['folder']="";
        $_SESSION['token=']="";
        header("Location: index.php");
        exit(0);
    }
         
?>
<html>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
<title>Voice Mail</title>
<body>
    
    <div class="container-md">
        <table><tr><td>
        <img src="logo.png" width="128" height="128"> 
        </td><td>
        <h2> Web Voice Mail - <?php echo $_SESSION['extension'];echo " [".$folderdesc."]"; ?></h2>
        <?php 
            if(strlen($error)>0){
                    echo '<div class="alert alert-danger" role="alert">';
                    echo $error;
                    echo '</div>';
            }
            if($folder=="INBOX"){
                echo '<div><form method="post"><button class="btn btn-primary" type="submit" name="archived" value="archived">Switch to Archived</button> ';
            }
            else {
                echo '<div><form method="post"><button class="btn btn-primary" type="submit" name="new" value="new">Switch to New Messages</button> ';            
            }
            echo '<button class="btn btn-secondary" type="submit" name="logout" value="new">Logout</button> ';            
            echo '<button class="btn btn-danger" name="deleteall" type="button" value="deleteall" onClick="';
            echo "if(confirm('Do you confirm the irreversible cancellation of ALL messages?') == true){";
            echo "window.location='dashboard.php?folder=".urlencode($_SESSION['folder']);
            echo "&deleteall=confirmed';}";
            echo'"';
            echo '>Delete All</button></form></div> ';
            
        ?>
        </td>
        <hr>
        <?php 
            if(strlen($error)>0){
                    echo '<div class="alert alert-danger" role="alert">';
                    echo $error;
                    echo '</div>';
            }
        ?>
        <div class="row">
        <table class="table table-striped">
        <tr><th>Caller Id</th><th>Duration</th><th>Date/Time</th><th>Action</th></tr>
        <?php
            //get messages from current FOLDER
            $socket=connect_to_server();
            $m="/list?folder=".$folder."&token=".urlencode($_SESSION['token']);
            socket_write($socket, $m, strlen($m));
            socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0));
            $a=socket_read($socket,32768);
            socket_close($socket);
            $j=json_decode($a);
            if($j->answer=="KO"){
                $error=$j->message;
                header("Location: index.php");
                exit(0);
            }
            if($j->answer=="OK")
            {
                foreach($j->files as $f){
                    echo "<tr><td>";
                    echo $f->callerid;
                    echo "</td><td>";
                    echo $f->duration;
                    echo "</td><td>";                    
                    echo "<script>date = new Date(".($f->origtime*1000).");document.write(date);</script>";
                    echo "</td><td>";
                    $ad=get_audio_data($folder,$f->filename,$_SESSION['token']);
                    echo '<p><audio controls> <source src="'.$ad.'" type="audio/wav"></audio></p>';
                    if($folder=="INBOX")
                        echo '<form method="post"><button class="btn btn-secondary" type="submit" name="archive" value="'.$f->filename.'">Archive</button> ';
                    else
                        echo '<form method="post"><button class="btn btn-secondary" type="submit" name="unarchive" value="'.$f->filename.'">Un-archive</button> ';                    
                        
                    echo '<button class="btn btn-danger" name="delete" type="button" value="'.$f->filename.'" ';
                    echo 'onClick="';
                    echo "if(confirm('Do you confirm the irreversible cancellation of this message?') == true){";
                    echo "window.location='dashboard.php?folder=".urlencode($_SESSION['folder']);
                    echo "&delete=".urlencode($f->filename);
                    echo "';}";
                    echo'"';
                    echo '>Delete</button></form> ';
                    echo "</td></tr>";
                }
            }

        ?>
        </table>
        
        </div>

    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>
</body>
</html>
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
// function to get audio data from the asterisk-web-voicemail-server
function get_audio_data($folder,$filename,$token){
    $m="/getfile?folder=".$folder."&filename=".urlencode($filename)."&token=".urlencode($token);
    $socket=connect_to_server();
    socket_write($socket, $m, strlen($m));
    socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>15, "usec"=>0));
    $a=socket_read($socket,10000000);
    return($a);    
}
?>
