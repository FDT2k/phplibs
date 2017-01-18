<?
namespace ICE\lib\database;

use \ICE\lib\helpers as helpers;
use \ICE\Env as Env;

class SQLCache extends \ICE\core\IObject{

	protected $sQueryID;
	protected $rowpointer;
	protected $rowset;
	protected $expired = true;
	protected $queries_by_table=array();
	protected $affected_tables=array();
	protected $iSubdir = 3;
	protected $bCached = false;
	
	function __construct($query=false){
		$this->setDefaultOptionGroup('database');
		$this->pageSize = 0;
		$this->currentPage=1;
		/*if(Env::getConfig()->get('sqlCachePathRelativeToSite')){
			if($site = Env::getConfig()->get('useCacheFromSite')){
				$this->sqlCachePath = Env::catPath(Env::getSiteFSPath($site),Env::getConfig()->get('cachePath'));
			}else{
				$this->sqlCachePath = Env::catPath(Env::getSiteFSPath(),Env::getConfig()->get('cachePath'));
			}
		}else{*/
			$this->sqlCachePath = Env::catPath(Env::getFSPath(),Env::getConfig('database')->get('cachePath'));
	//	}
		//var_dump($this->sqlCachePath);
	//	var_dump($this->sqlCachePath);
		
		if($query){
			$this->query = $query;
			$this->sQueryID = md5($query);
			for($i=0;$i < $this->iSubdir;$i++){
				$this->directory.=$this->sQueryID[$i]."/";
				/*if(!is_dir(SQL_CACHE_PATH."/".$dir)){
					echo SQL_CACHE_PATH."/".$dir."<br>";
					mkdir(SQL_CACHE_PATH."/".$dir);
					chmod(SQL_CACHE_PATH."/".$dir,0777);
				}*/
			}
			
			$this->load();
		}
	}
	public function setPageSize($int){
		if(is_numeric($int)){
			$this->pageSize = $int;
			$this->load();
			if($numrows = $this->getNumRows()){
				$this->pagesCount = ceil($numrows / $this->pageSize);
			}
		}
	}
	public function setCurrentPage($int){
		if(is_numeric($int)){
			$this->currentPage = $int;
			$this->load();
		}
	}
	public function hasExpired(){
		return $this->expired;
	}
	
	public function fetchArray($currentpage=0,$pagesize=0){
		if ($this->rowpointer < sizeof($this->rowset)){
			return $this->rowset[$this->rowpointer++];
		}
		return false;
	}
	
	public function fetchAssoc($currentpage=0,$pagesize=0){
		return $this->fetchArray();
	}
	
	public function fetchRow(){
		return $this->fetchArray();
	}
	
	public function getNumRows(){
		return $this->iNumRows;
	}
	
	protected function load(){
		$this->rowset = array();
		$this->rowpointer=0;
		$this->expired = true;
		if($this->pageSize > 0){
			$this->sCacheFile = $this->sqlCachePath."/".$this->directory.$this->sQueryID."-pagesize".intval($this->pageSize)."-".intval($this->currentPage).".php";
		}else{
			$this->sCacheFile = $this->sqlCachePath."/".$this->directory.$this->sQueryID.".php";
		}
	//	var_dump("loading ".$this->sCacheFile);
		if(file_exists($this->sCacheFile)){
			@include($this->sCacheFile);
			$this->bCached = true;
		}else{
			$this->bCached = false;
		}
		
		$this->loadTableQueryIndex();
		/*if($this->expired){
			$this->destroy();
		}*/
	}
	
	protected function loadTableQueryIndex(){
		//if(Env::getConfig()->get('cacheActivateIndex')){
			foreach ($this->affected_tables as $table){
				if(file_exists($this->sqlCachePath."/index/".$table.".php")){
					@include($this->sqlCachePath."/index/".$table.".php");
				}
			}
		//}
	}
	
	function append($row){
		$this->rowset[]= $row;
	}
	
	function save($iTimeToLive=0,$realnumrows=0){
		if($iTimeToLive != 0){
//			$iTimeToLive=10;
			//if(Env::getConfig()->get('cacheActivateIndex')){
				if(!is_dir($this->sqlCachePath."/index")){
					@mkdir($this->sqlCachePath."/index");
					@chmod($this->sqlCachePath."/index",0777);
				}
				$this->query_extract_tables();
			//}
			for($i=0;$i < $this->iSubdir;$i++){
				$dir.=$this->sQueryID[$i]."/";
				if(!is_dir($this->sqlCachePath."/".$dir)){
					@mkdir($this->sqlCachePath."/".$dir);
					@chmod($this->sqlCachePath."/".$dir,0777);
				}
			}
			if($handle = @fopen($this->sCacheFile,'w')){

				@flock($handle,LOCK_EX);
				$file = "<?php\n\n/* " . str_replace('*/', '*\/', $this->query) . " */\n";
				if($iTimeToLive < 0){ // infinite cache ? 
					$file .= "\n\$this->expired = false;\n";
				} else  {
					$file .= "\n\$this->expired = (time() > " . (time() + $iTimeToLive) . ") ? true : false;\nif (\$this->expired) { return false; }\n";
				}
				$file .= "\n\$this->iNumRows=".intval($realnumrows).";\n";
			//	$file .= "\n\$this->affected_tables = ".var_export($this->affected_tables, true).";\n";
				foreach ($this->affected_tables as $key => $value){
					$file .= "\n\$this->affected_tables[] = '".$value."';\n";
				}
			
				@fwrite($handle, $file . "\n\$this->rowset = " . var_export($this->rowset, true) . ";\n?>");
				chmod($this->sCacheFile,0777);
				@flock($handle,LOCK_UN);
				@fclose($handle);
			//	if(Env::getConfig()->get('cacheActivateIndex')){
					$this->update_queries_index();
				//}
			}
		}
	}
	
	function query_extract_tables(){
		$datas = preg_split("/[\n\s]+/",$this->query);
		$previous = "";
		foreach ($datas as $value){
			
			if($previous == "join" || $previous =="from" || $previous =="update" || $previous == "into"){
				$table= str_replace("`","",$value);
				$tables[]=$table;
				$this->append_affected_table($table);
			}
			$previous = strtolower($value);
		}
		return $tables;
	}
	
	function append_affected_table($table){
		if(!in_array($table,$this->affected_tables)){
			$this->affected_tables[]=$table;
		}
	}
	
	function update_queries_index(){
		foreach ($this->affected_tables as $table){
			
			$this->loadTableQueryIndex();
			if($handle = @fopen($this->sqlCachePath."/index/".$table.".php","w")){
				@flock($handle,LOCK_EX);
		//		var_dump($this->queries_by_table,$this->sQueryID,'-------');
				$this->queries_by_table[$table][$this->sQueryID]=$this->sCacheFile;
				$file = "<?php\n\n/* cache index for table ".$table.", last generated on ".date("d.m.Y H:i:s")." (".time().") */\n";
				@fwrite($handle, $file . "\n\$this->queries_by_table['".$table."'] = " . var_export($this->queries_by_table[$table], true) . ";\n?>");
				@flock($handle,LOCK_UN);
				fclose($handle);
			}
		}
	}
	
	function destroy(){
		if($this->bCached){
			unlink($this->sCacheFile);
		}
	}
	
	public function invalidate(){
	//var_dump('invalidate');
		if(Env::getConfig('database')->get('cacheActive') ){
	//	var_dump('invalidate');
			$this->query_extract_tables();
			$this->load();
			//var_dump($this->affected_tables);
			if(is_array($this->affected_tables)){
				foreach ($this->affected_tables as $table){
				//	var_dump($this->queries_by_table[$table]);
					if(is_array($this->queries_by_table[$table])){
						foreach ($this->queries_by_table[$table] as $query){
							//echo "invalidate ".$query."<br>";
							@unlink($query);
						}
						$this->queries_by_table[$table] = array();
					}
					$this->update_queries_index();
				}
			}
		}
	}
	
}

?>