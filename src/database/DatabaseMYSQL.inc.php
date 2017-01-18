<?
namespace ICE\lib\database;
use \ICE\lib\helpers as helpers;
use \ICE\Env as Env;
use \ICE\lib\scaffolding as sf;
/**********************************
Mysql Database class
***********************************/

class DatabaseMYSQL extends Database{
	
	protected function connect($db,$server,$user,$password){
		$this->error = ERROR_NO_ERROR;
		Env::getLogger()->log('Connecting to '.$user.'@'.$server.'/'.$db.' :'.$this->getOption('forceNewLink') );
		if($this->handler = mysql_connect($server,$user,$password,$this->getOption('forceNewLink'))){
		
			$this->bConnected = true;
			if($charset = $this->getOption('clientCharset')){
				$this->setCharset($charset);
			}else{
				$this->setCharset('utf8');
			}			
			return $this->handler;	
		}else{
		
			$this->error = ERROR_NOT_CONNECTED;
			throw new IMCoreException('can\'t connect to database',0);
		}
		return false;
		
	}

	public function setCharset($charset){
		mysql_set_charset($charset,$this->handler);
	}
	
	public function selectDatabase($database){
		$this->error = ERROR_NO_ERROR;
		if(!mysql_select_db($database,$this->handler)){
			$this->error = ERROR_DATABASE_NOT_FOUND;
			throw new IMCoreException(sprintf(__('database "%s" not found'),$database),0);
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
		$rs = new MysqlResultset($this,'',0,false,mysql_list_tables($sDB));
		while ($result = $rs->fetchRow()) {
			$tables[] = $result[0];
		}
		$rs->free();
		unset($rs);
		return $tables;
	}
	
	

	public function executeQuery($query,$iTTL=0){
		$this->setError(ERROR_NO_ERROR);
		$this->clearError();
		
	/*	if($this->getOption(OPTION_DUMP_QUERIES)){
			echo "<font color=\"blue\">".$query."</font>";
		}*/
	//var_dump($query."<bR>");
		$rs = new MysqlResultset($this,$query,$iTTL,false);
		//var_dump('test ',$rs);
		$e = $this->getError();
		//var_dump($e!=ERROR_NO_ERROR && !empty($e));
		
		if($e==ERROR_NO_ERROR || empty($e)){
		//	var_dump('test');
		
		//var_dump($rs);
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
			
			$tmp = new MysqlResultset($this,$query,0,true);
			
			$tmp->free();
			$this->affectedRows = mysql_affected_rows($this->handler);
			//var_dump($query,$this->errorOccured());
			if(!$this->errorOccured()){
			//var_dump(mysql_error($this->handler))
				return true;
			}else{
				return false;
			}
		}else{
			return true;
		}
	}

	public function getErrorString(){
		return mysql_errno($this->handler) . " : " . mysql_error($this->handler);
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
		if(!isEmpty($string)){
			return $this->convertString($string);
		}else{
			return "NULL";
		}
	}
	

	public function getUniqueId(){
		$this->error = ERROR_NO_ERROR;
		$id =  mysql_insert_id($this->handler);
		return $id?$id:-1;
	}

	public function lastid(){
		$this->error = ERROR_NO_ERROR;
		$id = $this->getUniqueId();
		return $id==-1?false:$id;
	}
	
	public function getFields($table){
		$this->getForeignKeys($table);
		$sSQL="SHOW FULL COLUMNS FROM ".$this->convertStringSQL($table);
		$rs = $this->executeQuery($sSQL,-1);
		//var_dump($rs);
		while ($f =  $rs->fetchAssoc()){
			$fields[]=$f;
		}
		//var_dump($fields);
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

class MysqlResultset extends Resultset {
	
	protected $rowCount=1;
	protected $bInitCount=false;
	protected function query($query,$bUpdate=false){
//
//var_dump($query);
		Env::getLogger()->startLog('starting query: '.$query);

		$this->query = $query;
		$this->error = false;
//var_dump($this->cache);
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
			if(!$this->database->getOption('unBufferedQueries')){
	
				$this->resultset = @mysql_query($query,$this->database->getHandler());
			}else{
				$this->resultset = @mysql_unbuffered_query($query,$this->database->getHandler());
			}
			//var_dump(mysql_error($this->database->getHandler()));
			$time_end = microtime(true);
			$time = $time_end - $time_start;
			$this->totalTime += $time;
			if($this->database->getOption('dumpQueries')){
				if($bUpdate){
					echo "<div style=\"color: red; font-weight: bold; border: 1px solid #000000; background-color:#CACACA\">".$query."<br/><p style=\"font-size: 10px;\">Query time: ".$time." secs <br> Affected rows: ".mysql_affected_rows($this->database->getHandler())."</p></div>";					
					
				}else{
					echo "<div style=\"color: blue; border: 1px solid #000000; background-color:#CACACA\">".$query."<br/><p style=\"font-size: 10px;\">Query time: ".$time." secs <br> Results:".$this->getNumRows()."</p></div>";
				}
			}
			
			if($this->resultset){
				Env::getLogger()->endLog('starting query: '.$query);
				return true;
			}else{
				//var_dump(mysql_error($this->database->getHandler()));
				//throw new IMCoreException(sprintf(__('Query error %s'),mysql_error($this->database->getHandler())),0,__('Query error'));
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
		Env::getLogger()->log('[WARNING] query failed: '.$query);
		return false;
	}

	protected function fetchDatas($type=self::ARRAY_FETCH){
		//static $rowCount ;
		//static $bInitCount;
		
		switch($type){
			case self::ASSOC_FETCH:
				$fetchFunc= 'mysql_fetch_assoc';
				$internalFunc = "fetchAssoc";	
			break;
			case self::ROW_FETCH:
				$fetchFunc= 'mysql_fetch_row';	
				$internalFunc = "fetchRow";	
			break;
			case self::ARRAY_FETCH:
			default:
				$fetchFunc= 'mysql_fetch_array';	
				$internalFunc = "fetchArray";	

			break;
		}
		
		if (is_numeric($this->currentPage) && is_numeric($this->pageSize)) {
	//	var_dump($this->rowCount,$this->pageSize);
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
				$this->row = $row;
				$this->rowCount++;

				return $this->row;
			}else{
				$this->rowCount = 1;
			}
			$this->bInitCount = false;
			return false;
		}else{
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
		
		if(!$this->database->getOption('cacheActive') || $this->cache->hasExpired()){
			if($this->resultset){
				return mysql_num_rows($this->resultset);
			}
		}else{

			return $this->cache->iNumRows;
		}
		return -1;
	
	}

	public function free(){
		
		@mysql_free_result($this->resultset);
		parent::free();
	}
	
	public function getFieldName($num) {
		$this->error=ERROR_NO_ERROR;
		$fieldInfo=mysql_fetch_field($this->resultset, $num);
		return $fieldInfo->name;
	}

	public function getFieldType($num) {
		$this->error=ERROR_NO_ERROR;
		$fieldInfo=mysql_fetch_field($this->resultset, $num);
		return $fieldInfo->type;
	}	
	public function getFieldInfo($num) {
		$this->error=ERROR_NO_ERROR;
		$fieldInfo=mysql_fetch_field($this->resultset, $num);
		return $fieldInfo;
	}

	public function getFieldTable($num) {
		$this->error=ERROR_NO_ERROR;
		$fieldInfo=mysql_fetch_field($this->resultset, $num);
		return $fieldInfo->column_source;
	}
	public function getFieldFlags($num){
		$this->error=ERROR_NO_ERROR;
		$flags = mysql_field_flags($this->resultset,$num);
		return $flags;
	}
	
	public function getFieldLength($num) {
		$this->error=ERROR_NO_ERROR;
		$fieldInfo=mysql_fetch_field($this->resultset, $num);
		return $fieldInfo->max_length;
	}

	public function getNumFields() {
		$this->error=ERROR_NO_ERROR;
		if(!$this->database->getOption('cacheActive') || $this->cache->hasExpired()){
			return mysql_num_fields($this->resultset);
		}else{
			return $this->cache->iFields;
		}
		
	}

	public function dataSeek($row) {
		$this->error=ERROR_NO_ERROR;
		return mysql_data_seek($this->resultset, $row);
	}

	public function fieldSeek($cols) {
		$this->error=ERROR_NO_ERROR;
		return mysql_field_seek($this->resultset, $cols);
	}

	public function fetchField($num) {
		$this->error=ERROR_NO_ERROR;
		return mysql_fetch_field($this->resultset, $num);
	}
	
}



?>