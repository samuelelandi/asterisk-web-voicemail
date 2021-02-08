<?php
    session_start();
    if(isset($_REQUEST['extension']) && isset($_REQUEST['password'])) 
    {
        $socket=connect_to_server();
        $m="/login?extension=".urlencode($_REQUEST['extension'])."&password=".urlencode($_REQUEST['password']);
        socket_write($socket, $m, strlen($m));
        socket_set_option($socket,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>5, "usec"=>0));
        $a=socket_read($socket,2048);
        $j=json_decode($a);
        if($j->answer=="KO"){
            $error=$j->message;
            socket_close($socket);
        }
        if($j->answer=="OK")
        {
            $_SESSION["token"]=$j->token;            
            $_SESSION["extension"]=$_REQUEST['extension'];            
            $_SESSION["folder"]="INBOX";            
            header("Location: dashboard.php");
            socket_close($socket);
            exit(0);
        }
  }
?>
<html>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
<body>
    <div class="container-md">
    <hr>
    <div class="row">
        <div class="col-4">
        <img src="logo.png" width="256" height="256">
        </div>
        <div class="col-4">
            <h2>Web Voice Mail</h2>
            <form method="post">
            <div class="input-group mb-3">
              <span class="input-group-text" id="basic-addon1">Extension:</span>
              <input type="text" class="form-control" placeholder="Number" aria-label="extension" name="extension">
            </div>
            <div class="input-group mb-3">
              <span class="input-group-text" id="basic-addon1">Password:</span>
              <input type="password" class="form-control" placeholder="Password" aria-label="password" name="password">
            </div>
            <input type="submit" value="Login" name="login" class="btn btn-primary mb-3">
            <?php 
                if(strlen($error)>0){
                    echo '<div class="alert alert-danger" role="alert">';
                    echo $error;
                    echo '</div>';
                }
            ?>
            </form>
        </div>
    </div>
    <hr>
    <?php 
        if(strlen($error)>0){
            echo '<div class="alert alert-danger" role="alert">';
            echo $error;
            echo '</div>';
        }
    ?>
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
?>