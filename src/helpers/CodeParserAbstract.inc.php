<?php
namespace ICE\lib\helpers;	

define('PARSER_NONE',0);
define('PARSER_IN_TAG',1);
define('PARSER_IN_ENDTAG',2);
define('PARSER_IN_ATTRIBUTE',4);
define('PARSER_IN_DATA',8);
define('PARSER_IN_ATTRIBUTE_NAME',16);
define('PARSER_IN_ATTRIBUTE_VALUE',32);

define('PARSER_IN_ATTRIBUTE_VALUE_DATA',64);
 	  
abstract class CodeParserAbstract {
	public $tagPrefix= '[';
	public $tagSuffix= ']';
	public $debug = false;
	
	function __construct($code=''){
		
		
	}
	
	
	public function parse($buffer){
	/*	$currentState;
		char currentChar,previousChar,nextChar,openedWith;
		bool bAttributeValueStarted = false;
		bool bMoreCommentCheck = false;
		JString tagName,endtagName,attributeName,attributeValue,currentDatas;
	*/
		$currentState = PARSER_NONE;
		$currentChar = '';
		$previousChar = '';
		$nextChar = '';
		$openedWith = '';
		$bAttributeValueStarted = '';
		$bMoreCommentCheck = '';
		//mb_internal_encoding('utf8');
		for($i = 0; $i <strlen($buffer);$i++){
	
			$previousChar = $currentChar;
			$currentChar = $buffer[$i];
		//	$currentChar = substr($buffer,$i,1);
			if($this->debug)
			var_dump($currentChar);
		
			switch($currentState){
				case PARSER_IN_TAG:
				if($this->debug)
					var_dump('in_tag');
					if($currentChar != $this->tagSuffix && $currentChar!=' '){
						if($currentChar == '/'){ // this is a end tag switching to end-tag mode
							$currentState = PARSER_IN_ENDTAG;
						}else{  
							$tagName .= $currentChar;
						}	
							
					}else if($currentChar==$this->tagSuffix){ // end of tag calling ontag event;
						//var_dump($tagName);
		
						$this->onTag($tagName);
						$tagName = "";
						$currentState = PARSER_IN_DATA;
						$this->onTagEnd();
					}else if($currentChar ==' '){ // attribute may be present switching
						//var_dump($tagName);
						$this->onTag($tagName);
						$tagName = "";
						$currentState = PARSER_IN_ATTRIBUTE_NAME;
					}else{
					 // FDK_logger::log("parsing error don't know what to do with this %s",tagName.print()); 
					}
				break;
				
				case PARSER_IN_ATTRIBUTE_NAME:
					if($this->debug)
					var_dump('in_attribute_name');
					$attributeValue="";
					if($currentChar != '=' && $currentChar != $this->tagSuffix &&$currentChar!=' '){
						$attributeName .= $currentChar;
					}else if( $currentChar == $this->tagSuffix){ // end of tag then no value !! yeap calling on attribute event
						if($attributeName != ""){
							$this->onAttribute($attributeName,$attributeValue);
						}
						$attributeName="";
						$attributeValue="";
						$currentState = PARSER_IN_DATA;
					}else if($currentChar == '=') { // ok there is a value in this attribute !! setting to on attribute value;
						$currentState = PARSER_IN_ATTRIBUTE_VALUE;
					}else{ // parse error where am i?
					
					}
				break;    	
				
				case PARSER_IN_ATTRIBUTE_VALUE:
				if($this->debug)
				var_dump('in_attribute_value');
				//	var_dump($currentChar);
				/*	if($currentChar !=' ' && $currentChar != $this->tagSuffix){ // 
						if($currentChar!='\'' && $currentChar !='"'){
							$attributeValue.=$currentChar;
						}		
					}else if ($currentChar == ' '){ // end of attribute return in ATTRIBUTE name mode 
						$this->onAttribute($attributeName,$attributeValue);
						$attributeName ="";
						$attributeValue="";
						$currentState = PARSER_IN_ATTRIBUTE_NAME;
					}else if ($currentChar == $this->tagSuffix){ // we found the suffix 
						$this->onAttribute($attributeName,$attributeValue);
						$attributeName ="";
						$attributeValue="";
						$currentState = PARSER_IN_DATA;
						$this->onTagEnd();
					}
				*/
					if($currentChar == '"' || $currentChar =="'"){
						$attributeOpenedWith = $currentChar;
						$currentState=PARSER_IN_ATTRIBUTE_VALUE_DATA;
					}
					
				break;
				case PARSER_IN_ATTRIBUTE_VALUE_DATA:
				if($this->debug)
				var_dump('in_attribute_value_data');
					if( $currentChar != $this->tagSuffix && $currentChar!=$attributeOpenedWith){ // 
					//	echo($currentChar);
						$attributeValue.=$currentChar;
					}else if($currentChar ==$attributeOpenedWith){
						$this->onAttribute($attributeName,$attributeValue);
						$attributeName ="";
						$attributeValue="";
						$currentState = PARSER_IN_ATTRIBUTE_NAME;
					}else if($currentChar == $this->tagSuffix){
						$this->onAttribute($attributeName,$attributeValue);
						$attributeName ="";
						$attributeValue="";
						$currentState = PARSER_IN_DATA;
						$this->onTagEnd();
					}
				break;
				case PARSER_IN_ENDTAG:
				if($this->debug)
				var_dump('in_end_tag');
					if($currentChar !=$this->tagSuffix){
						$endtagName .= $currentChar;
					}else{
						$tagName="";
						$this->onEndTag($endtagName);
						$endtagName="";
						$currentState = PARSER_IN_DATA;
					}
				break;
				default:
					if($this->debug)
					var_dump('in_default');
					//    		FDK_logger::log("%c",currentChar);
					if($currentChar == $this->tagPrefix){ // tagstart
						$currentState = PARSER_IN_TAG;
						if(!empty($datas)){
							$this->onData($datas);
							$datas = "";
						}
					}else{
						$datas .=$currentChar;
					}
				break; 
			}
			
		}
		
		if($datas){
			$this->onData($datas);
		}
	
	}
	
	abstract public function onTag($tag);
	abstract public function onEndTag($tag);
	abstract public function onAttribute($name, $value);
	abstract public function onData($datas);
	abstract public function onTagEnd();
	abstract public function onTagStart();
	
}