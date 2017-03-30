<?php
namespace ICE\lib\net;
use \ICE\Env as Env;


class SSH extends \ICE\core\IObject{
    // SSH Host
	private $ssh_host = '';
    // SSH Port
	private $ssh_port = 22;
    // SSH Server Fingerprint
	private $ssh_server_fp = '';
    // SSH Username
	private $ssh_auth_user = 'root';
    // SSH Public Key File
	private $ssh_auth_pub = '';
    // SSH Private Key File
	private $ssh_auth_priv = ''; 
    // SSH Private Key Passphrase (null == no passphrase)
	private $ssh_auth_pass;
    // SSH Connection
	private $connection;

	public function __construct($host,$port=22){
		##parent::__construct();
		$this->setHost($host);
		$this->setPort($port);
		$this->setFingerPrintChecking(false);
		#$this->connect();
	}

	public function connect() {
		$result = true;
		if (($this->connection = @ssh2_connect($this->getHost(), $this->getPort()))) {
			//throw new \ICE\core\Exception('Cannot connect to server',2);

			if($this->isFingerPrintChecking() && ! $fingerprint = ssh2_fingerprint($this->connection, SSH2_FINGERPRINT_MD5 | SSH2_FINGERPRINT_HEX)){

			    if (strcmp($this->getFingerPrint(), $fingerprint) !== 0) {
			       // throw new \ICE\core\Exception('Unable to verify server identity!',2);
			    	$result = false;
			    	$this->setError('Fingerprint mismatch' );
			    }
	        }
	       # if($this->hasSSHPrivateKey() && $this->hasSSHPublicKey()){
	       	#en var_dump($this->getSSHPublicKey());
	        if (!ssh2_auth_pubkey_file($this->connection, $this->getUser(), $this->getSSHPublicKey(), $this->getSSHPrivateKey(), $this->getPassword())) {
	        	//throw new \ICE\core\Exception('Autentication rejected by server',2);
	        	$result = false;
	        	$this->setError('Can\'t authenticate with ssh server' );
	        }
		} else{
			$result = false;
			$this->setError('Can\'t connect to ssh server' );
		}

        #}
        return $result;
    }

    public function exec($cmd) {
    	if (!($stream = @ssh2_exec($this->connection, $cmd))) {
    		//throw new \ICE\core\Exception('SSH command failed',2);
    		return false;
    	}
    	stream_set_blocking($stream, true);
    	$data = "";
    	while ($buf = fread($stream, 4096)) {
    		$data .= $buf;
    	}
    	fclose($stream);
    	return $data;
    }

    public function run ($cmd, $stdout,$stderr){
    	if ($shell = ssh2_exec($this->connection, $cmd)){

			$stderr_stream = ssh2_fetch_stream($shell, SSH2_STREAM_STDERR);
			#$stdio_stream = ssh2_fetch_stream($shell, SSH2_STREAM_STDDIO);

			stream_set_blocking($stderr_stream, true);
			stream_set_blocking($shell, true);
			#stream_set_blocking($stdio_stream, true);

			$err = stream_get_contents($stderr_stream);
			$out = stream_get_contents($shell);

			stream_set_blocking($stderr_stream, false);
			stream_set_blocking($shell, false);

			if(!empty($err) && $stderr){
				$stderr($err);
			}
			if(!empty($out) && $stdout){
				$stdout($out);
			}
		}
		return false;
    }

    public function disconnect() {
    	if($this->connection){
    		$this->exec('echo "EXITING" && exit;');
    		$this->connection = null;
    	}
    }

    public function __destruct() {
    	$this->disconnect();
    }
}
