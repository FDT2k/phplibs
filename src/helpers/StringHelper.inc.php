<?php
namespace ICE\lib\helpers;

class StringHelper{

	static function parseMYSQLCommentString($string){
		if(strpos($string,':') === false){ // old system support
			switch ($string){
				case 'upload':
				case 'filemanager':
				case 'html':
				case 'password':
				case 'email':
					$this->type = $string;
				break;
			}	
		} else { // new system support
			/*$datas = explode(':',$string);
			if(is_array($datas)){
				foreach($datas as $key){
					list($k,$v)=explode('=',$key);
					$arr[$k] = $v;
				}
			}*/
			
			//new support of quoted strings			
			define('NORMAL',0);
			define('IN_NAME',1);
			define('IN_VALUE',2);
			define('_END',4);
			define('_START_VALUE',8);
			$status = IN_NAME;
			$bQuotedStringStarted= false;
			
			$arr = array();
			for($i = 0; $i < strlen($string);$i++){
				$c = $string[$i];
				$next= $string[$i+1];
				$prev= $string[$i-1];
			//	var_dump($c,$status);
				switch($status){
					case IN_NAME:
						if($next == ':'){
							$status = _END;
						}else if($next == '='){
							$status = _START_VALUE;
						}
						$name .=$c;
					break;
					case _START_VALUE:
						if($next=='"' && !$bQuotedStringStarted){
							$bQuotedStringStarted= true;
						}/*else if($c=='"' && $bQuotedStringStarted){
							$bQuotedStringStarted = false;
						}*/
						//var_dump($bQuotedStringStarted);	
						$status = IN_VALUE;
					break;
					case IN_VALUE:
						//var_dump($next);
						/*if($next=='"' && !$bQuotedStringStarted){
							$bQuotedStringStarted= true;
						}else*/ if($next=='"' && $bQuotedStringStarted){
							$bQuotedStringStarted = false;
						}else if($next==':' && !$bQuotedStringStarted){
							$status = _END;
						}else if($next ==''){
							$status = _END;
						}
						
						if(/*$c!='=' &&*/ $c!='"'){
							$value .=$c;
						}
						
					break;
					case _END:
					//var_dump($name,$value);

					//var_dump(isset($arr[$name]),is_array($arr[$name]),$arr[$name]);
						if(isset($arr[$name]) && !is_array($arr[$name])){
							$arr[$name] = array($arr[$name]);
							$arr[$name][] = $value;
						}else if(!isset($$arr[$name])){
							$arr[$name]=$value;
						}else if( is_array($arr[$name]) ){
							$arr[$name][] = $value;
						}

						$name="";
						$value="";
						$status = IN_NAME;
					break;
				}
			
			}

		}

		return $arr;
	}
	
	
	static function noAccents($texte){
  		return str_replace( array('à','á','â','ã','ä', 'ç', 'è','é','ê','ë', 'ì','í','î','ï', 'ñ', 'ò','ó','ô','õ','ö', 'ù','ú','û','ü', 'ý','ÿ', 'À','Á','Â','Ã','Ä', 'Ç', 'È','É','Ê','Ë', 'Ì','Í','Î','Ï', 'Ñ', 'Ò','Ó','Ô','Õ','Ö', 'Ù','Ú','Û','Ü', 'Ý'), array('a','a','a','a','a', 'c', 'e','e','e','e', 'i','i','i','i', 'n', 'o','o','o','o','o', 'u','u','u','u', 'y','y', 'A','A','A','A','A', 'C', 'E','E','E','E', 'I','I','I','I', 'N', 'O','O','O','O','O', 'U','U','U','U', 'Y'), $texte);
	}

	
	
	static function slugify($string){
		$regex = '/[^a-z0-9\.]/i';
		$replace = '_';
		$string = self::noAccents($string);
		return preg_replace($regex,$replace,$string);
	}
	
	static function pathExtension($string){
	//var_dump($string);
		return substr($string,strrpos($string,'.')+1);
	}
	
	
	
	static function php2json($a) {
	  if (is_null($a)) {
	  	return 'null';
	  }

	  if ($a===false) {
	  	return 'false';
	  }

	  if ($a===true) {
	  	return 'true';
	  }

	  if (is_scalar($a)) {
	      $a=addslashes($a);
	      $a=str_replace("\n", '\n', $a);
	      $a=str_replace("\r", '\r', $a);
	      $a=preg_replace('{(</)(script)}i', "$1'+'$2", $a);
	      return "'$a'";
	  }

	  $isList=true;

	  for ($i=0, reset($a); $i<count($a); $i++, next($a)) {
	      if (key($a)!==$i) {
	      	$isList=false;
	      	break;
	      }
	  }

	  $result=array();

	  if ($isList) {
	      foreach ($a as $v) {
	      	$result[]=self::php2json($v);
	      }

	      return '[ ' . join(', ', $result) . ' ]';

	  } else {
	      foreach ($a as $k=>$v) {
	          $result[]=self::php2json($k) . ': ' . self::php2json($v);
	      }

	      return '{ ' . join(', ', $result) . ' }';
	  }
	}
	
	static function isEmpty($val) {
    $val = trim($val);
    return empty($val) && $val !== 0 && $val !=="0";
	}
}