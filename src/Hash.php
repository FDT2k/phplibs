<?php
namespace FDT2k\Helpers;

class Hash{

	static function getDefaultKey(){
		$key ="blabla";
		return $key;
	}

	static function signAndURLEncode($msg,$key=''){
		if(empty($key)){
			$key = self::getDefaultKey();
		}
		$string = base64_encode($msg."--///--".md5($msg.$key));
		return $string;
	}

	static function decodeFromURL($hash,$key=''){
		if(empty($key)){
			$key = self::getDefaultKey();
		}
		list ($msg,$hash) = explode('--///--',base64_decode($hash));
//var_dump($msg,$hash);
		if(md5($msg.$key) == $hash){
			return $msg;
		}
		return false;
	}

	static function randomString($length = 10, $lowercase=true,$digits=true,$uppercase=true) {
		$characters = ($digits ? "123456789":"").($lowercase ? "abcdefghijklmnopqrstuvwxyz":"").($uppercase ? "ABCDEFGHIJKLMNOPQRSTUVWXYZ":"");
	//	var_dump($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, strlen($characters) - 1)];
		}
		return $randomString;
	}

}
