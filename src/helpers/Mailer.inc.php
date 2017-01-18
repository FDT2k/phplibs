<?php
namespace ICE\lib\helpers;

class Mailer {

	function send($author, $address, $subject, $content){

		return self::sendEmail($author,$address,$subject,$content);
	}		

	function sendEmail($author, $address, $subject, $content)
	{
		$headers  = "MIME-Version: 1.0\r\n";
		$headers .= "Content-type: text/plain; charset=utf-8\r\n";

		return mail($address,$subject, $content, $headers."From: ".$author."\r\n",'-f'.$author);
	}

}