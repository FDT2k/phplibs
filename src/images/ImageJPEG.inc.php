<?php
namespace ICE\lib\images;


class ImageJPEG extends ImageAbstract{
	function __construct($file=''){
		
		$this->type = IMAGETYPE_JPEG;
		parent::__construct($file);
		if(!empty($file)){
			$this->handle = imagecreatefromjpeg($file);
		}
	}

	function writeToFile($file){
		imagejpeg($this->handle,$file,$this->quality);
	}

	function draw(){
		imagejpeg($this->handle,'',$this->quality);
	}

	function base64(){
		ob_start();
		imagejpeg($this->handle,'',$this->quality);
		$sBuffer = ob_get_contents();
		ob_end_clean();
		return base64_encode($sBuffer);
	}
}