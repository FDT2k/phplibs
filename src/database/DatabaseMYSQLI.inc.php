<?php
namespace ICE\lib\database;
use \ICE\lib\helpers as helpers;
use \ICE\Env as Env;
use \ICE\lib\scaffolding as sf;
/**********************************
Mysql Database class
***********************************/

class DatabaseMYSQLI extends Database{

	protected function connect($db,$server,$user,$password){
		$this->error = ERROR_NO_ERROR;
		Env::getLogger('sql')->startLog('Connecting to '.$user.'@'.$server.'/'.$db.' :'.$this->getOption('forceNewLink') );

		if($this->handler = mysqli_connect($server,$user,$password,$this->database,$this->port)){

			$this->bConnected = true;
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


	public function setCharset($charset){
		mysqli_set_charset($this->handler,$charset);
	}

	public function selectDatabase($database){
		$this->error = ERROR_NO_ERROR;
		if(!mysqli_select_db($this->handler,$database)){
			$this->error = ERROR_DATABASE_NOT_FOUND;
			throw new  \ICE\core\Exception(sprintf(__('database "%s" not found'),$database),0);
			return false;
		}
		$this->db=$database;
		return true;
	}

	public function currentDatabase(){
		return $this->db;
	}

	public function getTables($sDB=''){
		if(empty($sDB)){
			$sDB = $this->db;
		}
		//$rs = new MysqliResultset($this,'',0,false,mysqli_list_tables($sDB));

		$rs = $this->executeQuery("show TABLES");

		while ($result = $rs->fetchRow()) {
			$tables[] = $result[0];
		}
		$rs->free();
		unset($rs);
		return $tables;
	}

/*	function tableExists($table){
		$tables = $this->getTables();
		return in_array($table,$tables);
	}
*/
	function createTable($table,$ifNotExists=true){
		$sql ="CREATE TABLE  ".($ifNotExists?"IF NOT EXISTS":"")." ".$this->convertStringSQL($table).";";
		var_dump($sql);
		return $this->executeUpdate($sql);

	}

	function updateOrCreateTable($table,$fields){
		/*

		*/
	//	var_dump($fields);
		//copy the table if it exists;
		$this->executeUpdate('START TRANSACTION;');
		$table_exists = $this->tableExists($table);
		$cmd = "CREATE TABLE ".$this->convertStringSQL($table);
		if($table_exists){
			$current_fields = $this->getFieldsByName($table);
	//	var_dump($current_fields);
			//$cmd = "ALTER TABLE";
			$sql .=" CREATE TABLE ".$this->convertStringSQL($table.'_tmp')." LIKE ".$this->convertStringSQL($table).";\n";
			$sql .= "INSERT ".$this->convertStringSQL($table.'_tmp')." SELECT * FROM ".$this->convertStringSQL($table).";\n";

			$fields_to_add = array_diff_key($fields,$current_fields);
			$fields_to_remove=  array_diff_key($current_fields,$fields);
			var_dump($fields_to_add);
			var_dump($fields_to_remove);
			// add the new fields
			$sql.="ALTER TABLE ".$this->convertStringSQL($table.'_tmp')."\n";
			foreach($fields_to_add as $field){
				$sql.="ADD COLUMN ".$field['Field']." ".$field['Type']." DEFAULT 0\n";
			}
			$sql.=";";
		}
		//create inexistent fields in the new table;
var_dump($sql);
		//do the data migration

		//
	}


	public function executeQuery($query,$iTTL=0){
		$this->setError(ERROR_NO_ERROR);
		$this->clearError();

	/*	if($this->getOption(OPTION_DUMP_QUERIES)){
			echo "<font color=\"blue\">".$query."</font>";
		}*/
	//var_dump($query."<bR>");
		$rs = new MysqliResultset($this,$query,$iTTL,false);
		//var_dump('test ',$rs);
		$e = $this->getError();
		//var_dump($e!=ERROR_NO_ERROR && !empty($e));

		if($e==ERROR_NO_ERROR || empty($e)){
		//	var_dump('test');

		//var_dump($rs);
			$this->setLastResultSet($rs);
			return $rs;
		}
	//	var_dump($rs);
		return false;
	}

	public function executeUpdate($query){

		//var_dump($this->options);
		$this->setError(ERROR_NO_ERROR);
		$this->clearError();

		/*if($this->getOption(OPTION_DUMP_QUERIES)){
			echo "<font color=\"red\">".$query."</font>";
		}*/

		//if($this->getOption(OPTION_RUN_UPDATES)){
		if(!$this->getOption('dontRunUpdates')){

			$this->affectedRows=-1;

			$tmp = new MysqliResultset($this,$query,0,true);

			$tmp->free();
			$this->affectedRows = mysqli_affected_rows($this->handler);
			//var_dump($this->affectedRows);
			//var_dump($query,$this->errorOccured());
			if(!$this->errorOccured()){
			//var_dump(mysqli_error($this->handler))
				return true;
			}else{
				return false;
			}
		}else{
			return true;
		}
	}

	public function getErrorString(){
		return mysqli_errno($this->handler) . " : " . mysqli_error($this->handler);
	}
	public function getErrorCode(){
		return mysqli_errno($this->handler);
	}

	public function getAffectedRows(){
		$this->error = ERROR_NO_ERROR;
		return $this->affectedRows;
	}

	public function convertString($string){
		$this->error = ERROR_NO_ERROR;
		return "'".addslashes($this->convertStringCharset($string))."'";
	}

	public function convertStringSQL($string){
		return "`".$string."`";
	}

	public function convertStringNull($string){
		$this->error = ERROR_NO_ERROR;
		if(!empty($string)){
			return $this->convertString($string);
		}else{
			return "NULL";
		}
	}


	public function getUniqueId(){
		$this->error = ERROR_NO_ERROR;
		$id =  mysqli_insert_id($this->handler);
		return $id?$id:-1;
	}

	public function lastid(){
		$this->error = ERROR_NO_ERROR;
		$id = $this->getUniqueId();
		return $id==-1?false:$id;
	}

	public function getFields($table){
		if(!isset($this->columns[$table])){
			$this->getForeignKeys($table);
			$sSQL="SHOW FULL COLUMNS FROM ".$this->convertStringSQL($table);
			$rs = $this->executeQuery($sSQL,-1);
			//var_dump($rs);
			if($rs){
				while ($f =  $rs->fetchAssoc()){
					$fields[]=$f;
				}
			//var_dump($fields);
				$rs->free();
			}
			$this->columns[$table]=$fields;
		}else{
			$fields = $this->columns[$table];
		}
		return $fields;
	}


	function getFieldsByName($table){
		$f = $this->getFields($table);
		$fields = array();
		foreach($f as $field){
			$fields[$field['Field']]=$field;
		}
		return $fields;
	}


	public function getForeignKey($table,$field){
		return $this->foreignKeys[$table][$field];
	}

	public function isForeignKey($table,$field){
		$this->getForeignKeys($table);
	//	var_dump($this->foreignKeys);
		return isset($this->foreignKeys[$table][$field]);
	}

	public function getForeignKeys($table){
		if(!isset($this->foreignKeys[$table])){
			$rs = $this->executeQuery('SHOW CREATE TABLE '.$this->convertStringSQL($table), -1);
			if($rs){
				$result = $rs->fetchArray();

				$sql = explode("\n",$result['Create Table']);
		//	var_dump($sql);
				$regex = '/^CONSTRAINT `([^`]*)` FOREIGN KEY \(`([^`]*)`\) REFERENCES `([^`]*)` \(`([^`]*)`\)/i';
				foreach($sql as $line){
					$line = trim($line);
					if(preg_match($regex,$line,$matches)){
						list($crap,$crap,$key,$referenced_table,$referenced_field) = $matches;
				//	var_dump($crap,$crap,$key,$referenced_table,$referenced_field);
						$fkD = new sf\ForeignKeyDescriptor(new sf\FieldDescriptor($table,$key),new sf\FieldDescriptor($referenced_table,$referenced_field));
						$this->foreignKeys[$table][$key]=$fkD;
					}
				}
				$rs->free();
			}
		}
	}

	public function getIndexes($table){
		if(!isset($this->indexes[$table])){
			$rs = $this->executeQuery('SHOW CREATE TABLE '.$this->convertStringSQL($table), -1);
			$result = $rs->fetchArray();
			$sql = explode("\n",$result['Create Table']);

			$regex = '/^KEY `([^`]*)` \(`([^`]*)`\)/i';
			$uniqueregex = '/^UNIQUE KEY `([^`]*)` \(([^\)]*)\)/i';
			foreach($sql as $line){
				$line = trim($line);

				if(preg_match($regex,$line,$matches)){
					list($crap,$name,$fields) = $matches;
					//var_dump($name,$fields);

				}else if (preg_match($uniqueregex,$line,$matches)){

					list($crap,$name,$fields) = $matches;
					//var_dump($name,$fields);
					//var_dump($matches);
				}

				if(sizeof($matches)>0){
					$idx = new sf\IndexDescriptor($name);

					$f = explode(',',$fields);
					foreach($f as $field){
						$field = str_replace('`','',$field);
						$ff= new sf\FieldDescriptor($table,$field);
						$idx->addField($ff);
						$this->indexes[$table][$field]=$idx;
					}

				}
			}
			$rs->free();
		}
	}


	public function isIndex($table,$field){
		$this->getIndexes($table);
		return isset($this->indexes[$table][$field]);
	}

	public function tableExists($table){
		$rs = $this->executeQuery('SHOW CREATE TABLE '.$table,-1);
		$num =$rs->getNumRows();
		$rs->free();
		return ($num>0);
	}
}

class MysqliResultset extends Resultset {

	protected $rowCount=1;
	protected $bInitCount=false;
	protected function query($query,$bUpdate=false){
//
//var_dump($query);

		Env::getLogger('sql')->startLog('query: '.$query);

		$this->query = $query;
		$this->error = false;
//var_dump($this->cache);
		$this->update=  $bUpdate;
		if($bUpdate){
			$this->cache->invalidate();
		}

		if(!$this->database->getOption('cacheActive') || $this->cache->hasExpired()){
			$time_start = microtime(true);
			//var_dump($this->cache->hasExpired());
			if($this->cache->hasExpired()){
				$this->cache->destroy();
			}

			if(!$this->database->isConnected()){

				$this->database->doConnection();
			}

				$this->resultset = mysqli_query($this->database->getHandler(),$query);
			//var_dump(mysqli_error($this->database->getHandler()));
			$time_end = microtime(true);
			$time = $time_end - $time_start;
			$this->totalTime += $time;
			if($this->database->getOption('dumpQueries')){
				if($bUpdate){
					echo "<div style=\"color: red; font-weight: bold; border: 1px solid #000000; background-color:#CACACA\">".$query."<br/><p style=\"font-size: 10px;\">Query time: ".$time." secs <br> Affected rows: ".mysqli_affected_rows($this->database->getHandler())."</p></div>";

				}else{
					echo "<div style=\"color: blue; border: 1px solid #000000; background-color:#CACACA\">".$query."<br/><p style=\"font-size: 10px;\">Query time: ".$time." secs <br> Results:".$this->getNumRows()."</p></div>";
				}
			}

			if($this->resultset){
				Env::getLogger('sql')->endLog('query: '.$query);
				return true;
			}else{
				//var_dump(mysqli_error($this->database->getHandler()));
				//throw new IMCoreException(sprintf(__('Query error %s'),mysqli_error($this->database->getHandler())),0,__('Query error'));
		//		var_dump('query failed '.$query );
				return false;
			}
		}else if($this->database->getOption('cacheActive') && !$this->cache->hasExpired()){
			Env::getLogger()->log('Cached query: '.$query);
			if($this->database->getOption('dumpQueries')){
				echo "<div style=\"color: purple; border: 1px solid #000000; background-color:#CACACA\">".$query."<br/><p style=\"font-size: 10px;\">Query time: ".$time." secs <br> Results:".$this->getNumRows()."</p></div>";
			}
			return true;
		}
		Env::getLogger('sql')->log('[WARNING] query failed: '.$query);
		return false;
	}

	protected function fetchDatas($type=self::ARRAY_FETCH){
		//static $rowCount ;
		//static $bInitCount;

		switch($type){
			case self::ASSOC_FETCH:
				$fetchFunc= 'mysqli_fetch_assoc';
				$internalFunc = "fetchAssoc";
			break;
			case self::ROW_FETCH:
				$fetchFunc= 'mysqli_fetch_row';
				$internalFunc = "fetchRow";
			break;
			case self::ARRAY_FETCH:
			default:
				$fetchFunc= 'mysqli_fetch_array';
				$internalFunc = "fetchArray";

			break;
		}
		if (is_numeric($this->currentPage) && is_numeric($this->pageSize)) { // should we handle page control?
			if($this->rowCount <= $this->pageSize ){
				if($this->currentPage > 1 && !$this->bInitCount){ // move the cursor to the correct record
					for ($i=0;$i<($this->currentPage * $this->pageSize)-$this->pageSize;$i++){

						if(!$fetchFunc($this->resultset)){
							$this->row = false;
							break;
						}
					}
					$this->bInitCount = true;
				}

				//$row = $fetchFunc($this->resultset);
				if($this->database->getOption('cacheActive') && !$this->cache->hasExpired()){
					$row = $this->cache->$internalFunc($pagesize,$currentpage); // give the cache the correct page
				}else{
					$row = $fetchFunc($this->resultset);
				}
				if ($row) {
					$row = $this->convertCharset($row);
				}
				if($this->cache->hasExpired()){
					$this->cache->append($row);
				}
				$this->row = $row;
				$this->rowCount++;

				return $this->row;
			}else{
				$this->rowCount = 1;
			}
			$this->bInitCount = false;
			return false;
		}else{ // no page control
			$this->rowCount = 1;
			$this->bInitCount = false;
			/*if(!$this->resultset){
				throw new IMCoreException("SQL ERROR ",0,$this->database->getError());
			}*/
			if($this->database->getOption('cacheActive') && !$this->cache->hasExpired()){
				$row = $this->cache->$internalFunc();
			}else{
				$row = $fetchFunc($this->resultset);
			}

			if ($row) {
				$row = $this->convertCharset($row);
			}
			if($this->cache->hasExpired()){
				$this->cache->append($row);
			}
			$this->row =  $row;
			return $this->row;
		}
		return false;
	}


	public function getNumRows(){
		if(!$this->update){
		if(!$this->database->getOption('cacheActive') || $this->cache->hasExpired()){
			if($this->resultset){
				return mysqli_num_rows($this->resultset);
			}
		}else{

			return $this->cache->iNumRows;
		}
		}
		return -1;

	}

	public function free(){
			parent::free();

		@mysqli_free_result($this->resultset);
	}

	public function getFieldName($num) {
		$this->error=ERROR_NO_ERROR;
		$fieldInfo=mysqli_fetch_field_direct($this->resultset, $num);
		return $fieldInfo->name;
	}

	public function getFieldType($num) {
		$this->error=ERROR_NO_ERROR;
		$fieldInfo=mysqli_fetch_field_direct($this->resultset, $num);
		return $fieldInfo->type;
	}
	public function getFieldInfo($num) {
		$this->error=ERROR_NO_ERROR;
		$fieldInfo=mysqli_fetch_field_direct($this->resultset, $num);
		return $fieldInfo;
	}

	public function getFieldTable($num) {
		$this->error=ERROR_NO_ERROR;
		$fieldInfo=mysqli_fetch_field_direct($this->resultset, $num);
		return $fieldInfo->column_source;
	}
	public function getFieldFlags($num){
		$this->error=ERROR_NO_ERROR;
		$flags = mysqli_field_flags($this->resultset,$num);
		return $flags;
	}

	public function getFieldLength($num) {
		$this->error=ERROR_NO_ERROR;
		$fieldInfo=mysqli_fetch_field_direct($this->resultset, $num);
		return $fieldInfo->max_length;
	}

	public function getNumFields() {
		$this->error=ERROR_NO_ERROR;
		if(!$this->database->getOption('cacheActive') || $this->cache->hasExpired()){
			return mysqli_num_fields($this->resultset);
		}else{
			return $this->cache->iFields;
		}

	}

	public function getFields(){
		/*
array(9) {
  ["Field"]=>
  string(9) "device_id"
  ["Type"]=>
  string(7) "int(11)"
  ["Collation"]=>
  NULL
  ["Null"]=>
  string(2) "NO"
  ["Key"]=>
  string(3) "PRI"
  ["Default"]=>
  NULL
  ["Extra"]=>
  string(14) "auto_increment"
  ["Privileges"]=>
  string(31) "select,insert,update,references"
  ["Comment"]=>
  string(0) ""
}*/
		$count = $this->getNumFields();
		for ($i=0; $i < $count; $i++) {
			$field = $this->fetchField($i);
			$fields []= (array) $field;

			//$f['N']
		}
		return $fields;
	}

	public function dataSeek($row) {
		$this->error=ERROR_NO_ERROR;
		return mysqli_data_seek($this->resultset, $row);
	}

	public function fieldSeek($cols) {
		$this->error=ERROR_NO_ERROR;
		return mysqli_field_seek($this->resultset, $cols);
	}

	public function fetchField($num) {
		$this->error=ERROR_NO_ERROR;
		return mysqli_fetch_field_direct($this->resultset, $num);
	}

}
