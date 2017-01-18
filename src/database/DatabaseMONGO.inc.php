<?
namespace ICE\lib\database;
use \ICE\lib\helpers as helpers;
use \ICE\Env as Env;
use \ICE\lib\scaffolding as sf;
/**********************************
Mysql Database class
***********************************/

class DatabaseMONGO extends Database{
	protected $databaseHandler;
	protected function connect($db,$server,$user,$password){
		$this->error = ERROR_NO_ERROR;
		Env::getLogger('sql')->startLog('Connecting to '.$user.'@'.$server.'/'.$db.' :'.$this->getOption('forceNewLink') );

		if($this->handler = new \MongoClient("mongodb://$user:$password@$server:$this->port/$db")){

			$this->bConnected = true;
			$dbString = $db;
			$this->databaseHandler = $this->handler->$dbString;
			if($charset = $this->getOption('clientCharset')){
				$this->setCharset($charset);
			}else{
				$this->setCharset('utf8');
			}
			Env::getLogger('sql')->endLog('Connecting to '.$user.'@'.$server.'/'.$db.' :'.$this->getOption('forceNewLink') );
			return $this->handler;
		}else{

			$this->error = ERROR_NOT_CONNECTED;
			throw new \ICE\core\Exception('can\'t connect to database',0);
		}


		return false;

	}

 public function getTables($db=''){}
 public function getFields($table){}

 public function selectDatabase($database){

	 $this->databaseHandler = $this->handler->$database;
 }

 public function executeQuery($query,$iTTL=0){

	 throw new Exception("Deprecated method, no query allowed with this database engine");
 }

 public function executeUpdate($query){

	 throw new Exception("Deprecated method, no query allowed with this database engine");
 }

 public function getErrorString(){}

 public function getAffectedRows(){}

 public function convertString($string){}
 public function convertStringSQL($string){}
	//abstract public function convertStringTable($string);

 public function convertStringNull($string){}


}
