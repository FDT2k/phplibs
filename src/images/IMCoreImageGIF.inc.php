<?php
require_once('graphic/IMCoreImageAbstract.inc.php');
class IMCoreImageGIF extends IMCoreImageAbstract{
	
	function __construct($file=''){
		$this->type = IMAGETYPE_GIF;
		parent::__construct($file);
		$this->handle = imagecreatefromgif($file);
	}


	function writeToFile($file){
		imagegif($this->handle,$file);
	}

	function draw(){
//		imagealphablending($this->handle, false);
		imagesavealpha($this->handle, true);
		imagegif($this->handle);
	}

	function base64(){
		ob_start();
		imagegif($this->handle);
		$sBuffer = ob_get_contents();
		ob_end_clean();
		return base64_encode($sBuffer);
	}

}