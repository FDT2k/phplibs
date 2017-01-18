<?php
require_once('graphic/IMCoreImageAbstract.inc.php');
class IMCoreImageTGA extends IMCoreImageAbstract{
	function __construct($file=''){
		if(!empty($file)){
//			$avInfos = getimagesize($file);
			
			$this->type = IMAGETYPE_TGA;
			$this->handle = imagecreatefromtga($file);
			
			$this->width = imagesx($this->handle);
			$this->height = imagesy($this->handle);
		}
	}








}