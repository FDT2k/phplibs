<?php
require_once('graphic/IMCoreImageAbstract.inc.php');
class IMCoreImagePNG extends IMCoreImageAbstract{
	
	function __construct($file=''){
		$this->type = IMAGETYPE_PNG;
		parent::__construct($file);
		if(!empty($file)){
		$this->handle = imagecreatefrompng($file);
		}
	}

	function writeToFile($file){
		imagealphablending($this->handle, false);
		imagesavealpha($this->handle, true);
		imagepng($this->handle,$file);
	}

	function draw(){
		imagealphablending($this->handle, false);
		imagesavealpha($this->handle, true);
		imagepng($this->handle);
	}

	function base64(){
		ob_start();
		imagepng($this->handle);
		$sBuffer = ob_get_contents();
		ob_end_clean();
		return base64_encode($sBuffer);
	}
	function getColorAlpha($r,$g,$b,$a){
		return imagecolorallocatealpha($this->handle,$r,$g,$b,$a);
	}




}