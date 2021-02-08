<?php
//****************************************************************************
//**  API SERVER FOR ASTERISK VOICEMAIL MANAGEMENT (IP: 127.0.0.1 PORT 4444)
//****************************************************************************

// **** PARAMETERS TO CUSTOMIZE FOR YOUR ASTERISK INSTALLATION *******************
$MAILBOXPATH ="/var/spool/asterisk/voicemail/dinara/";
$PASSWORDFILE  ="/etc/asterisk/asterisk-web-mailbox.pwd";
//**** very IMPORTANT - CHANGE THIS SECRET SEED 
$SECRETSEED = "5ac3162036c96541c8c2336c2689ee572f71ebdad2a67c98ab82ee1725436a56"; 
//********************END PARAMETERS *********************************************

// Check dependencies
if( ! extension_loaded('sockets' ) ) {
	echo "This program requires sockets extension for php\n";
	exit(-1);
}
if( ! extension_loaded('pcntl' ) ) {
	echo "This program  requires PCNTL extension for php\n";
	exit(-1);
}
// Socket opening on ports and ip address configure in config.php
$server = new SocketServer();
$server->init();
$server->setConnectionHandler( 'onConnect' );
$server->listen();

//Main loop after the connectio
function onConnect( $client ) {
	// fork the process to allow multiple connections 
	$pid = pcntl_fork();
	if ($pid == -1) {
		 die('could not fork');
	} else if ($pid) {
		// parent process
		return;
	}
	$read = '';
	printf( "[%s] Connected at port %d\n", $client->getAddress(), $client->getPort() );
	
	while( true ) {
		// read the GET request
		$read = $client->read();
		if( $read == '' )  break;
		if( $read === null ) {
			printf( "[%s] Disconnected 001\n", $client->getAddress() );
			break;
		}
		else {
			printf( "[%s] received: %s]\n", $client->getAddress(), $read );
		}
		// process the "echo" request
		if(strstr($read,"/echo")!=NULL){
			$answer=$read;
			echo "Echoing back: ".$answer."\n";
			$client->send($answer);		
			sleep(1);
			break;	
		}
		//process the "login" request
		if(strstr($read,"/login?")!=NULL){
			echo "Processing login request...\n";
			$v=explode("?",$read);
			if(!isset($v[1])){
				$answer='{"answer":"KO","message":"Missing data for the query"}';
				echo $answer;
				$client->send($answer);
	                        sleep(1);
        	                break;  
			}
			$f=array();
			parse_str($v[1], $f);
			if(strlen($f["extension"])==0 || strlen($f["password"])==0){
				$answer='{"answer":"KO","message":"Missing extension or password for logging in"}';
				echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;
			}
			//remove \n if present in the password field
			$pwd=str_replace("\n","",$f["password"]);
			$pwd=str_replace("\r","",$pwd);
			//check password validity
			$fv=file($GLOBALS["PASSWORDFILE"]);
			$fnd=false;
			foreach($fv as $u){
				$ue=explode("#",$u);
				if($ue[0]==$f['extension']){
					$fnd=true;
					$pwdc=substr($ue[1],0,strlen($ue[1])-1);
					if(password_verify($pwd,$pwdc)){
						// generate token
						$t=time()+3600; // validity for 1 hour
						$cleartext=$f["extension"]."#".$t;
						$enc=encrypt($cleartext,$GLOBALS["SECRETSEED"]);
						$answer='{"answer":"OK","message":"Login accepted","token":"'.$enc.'"}';
						echo $answer."\n";
                                                $client->send($answer);
                                                sleep(1);
                                                break;
					}
					else{
						//wrong password answer
						$answer='{"answer":"KO","message":"Wrong extension or password"}';
						echo $answer."\n";
		                                $client->send($answer);
                		                sleep(1);
						break;
					}
				}
			}
			if(!$fnd){
				$answer='{"answer":"KO","message":"Extension not found"}';
                                echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
			}
			break;
			
		}
		//process the "list" request
                if(strstr($read,"/list?")!=NULL){
                        $v=explode("?",$read);
                        if(!isset($v[1])){
                                $answer='{"answer":"KO","message":"Missing data for the query"}';
                                echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;  
                        }
                        $f=array();
                        parse_str($v[1], $f);
                        if(strlen($f["folder"])==0 || strlen($f["token"])==0){
                                $answer='{"answer":"KO","message":"Missing folder or token"}';
                                echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        //remove \r\n if present in the token field
                        $token=str_replace("\n","",$f["token"]);
                        $token=str_replace("\r","",$token);
                        // decrypt token and get the username
                        $cleartext=decrypt($token,$GLOBALS["SECRETSEED"]);
                        echo "cleartext: ".$cleartext."\n";
                        $v=explode("#",$cleartext);
                        if(!isset($v[0])){
	                        $answer='{"answer":"KO","message":"Token not valid"}';
	                        echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        if(time()>$v[1]){
                        	$answer='{"answer":"KO","message":"Token is expired"}';
                        	echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        $dp=$GLOBALS['MAILBOXPATH'].$v[0]."/".$f["folder"];
                        echo "path: ".$dp."\n";
                        $d=dir($dp);
                        //empty directory
                        if($d==NULL){
				$answer='{"answer":"OK","message":"list of voicemail for '.$v[0].'","voicemail":[]}';                        
				echo $answer."\n";
				$client->send($answer);
	                        sleep(1);
        	                break;
                        }
                        //directory is present
                        $fn=array();
                        while (($filename = $d->read()) !== false){ 
				if($filename=="." || $filename=="..")
					continue;
				$fn[]=$filename;
			}
			var_dump($fn);
			$vt="[";
			foreach($fn as $n){
				if(strstr($n,".txt")){
					$cf=file($dp."/".$n);
					$callerid="";
					$duration=0;
					$time=0;
					foreach($cf as $c) {
						if(strstr($c,"callerid=")){
							$b=explode("=",$c);
							$clid=explode('"',$b[1]);
							$callerid=$clid[1];
							var_dump($clid);
                                                        
						}			
						if(strstr($c,"origtime=")){
                                                        $b=explode("=",$c);
                                                        $origtime=$b[1];
                                                        $origtime=str_replace("\n","",$b[1]);
                                                        $origtime=str_replace("\r","",$origtime);
                                                }  		
						if(strstr($c,"duration=")){
                                                        $b=explode("=",$c);
                                                        $duration=str_replace("\n","",$b[1]);
                                                        $duration=str_replace("\r","",$duration);
                                                }  		                                                
					}
					$filename=str_replace(".txt",".wav",$n);
					if(strlen($vt)>1) $vt.=",";
					$vt.='{"filename":"'.$filename.'","callerid":"'.$callerid.'","origtime":"'.$origtime.'","duration":"'.$duration.'"}';
				}
			}
			$vt.="]";
                        // create json with list of voicemails
                        $answer='{"answer":"OK","message":"list of voicemail for '.$v[0].'","files":'.$vt.'}';
                        echo $answer."\n";
                        $client->send($answer);
                        sleep(1);
                        break;
		}
		//process the "getfile" request
                if(strstr($read,"/getfile?")!=NULL){
                        $v=explode("?",$read);
                        if(!isset($v[1])){
                                $answer='{"answer":"KO","message":"Missing data for the query"}';
                                echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;  
                        }
                        $f=array();
                        parse_str($v[1], $f);
                        var_dump($f);
                        if(strlen($f["folder"])==0 || strlen($f["filename"])==0 || strlen($f["token"])==0){
                                $answer='{"answer":"KO","message":"Missing folder or filename"}';
                                echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        if(strstr($f["filename"],".wav")==NULL){
				$answer='{"answer":"KO","message":"Filename is wrong, only .wav files are allowed"}';
				echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;                        	
                        }
                        //remove \r\n if present in the filename field
                        $filename=str_replace("\n","",$f["filename"]);
                        $filename=str_replace("\r","",$filename);
                        //remove \r\n if present in the token field
                        $token=str_replace("\n","",$f["token"]);
                        $token=str_replace("\r","",$token);
                        // decrypt token and get the username
                        $cleartext=decrypt($token,$GLOBALS["SECRETSEED"]);
                        $v=explode("#",$cleartext);
                        if(!isset($v[0])){
	                        $answer='{"answer":"KO","message":"Token not valid"}';
	                        echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        if(time()>$v[1]){
                        	$answer='{"answer":"KO","message":"Token is expired"}';
                        	echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        $fp=$GLOBALS['MAILBOXPATH'].$v[0]."/".$f["folder"]."/".$filename;
                        if(file_exists($fp)){
                        	$fh = fopen($fp, "rb");
                        	$content = fread($fh, filesize($fp));
                        	$encoded_data = base64_encode($content);
				$mime_type ='audio/wav';
				$binary_data = 'data:' . $mime_type . ';base64,' . $encoded_data ;
				$client->send($binary_data);
				sleep(1);
	                        break;
                        }
                        $answer='{"answer":"KO","message":"File not found"}';
                        echo $answer."\n";
                        $client->send($answer);
                        sleep(1);
                        break;
		}
		//process the "rmfile" request
                if(strstr($read,"/rmfile?")!=NULL){
                        $v=explode("?",$read);
                        if(!isset($v[1])){
                                $answer='{"answer":"KO","message":"Missing data for the query"}';
                                $client->send($answer);
                                sleep(1);
                                break;  
                        }
                        $f=array();
                        parse_str($v[1], $f);
                        if(strlen($f["folder"])==0 || strlen($f["filename"])==0 || strlen($f["token"])==0){
                                $answer='{"answer":"KO","message":"Missing folder or filename"}';
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        if(strstr($f["filename"],".wav")==NULL){
				$answer='{"answer":"KO","message":"Filenam is wrong, only .wav files are allowed"}';
                                $client->send($answer);
                                sleep(1);
                                break;                        	
                        }
                        //remove \r\n if present in the filename field
                        $filename=str_replace("\n","",$f["filename"]);
                        $filename=str_replace("\r","",$filename);
                        //remove \r\n if present in the token field
                        $token=str_replace("\n","",$f["token"]);
                        $token=str_replace("\r","",$token);
                        // decrypt token and get the username
                        $cleartext=decrypt($token,$GLOBALS["SECRETSEED"]);
                        $v=explode("#",$cleartext);
                        if(!isset($v[0])){
	                        $answer='{"answer":"KO","message":"Token not valid"}';
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        if(time()>$v[1]){
                        	$answer='{"answer":"KO","message":"Token is expired"}';
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        $fp=$GLOBALS['MAILBOXPATH'].$v[0]."/".$f["folder"]."/".$filename;
                        if(file_exists($fp)){
                        	$fpt=str_replace(".wav",".txt",$fp);
                        	$fpw=str_replace(".wav",".WAV",$fp);
                        	$fpg=str_replace(".wav",".gsm",$fp);
                        	unlink($fp);
                        	unlink($fpt);
                        	unlink($fpw);
                        	unlink($fpg);
                        	$answer='{"answer":"OK","message":"file deleted"}';
                        }
                        else
	                        $answer='{"answer":"KO","message":"file not found"}';
                        $client->send($answer);
                        sleep(1);
                        break;
		}
		//process the "archive" request
                if(strstr($read,"/archive?")!=NULL){
                        $v=explode("?",$read);
                        if(!isset($v[1])){
                                $answer='{"answer":"KO","message":"Missing data for the query"}';
                                echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;  
                        }
                        $f=array();
                        parse_str($v[1], $f);
                        if(strlen($f["folder"])==0 || strlen($f["filename"])==0 || strlen($f["token"])==0){
                                $answer='{"answer":"KO","message":"Missing folder or filename"}';
                                echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        if(strstr($f["filename"],".wav")==NULL){
				$answer='{"answer":"KO","message":"Filename is wrong, only .wav files are allowed"}';
				echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;                        	
                        }
                        $folder=$f["folder"];
                        //remove \r\n if present in the filename field
                        $filename=str_replace("\n","",$f["filename"]);
                        $filename=str_replace("\r","",$filename);
                        //remove \r\n if present in the token field
                        $token=str_replace("\n","",$f["token"]);
                        $token=str_replace("\r","",$token);
                        // decrypt token and get the username
                        $cleartext=decrypt($token,$GLOBALS["SECRETSEED"]);
                        $v=explode("#",$cleartext);
                        if(!isset($v[0])){
	                        $answer='{"answer":"KO","message":"Token not valid"}';
	                        echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        if(time()>$v[1]){
                        	$answer='{"answer":"KO","message":"Token is expired"}';
                        	echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        $fp=$GLOBALS['MAILBOXPATH'].$v[0]."/".$f["folder"]."/".$filename;
                        if(file_exists($fp)){
                        	$fpt=str_replace(".wav",".txt",$fp);
                        	$fpw=str_replace(".wav",".WAV",$fp);
                        	$fpg=str_replace(".wav",".gsm",$fp);
                        	$dfp=str_replace("/".$folder."/","/Old/",$fp);
                        	$dfpt=str_replace("/".$folder."/","/Old/",$fpt);
                        	$dfpw=str_replace("/".$folder."/","/Old/",$fpw);
                        	$dfpg=str_replace("/".$folder."/","/Old/",$fpg);
                        	echo "rename: ".$fp." ".$fpd."\n";
                        	rename($fp,$dfp);
                        	echo "rename: ".$fpt." ".$dfpt."\n";
                        	rename($fpt,$dfpt);
                        	echo "rename: ".$fpw." ".$dfpw."\n";
                        	rename($fpw,$dfpw);
                        	echo "rename: ".$fpg." ".$dfpg."\n";
                        	rename($fpg,$dfpg);                        	
                        	$answer='{"answer":"OK","message":"file archived"}';
                        	echo $answer."\n";
                        }
                        else {
	                        $answer='{"answer":"KO","message":"file not found"}';
	                        echo $answer."\n";
	                        $client->send($answer);
	                        sleep(1);
        	                break;
			}
		}
		//process the "unarchive" request
                if(strstr($read,"/unarchive?")!=NULL){
                        $v=explode("?",$read);
                        if(!isset($v[1])){
                                $answer='{"answer":"KO","message":"Missing data for the query"}';
                                echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;  
                        }
                        $f=array();
                        parse_str($v[1], $f);
                        if(strlen($f["folder"])==0 || strlen($f["filename"])==0 || strlen($f["token"])==0){
                                $answer='{"answer":"KO","message":"Missing folder or filename"}';
                                echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        if(strstr($f["filename"],".wav")==NULL){
				$answer='{"answer":"KO","message":"Filename is wrong, only .wav files are allowed"}';
				echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;                        	
                        }
                        $folder=$f["folder"];
                        //remove \r\n if present in the filename field
                        $filename=str_replace("\n","",$f["filename"]);
                        $filename=str_replace("\r","",$filename);
                        //remove \r\n if present in the token field
                        $token=str_replace("\n","",$f["token"]);
                        $token=str_replace("\r","",$token);
                        // decrypt token and get the username
                        $cleartext=decrypt($token,$GLOBALS["SECRETSEED"]);
                        $v=explode("#",$cleartext);
                        if(!isset($v[0])){
	                        $answer='{"answer":"KO","message":"Token not valid"}';
	                        echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        if(time()>$v[1]){
                        	$answer='{"answer":"KO","message":"Token is expired"}';
                        	echo $answer."\n";
                                $client->send($answer);
                                sleep(1);
                                break;
                        }
                        $fp=$GLOBALS['MAILBOXPATH'].$v[0]."/".$f["folder"]."/".$filename;
                        if(file_exists($fp)){
                        	$fpt=str_replace(".wav",".txt",$fp);
                        	$fpw=str_replace(".wav",".WAV",$fp);
                        	$fpg=str_replace(".wav",".gsm",$fp);
                        	$dfp=str_replace("/".$folder."/","/INBOX/",$fp);
                        	$dfpt=str_replace("/".$folder."/","/INBOX/",$fpt);
                        	$dfpw=str_replace("/".$folder."/","/INBOX/",$fpw);
                        	$dfpg=str_replace("/".$folder."/","/INBOX/",$fpg);
                        	echo "rename: ".$fp." ".$fpd."\n";
                        	rename($fp,$dfp);
                        	echo "rename: ".$fpt." ".$dfpt."\n";
                        	rename($fpt,$dfpt);
                        	echo "rename: ".$fpw." ".$dfpw."\n";
                        	rename($fpw,$dfpw);
                        	echo "rename: ".$fpg." ".$dfpg."\n";
                        	rename($fpg,$dfpg);                        	
                        	$answer='{"answer":"OK","message":"file un-archived"}';
                        	echo $answer."\n";
                        }
                        else {
	                        $answer='{"answer":"KO","message":"file not found"}';
	                        echo $answer."\n";
	                        $client->send($answer);
	                        sleep(1);
        	                break;
			}
		}
		

		// end processing GET request
		
	}
	$client->close();
	printf( "[%s] Disconnected \n", $client->getAddress() );
	exit(true);
	
}

// ENCRYPTION FUNCTION
function encrypt($cleartext,$pwd){
	$iv=openssl_random_pseudo_bytes(64,$cs);
	$iv=substr(base64_encode($iv),0,64);
	$s=$iv;
	$dpwd=openssl_pbkdf2($pwd,$iv,64,100,"sha512");
	$ivl=openssl_cipher_iv_length($cipher="AES-256-OFB");
	$ivc=substr($iv,0,$ivl);
	$rc=openssl_encrypt($cleartext,"AES-256-OFB",$dpwd,$options=OPENSSL_RAW_DATA,$ivc);
	$ivl=openssl_cipher_iv_length($cipher="chacha20");
	$ivc=substr($iv,0,$ivl);
	$rc=openssl_encrypt($rc,"CHACHA20",$dpwd,$options=OPENSSL_RAW_DATA,$ivc);
	$ivl=openssl_cipher_iv_length($cipher="CAMELLIA-256-CFB");
	$ivc=substr($iv,0,$ivl);
	$rc=openssl_encrypt($rc,"CAMELLIA-256-CFB",$dpwd,$options=OPENSSL_RAW_DATA,$ivc);
	$rcf=$iv."!".base64_encode($rc);
	return($rcf);
}
// DECRYPTION FUNCTION
function decrypt($enctext,$pwd){
	$s=$enctext;
	$iv=substr($s,0,64);
	$dbh=substr($s,64);
	$db=base64_decode($dbh);
	$dpwd=openssl_pbkdf2($pwd,$iv,64,100,"sha512");
	$ivl=openssl_cipher_iv_length($cipher="CAMELLIA-256-CFB");
	$ivc=substr($iv,0,$ivl);
	$r=openssl_decrypt($db,"CAMELLIA-256-CFB",$dpwd,$options=OPENSSL_RAW_DATA,$ivc);
	$ivl=openssl_cipher_iv_length($cipher="CHACHA20");
 	$ivc=substr($iv,0,$ivl);
	$r=openssl_decrypt($r,"CHACHA20",$dpwd,$options=OPENSSL_RAW_DATA,$ivc);
	$ivl=openssl_cipher_iv_length($cipher="AES-256-OFB");
	$ivc=substr($iv,0,$ivl);
	$r=openssl_decrypt($r,"AES-256-OFB",$dpwd,$options=OPENSSL_RAW_DATA,$ivc);
	return($r);
}
//************************************************************
// SOCKET CLASS
//************************************************************
class SocketClient {

	private $connection;
	private $address;
	private $port;

	public function __construct( $connection ) {
		$address = ''; 
		$port = '';
		socket_getsockname($connection, $address, $port);
		$this->address = $address;
		$this->port = $port;
		$this->connection = $connection;
	}
	
	public function send( $message ) {	
		socket_write($this->connection, $message, strlen($message));
	}
	
	public function read($len = 1024) {
		if ( ( $buf = @socket_read( $this->connection, $len, PHP_BINARY_READ  ) ) === false ) {
				return null;
		}
		
		return $buf;
	}

	public function getAddress() {
		return $this->address;
	}
	
	public function getPort() {
		return $this->port;
	}
	
	public function close() {
		socket_shutdown( $this->connection );
		socket_close( $this->connection );
	}
}

class SocketException extends \Exception {

	const CANT_CREATE_SOCKET = 1;
	const CANT_BIND_SOCKET = 2;
	const CANT_LISTEN = 3;
	const CANT_ACCEPT = 4;
	
	public $messages = array(
		self::CANT_CREATE_SOCKET => 'Can\'t create socket: "%s"',
		self::CANT_BIND_SOCKET => 'Can\'t bind socket: "%s"',
		self::CANT_LISTEN => 'Can\'t listen: "%s"',
		self::CANT_ACCEPT => 'Can\'t accept connections: "%s"',
	);
	
	public function __construct( $code, $params = false ) {
		if( $params ) {
			$args = array( $this->messages[ $code ], $params );
			$message = call_user_func_array('sprintf', $args );
		}
		else {
			$message = $this->messages[ $code ];
		}
		
		parent::__construct( $message, $code );
	}
}
class SocketServer {
	
	protected $sockServer;
	protected $address;
	protected $port;
	protected $_listenLoop;
	protected $connectionHandler;
	
	public function __construct( $port = 4444, $address = '127.0.0.1' ) {
		$this->address = $address;
		$this->port = $port;
		$this->_listenLoop = false;
	}
	
	public function init() {
		$this->_createSocket();
		$this->_bindSocket();
	}
	
	private function _createSocket() {
		$this->sockServer = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if( $this->sockServer === false ) {
			throw new SocketException( 
				SocketException::CANT_CREATE_SOCKET, 
				socket_strerror(socket_last_error()) );
		}
		
		socket_set_option($this->sockServer, SOL_SOCKET, SO_REUSEADDR, 1);
	}
	
	private function _bindSocket() {
		if( socket_bind($this->sockServer, $this->address, $this->port) === false ) {
			throw new SocketException( 
				SocketException::CANT_BIND_SOCKET, 
				socket_strerror(socket_last_error( $this->sockServer ) ) );
		}
	}
	
	public function setConnectionHandler( $handler ) {
		$this->connectionHandler = $handler;
	}
	
	public function listen() {
		if( socket_listen($this->sockServer, 5) === false) {
			throw new SocketException( 
				SocketException::CANT_LISTEN, 
				socket_strerror(socket_last_error( $this->sockServer ) ) );
		}

		$this->_listenLoop = true;
		$this->beforeServerLoop();
		$this->serverLoop();
		
		socket_close( $this->sockServer );
	}
	
	protected function beforeServerLoop() {
		printf( "Listening on %s:%d...\n", $this->address, $this->port );
	}
	
	protected function serverLoop() {
		while( $this->_listenLoop ) {
		        $status=0;
		        pcntl_wait($status,WNOHANG);
			if( ( $client = @socket_accept( $this->sockServer ) ) === false ) {
				throw new SocketException(
						SocketException::CANT_ACCEPT,
						socket_strerror(socket_last_error( $this->sockServer ) ) );
				continue;
			}
				
			$socketClient = new SocketClient( $client );
				
			if( is_array( $this->connectionHandler ) ) {
				$object = $this->connectionHandler[0];
				$method = $this->connectionHandler[1];
				$object->$method( $socketClient );
			}
			else {
				$function = $this->connectionHandler;
				$function( $socketClient );
			}
		}
	}

}

?>