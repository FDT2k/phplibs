<?php
namespace ICE\lib\helpers;

use \ICE\Env as Env;
class Formatter{
	static function formatDate($date){
		$format=Env::getConfig('formats')->get('dateFormatTransform');
		if(preg_match('/'.Env::getConfig('formats')->get('dateFormatSourceCheck').'/i',$date)){
			sscanf($date, $format, $day, $month, $year);
			//var_dump($format, $day, $month, $year);
			$date = sprintf("%4d-%02d-%02d", $year,$month,$day);
			//var_dump($date);
		} 
		return $date;			

	}

	static function formatUTCDate($date,$sourceFormat="%4d-%2d-%2d",$destFormat="%4d-%02d-%02d"){
		$format=$sourceFormat;
		if(preg_match('/'.Env::getConfig('formats')->get('dateFormatSourceCheck').'/i',$date)){
			sscanf($date, $format, $day, $month, $year);
			//var_dump($format, $day, $month, $year);
			$date = sprintf($destFormat, $year,$month,$day);
			//var_dump($date);
		} 
		return $date;
	}

	static function timeStringToInt($time){
		$format=Env::getConfig('formats')->get('timeFormatTransform');
			sscanf($time, $format, $h, $m, $s);
			//var_dump($format, $h, $m, $s);
			return ($h*60*60) + ($m *60) + $s;
			//$date = sprintf("%4d-%02d-%02d", $year,$month,$day);
			//var_dump($date);
		

	}

}