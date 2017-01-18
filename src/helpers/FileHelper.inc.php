<?php

namespace ICE\lib\helpers;

class FileHelper {

	static $fileCopyDelegate=null;



	static function writeTo($file,$content){
		if($handle = fopen($file,'w+')){
			fwrite($handle,$content);

			fclose($handle);

			chmod($file,0777);
		}
	}

	static function copy_directory($src,$dst,$copy_hidden=false,$callback='') {
		$dir = opendir($src);
		if(!is_dir($dst)){
			@mkdir($dst);
		}
		//var_dump($dst);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' ) && ($file[0]!='.' || $copy_hidden)) {
				if ( is_dir($src . '/' . $file) ) {
					self::copy_directory($src . '/' . $file,$dst . '/' . $file,$copy_hidden,$callback);
				}
				else {
					copy($src . '/' . $file,$dst . '/' . $file);
					//var_dump(self::$fileCopyDelegate);
					if(!empty($callback)){
						$callback($src . '/' . $file,$dst . '/' . $file);
					}
					//var_dump($src . '/' . $file);
					//var_dump('=>'.$dst . '/' . $file);
				}
			}
		}
		closedir($dir);
	}

	static function read_directory_event($directory,$event){
		if($handle = opendir($directory)){
			while($file = readdir($handle)){
				if(( $file != '.' ) && ( $file != '..' )){
					if($event){
						$event($file,$directory."/".$file);
					}
				}
			}

			closedir($handle);
		}

	}

	static function copy_directory_replace($src,$dst,$copy_hidden=false,$replaceContentCall,$chmod=0777) {
		$dir = opendir($src);
		@mkdir($dst);
		while(false !== ( $file = readdir($dir)) ) {
			if (( $file != '.' ) && ( $file != '..' ) && ($file[0]!='.' || $copy_hidden)) {

				$completeFilePath=$src . '/' . $file;

				if ( is_dir($src . '/' . $file) ) {
					self::copy_directory_replace($src . '/' . $file,$dst . '/' . $file,$copy_hidden,$replaceContentCall);
				}
				else {
					$content = file_get_contents($completeFilePath);

					if($replaceContentCall){
						$content = $replaceContentCall($completeFilePath,$content);
					}
					if($handle = fopen($dst.'/'.$file, 'w+')){
						fwrite($handle, $content);
						fclose($handle);
						chmod($completeFilePath,$chmod);
					}
					//copy($src . '/' . $file,$dst . '/' . $file);
				}
			}
		}
		closedir($dir);
	}



}
