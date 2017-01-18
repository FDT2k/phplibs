<?php
namespace ICE\lib\database;

use \ICE\lib\helpers as helpers;
use \ICE\Env as Env;

define('DB_TYPE_MYSQL',1);
define('DB_TYPE_MSSQL',2);
define('DB_TYPE_MYSQLI',4);
define ('ERROR_NO_ERROR',0);
abstract class Database extends \ICE\core\IObject {
	protected $handler;
	protected $affectedRows;
	protected $typeToConvertCharset;

	protected $dbType;
	protected $bConnected;
	protected $foreignKeys;
	protected $indexes;

	public $charsetFrom;
	public $charsetTo;

	public $db;
	public static $totalTime;

	protected $transaction= false;
	protected $buffer = '';

	public static function factory($id,$url=''){
		static $databases;
		if(!is_object($databases[$id]) && !empty($url)){
		//var_dump($url);
			$URI = new helpers\URI($url);
		//	var_dump($URI);
		//	var_dump(IMCoreEnv::getDocumentRoot());
			//var_dump($URI);
			if(!empty($URI->hostname)){
				//switch ($URI->protocol){
				//if(file_exists(Env::getFrameworkFSPath().'/lib/database/'.$URI->protocol.'.'.IMCORE_FRAMEWORK_EXTENSION)){
					if($URI->protocol == 'sqlite' &&  ((PHP_VERSION_ID < 50300) )){
						$className = 'sqlite_php52';
					}else{
						$className = $URI->protocol;
					}

			//		require_once('lib/database/'.$className.'.'.IMCORE_FRAMEWORK_EXTENSION);
					if($URI->protocol == 'sqlite' ){

						$protocol = 'IMSQLiteDatabase';
					}else{
						$protocol = "ICE\lib\database\Database".strtoupper ($URI->protocol);
					}

					$config = 'database';

					foreach($URI->query as $key=> $value){
						if($key =='config'){
							$config = $value;
						}
					}


					$databases[$id] = new $protocol($URI->path[0],$URI->hostname,$URI->username,$URI->password,$URI->port,$URI->query,$config,$protocol);
					
					//var_dump($databases[$id]->getHandler());
				//	IMCoreEnv::getLogger()->log('Creating new database object '.$URI->username.'@'.$URI->hostname.'/'.$URI->path[0]);
					//$db,$server,$user,$password,$options=""

			//	}else{
			//		throw new IMCoreException('Can\'t find database class',0);
			//	}
				//}
				return $databases[$id];
			}
		}else{
			return $databases[$id];
		}
		return false;
	}

	function __construct($db,$server,$user,$password,$port=0,$options="",$groupconfig="database",$type='none'){
		$this->setDefaultOptionGroup($groupconfig);
		$this->bConnected = false;

		//$this->setOptions($options);
		//$this->options = $this->parseOptions($options);
		$this->db = $db;
		$this->server = $server;
		$this->user = $user;
		$this->password = $password;
		$this->charsetFrom=$this->getOption('convertCharsetFrom');
		$this->charsetTo=$this->getOption('convertCharsetTo');
		$this->port = $port;
		/*if($this->getOption('autoConnect')){
			$this->doConnection();
		}*/
		$this->dbType=$type;
	}

	public function beginTransaction(){
		$this->transaction = true;
		//$this->buffer = 'BEGIN;'
		return $this->executeUpdate('BEGIN;');
	}

	public function commitTransaction(){
		//$this->buffer .= 'COMMIT;';
		return $this->executeUpdate('COMMIT;');
		//$this->buffer = ''
	}
	public function rollbackTransaction(){
		return $this->executeUpdate('ROLLBACK;');
	}

	public function isConnected(){
		return $this->bConnected;
	}


	function __destruct(){

	}

	public function getHandler(){
		return $this->handler;
	}


	public function doConnection(){
		if($this->handler = $this->connect($this->db,$this->server,$this->user,$this->password)){
			$this->selectDatabase($this->db);
			$this->typeToConvertCharset=array("char", "string");
			return true;
		}
		throw new ImpException('Can\'t connect to database');
		return false;
	}

	abstract public function getTables($db='');
	abstract public function getFields($table);

	abstract protected function connect($db,$server,$user,$password);

	abstract public function selectDatabase($database);

	abstract public function executeQuery($query,$iTTL=0);

	abstract public function executeUpdate($query);

	abstract public function getErrorString();

	abstract public function getAffectedRows();

	abstract public function convertString($string);
	abstract public function convertStringSQL($string);
	//abstract public function convertStringTable($string);

	abstract public function convertStringNull($string);

	/*abstract public function listDatabases();

	abstract public function listCurrentDBTables();

	abstract public function listTableFields($table);
	*/
	public function explainError($error=""){
		if(empty($error)){
			$error = $this->error;
		}
		switch ($error){
			case ERROR_NO_ERROR:
			break;
			case ERROR_BAD_DSN:
				echo "Given dsn is not valid.";
			break;
			case ERROR_BAD_QUERY:
				echo "An error has occured while processing your query. Server said : ".$this->getError();
			break;
			case ERROR_DATABASE_NOT_FOUND:
				echo "The specified database cannot be found";
			break;
			case ERROR_HOST_NOT_FOUND:
				echo "The specified host cannot be found";
			break;
			case ERROR_NOT_CONNECTED:
				echo "Not connected to server";
			break;
			case ERROR_NOT_SUPPORTED:
				echo "This function isn't supported under your current database server version";
			break;
			case ERROR_USER_PASS:
				echo "Wrong username/password";
			break;
		}

	}



	public function executeFile($filename){
		$bSuccess=true;
		if (!is_file($filename)) {
			return false;
		}
		$sSqlLines=file($filename);
		foreach($sSqlLines as $sSqlLine) {
			if (strpos(trim($sSqlLine), '#')===false)
				$sSql.=trim($sSqlLine);
			}
		$this->splitSqlFile($sSqlQueries,$sSql);
		foreach($sSqlQueries as $sQuery) {
			if (!empty($sQuery)) { // la dernière requete est vide
				$this->executeUpdate($sQuery);
			}
		}
		return $bSuccess;
	}

	protected function convertStringCharset($string) {

		if (function_exists("iconv")) {
			if ($this->charsetFrom) {
				if ($this->charsetTo) {
					// On fait dans le sens inverse
					//var_dump('convert');
				//	var_dump($this->charsetFrom,$this->charsetTo,$string);
					return iconv($this->charsetTo, $this->charsetFrom."//IGNORE", $string);
				}
			}
		}

		return $string;
	}

	protected function splitSqlFile(&$ret, $sql){
		$release = 32270;
		$sql          = trim($sql);
		$sql_len      = strlen($sql);
		$char         = '';
		$string_start = '';
		$in_string    = FALSE;
		$time0        = time();

		for ($i = 0; $i < $sql_len; ++$i) {
			$char = $sql[$i];
			if ($in_string) {
				for (;;) {
					$i         = strpos($sql, $string_start, $i);
					if (!$i) {
						$ret[] = $sql;
						return TRUE;
					}
					else if ($string_start == '`' || $sql[$i-1] != '\\') {
						$string_start      = '';
						$in_string         = FALSE;
						break;
					}
					else {
						$j                     = 2;
						$escaped_backslash     = FALSE;
						while ($i-$j > 0 && $sql[$i-$j] == '\\') {
							$escaped_backslash = !$escaped_backslash;
							$j++;
						}
						if ($escaped_backslash) {
							$string_start  = '';
							$in_string     = FALSE;
							break;
						}
						else {
							$i++;
						}
					}
				}
			}
			else if ($char == ';') {
				$ret[]      = substr($sql, 0, $i);
				$sql        = ltrim(substr($sql, min($i + 1, $sql_len)));
				$sql_len    = strlen($sql);
				if ($sql_len) {
					$i      = -1;
				} else {
					return TRUE;
				}
			}
			else if (($char == '"') || ($char == '\'') || ($char == '`')) {
				$in_string    = TRUE;
				$string_start = $char;
			}
			else if ($char == '#'
			|| ($char == ' ' && $i > 1 && $sql[$i-2] . $sql[$i-1] == '--')) {
				$start_of_comment = (($sql[$i] == '#') ? $i : $i-2);
				$end_of_comment   = (strpos(' ' . $sql, "\012", $i+2))
				? strpos(' ' . $sql, "\012", $i+2)
				: strpos(' ' . $sql, "\015", $i+2);
				if (!$end_of_comment) {
					if ($start_of_comment > 0) {
						$ret[]    = trim(substr($sql, 0, $start_of_comment));
					}
					return TRUE;
				} else {
					$sql          = substr($sql, 0, $start_of_comment)
					. ltrim(substr($sql, $end_of_comment));
					$sql_len      = strlen($sql);
					$i--;
				}
			}
			else if ($release < 32270 && ($char == '!' && $i > 1  && $sql[$i-2] . $sql[$i-1] == '/*')) {
				$sql[$i] = ' ';
			}
			// loic1: send a fake header each 30 sec. to bypass browser timeout
			$time1     = time();
			if ($time1 >= $time0 + 30) {
				$time0 = $time1;
//				header('X-pmaPing: Pong');
			}
		}
		if (!empty($sql) && ereg('[^[:space:]]+', $sql)) {
			$ret[] = $sql;
		}
		return TRUE;
	}

}

abstract class Resultset{
	protected $currentPage;
	protected $pageSize;
	protected $resultset;
	protected $row;
	protected $cache;
	protected $iTTL;
	protected $sCurrentContext;

	public $charsetFrom;
	public $charsetTo;

	const ARRAY_FETCH = 1;
	const ASSOC_FETCH = 2;
	const ROW_FETCH = 3;

	function hasResult(){
		return ($this->resultset !=false);
	}

	public static function factory(&$database,$query,$iTTL=0,$bUpdate=false){

		$buffered = $this->database->getOption('bufferedQueries');
		if($iTTL > 0){ // cached part
			switch($database->getType()){
				case DB_TYPE_MYSQL:
					return new MysqlResultset($query,$buffered);
				break;
			}
		}else{
			return new SQLCache($query);
		}
	}

	function __construct(&$database,$query="",$iTTL=0,$bUpdate=false,$handler=null){
		$this->database = &$database;
		$this->iTTL = $iTTL;

		if(!empty($query) && is_string($query)){
			$this->cache = new SQLCache($query);
			if(!$this->query($query,$bUpdate)){
				$this->database->setError(ERROR_BAD_QUERY);
				//throw new \Exception();
			}
		}else if ($handler != null){
			$this->resultset = $handler;
		} else {
			$this->database->setError(ERROR_BAD_QUERY);
		}

	}

	function __destruct(){

	}

	protected function setContext($sContext){
		$this->sCurrentContext = $sContext;
	}

	public function setPageSize($int){
		$this->pageSize = $int;
		if(is_numeric($int)){
			$this->cache->setPageSize($int);
			if($numrows = $this->getNumRows()){

				$this->pagesCount = intval(ceil($numrows / $this->pageSize));

				/*if(intval(round($numrows % $this->pageSize)) > 0){
					$this->pagesCount++;
				}*/
			}
		}
	}

	public function setCurrentPage($int){
		$this->currentPage = $int;
		$this->cache->setCurrentPage($int);
	}

	public function getPagesCount(){
		return $this->pagesCount;
	}


	public function fetchRow(){
		return $this->fetchDatas(self::ROW_FETCH);
	}

	public function fetchAssoc(){
		return $this->fetchDatas(self::ASSOC_FETCH);
	}

	public function fetchArray(){
		return $this->fetchDatas(self::ARRAY_FETCH);
	}

	public function fetchAllRows(){
		while($result = $this->fetchRow()){
			$datas[] = $result ;
		}
		return $datas;
	}

	public function fetchAllAssoc(){
		while($result = $this->fetchAssoc()){
			$datas[] = $result ;
		}
		return $datas;
	}

	public function fetchAllArray(){
		while($result = $this->fetchArray()){
			$datas[] = $result ;
		}
		return $datas;
	}

	abstract public function getNumRows();

	public function free(){
		if($this->resultset){
			if($this->database->getOption('cacheActive')
				&&
				$this->cache->hasExpired() //do not always write cache
			){

				$this->cache->save($this->iTTL,$this->getNumRows());
			}
		}
	}

	abstract public function getFieldName($num);

	abstract public function getFieldType($num);

	abstract public function getFieldTable($num);

	abstract public function getFieldLength($num);

	abstract public function getNumFields();

	abstract protected function fetchDatas($type=self::ARRAY_FETCH);

	abstract protected function query($query);

	protected function convertCharset($row) {
		if (function_exists("iconv")) {
			if ($this->charsetFrom && $this->charsetTo) {
					for ($i=0; $i<$this->getNumFields(); $i++) {
						if (in_array($this->getFieldType($i), $this->database->typeToConvertCharset)) {
							if ($convString=iconv($this->charsetFrom, $this->charsetTo."//IGNORE", $row[$i])) {
								$row[$i]=$convString;
								$fieldname=$this->getFieldName($i);

								if (!empty($fieldname)) {
									$row[$fieldname]=$convString;
								}
							}
						}
					}

			}
		}

		return $row;
	}

	public function fetchAssocs(){
		while($result = $this->fetchAssocs()){
			$return[] = $result;
		}
		return $return;
	}


	public function fetchArrays(){
		while($result = $this->fetchArray()){
			$return[] = $result;
		}
		return $return;
	}

	public function fetchRows(){
		while($result = $this->fetchRow()){
			$return[] = $result;
		}
		return $return;
	}

	public function isValid() {
			return $this->resultset!=false;
	}
}
?>
