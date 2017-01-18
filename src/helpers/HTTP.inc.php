<?php

namespace ICE\lib\helpers;

	class HTTP {

		function do404Unless($arg){
			if(!$arg){
				header("Status : 404 Not Found");
				header("HTTP/1.0 404 Not Found");
				die();
			}
		}



		static function post($url, $postdata, $files = null)
		{

			$ch = \curl_init();
			\curl_setopt($ch, CURLOPT_HEADER, 0);
			\curl_setopt($ch, CURLOPT_VERBOSE, 0);
			\curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			\curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
			\curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                                            'application/json; charset=utf-8'
                                            ));
			\curl_setopt($ch, CURLOPT_URL, $url);
			\curl_setopt($ch, CURLOPT_POST, true);
			// same as <input type="file" name="file_box">
			\curl_setopt($ch, CURLOPT_VERBOSE, true);
			\curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
			$response = \curl_exec($ch);
			return $response;
		}

		function get($url,$headers=array())
		{

			$c = curl_init();
/*On indique à curl quelle url on souhaite télécharger*/
curl_setopt($c, CURLOPT_URL, $url);
/*On indique à curl de nous retourner le contenu de la requête plutôt que de l'afficher*/
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
/*On indique à curl de ne pas retourner les headers http de la réponse dans la chaine de retour*/
curl_setopt($c, CURLOPT_HEADER, false);
curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
curl_setopt($c,CURLOPT_SSL_VERIFYPEER, false);
/*On execute la requete*/
//$output = curl_exec($c);
			//curl_setopt($ch, CURLOPT_GET, true);
			if (is_array($headers)){
				curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
			}
			$response = curl_exec($c);
			return $response;
		}
	}
