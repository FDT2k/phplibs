<?php
require_once('graphic/IMCoreImageAbstract.inc.php');
class IMCoreImageManager extends IMCoreObject {
	static $layers;
	static $tmpfiles;
	public static function createLayerFromFile($layer,$file){
		self::$layers[$layer] = IMCoreImageAbstract::factory($file);
		return self::getLayer($layer);
	}
	
	public static function createLayerFromBase64($layer,$datas){
		$datas = base64_decode($datas);
		$tmpFileName = tempnam('tmp','CHK_IMG');
		if($handle= fopen($tmpFileName,'w')){
			fwrite($handle,$datas,strlen($datas));
			fclose($handle);
		}		
		self::$tmpfiles[$layer] = $tmpFileName;
		self::createLayerFromFile($layer,$tmpFileName);
		return self::getLayer($layer);
	}

	public static function createEmptyLayer($layer,$width,$height){
		self::$layers[$layer] = IMCoreImageAbstract::create($width,$height);
		return self::getLayer($layer);
	}

	public static function createLayerFromLayer($layer,$lay){
		self::$layers[$layer] = clone $lay;
	}
	
	public static function clean(){
		foreach (self::$layers as $key => $value){
			self::$layers[$key]->clean();
			if(isset(self::$tmpfiles[$key])){
				unlink(self::$tmpfiles[$key]);
			}
			unset(self::$layers[$key]);
		}
	}

	public static function getLayer($layer){
		return self::$layers[$layer];
	}

	public static function mergeLayers($src, $dest, $dst_x=0,  $dst_y=0,  $src_x=0,  $src_y=0,  $src_w=-1,  $src_h=-1, $alpha=100){
		if($src_w == -1){
			$src_w = self::getLayer($src)->width;
		}
		if($src_h == -1){
			$src_h = self::getLayer($src)->height;
		}
//var_dump(self::GgetLayer($dest)->handle,  self::getLayer($src)->handle, $dst_x,$dst_y, $src_x,$src_y, $src_w, $src_h, $alpha);
		imagecopymerge ( self::getLayer($dest)->handle,  self::getLayer($src)->handle, $dst_x,$dst_y, $src_x,$src_y, $src_w, $src_h, $alpha);
	}

	public static function resizeLayer($layer,$width,$height,$keepProportions=true,$resample=true){
		if(empty($width) || !is_numeric($width)){
			$width=$height;
		}
		if(empty($height) || !is_numeric($height)){
			$height=$width;
		}
		if($keepProportions){
			$origWidth = self::getLayer($layer)->width;
			$origHeight = self::getLayer($layer)->height;
			if($origWidth > $origHeight){
				$newWidth = $width;
				$newHeight = intval($origHeight * ($width / $origWidth));
			}else{
				$newHeight = $height;
				$newWidth = intval($origWidth * ($height / $origHeight));
			}
		}else{
			$newWidth = $width;
			$newHeight = $height;
		}

		self::createEmptyLayer("imageCopyTemp",$newWidth,$newHeight);

		if(!$resample){
			imagecopyresized(self::getLayer('imageCopyTemp')->handle,self::getLayer($layer)->handle,0,0,0,0,$newWidth,$newHeight,self::getLayer($layer)->width,self::getLayer($layer)->height);
		}else{
			imagecopyresampled(self::getLayer('imageCopyTemp')->handle,self::getLayer($layer)->handle,0,0,0,0,$newWidth,$newHeight,self::getLayer($layer)->width,self::getLayer($layer)->height);
		}
	//	self::getLayer($layer)->clean();
//		self::createLayerFromLayer('tmp',self::getLayer('imageCopyTemp'));
		self::$layers['tmp'] = self::$layers['imageCopyTemp'];
		//self::getLayer('imageCopyTemp')->clean();
	//	unset(self::getLayer('imageCopyTemp'));
	}

}