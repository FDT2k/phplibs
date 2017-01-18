<?php


namespace ICE\lib\helpers;

use \ICE\Env as Env;
use \ICE\lib\helpers\FileHelper as FileHelper;

class CachedWebserviceFetcher {
	static function fetch($url,$path='ws',$timeout=300){
		$cachePath = Env::getCachePath($path);
		
	#	$url = "http://pitre-iphone.tpg.ch/GetTousArrets.json";
		$file = md5($url);
		$content = "";
		if(!file_exists($cachePath."/".$file) || ( time() - filemtime($cachePath."/".$file))> $timeout || $timeout==0){
		#	echo "fetching content from ".$url."<br/>";
			$content = file_get_contents($url);
			FileHelper::writeTo($cachePath."/".$file,$content);
		}else{
		#	echo "fetching cached content from ".$url."<br/>";
			$content = file_get_contents($cachePath."/".$file);
		}
		return $content;
	}

}