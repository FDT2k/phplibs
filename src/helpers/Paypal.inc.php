<?php
namespace ICE\lib\helpers;

class Paypal extends \ICE\core\IObject{
	protected $language="fr";
	protected $charset="utf8";
	function __construct($id,$token='',$env='dev'){
		$this->id = $id;
		$this->env = $env;
		$this->transferToken = $token;
		if($env == 'prod'){
			$this->url = 'https://www.paypal.com/cgi-bin/webscr';
		}else{
			
			$this->url = 'https://www.paypal.com/cgi-bin/webscr';
		}
		$this->buttonText="PAYER AVEC PAYPAL";
	}
	function setButtonText($text){
		$this->buttonText = $text;
		return $this;
	}

	function pay ($orderID,$amount,$currency,$orderDesc=""){
		$query['business'] = $this->id;
		$query['currency_code'] = $currency;
		$query['lc'] = $this->language;
		$query['charset'] = $this->charset;
		$query['custom'] = $orderID;
		$query['cmd'] = '_xclick';
		if($this->env == 'dev'){
			$query['amount'] = '0.10'	;	
		}else{
			$query['amount'] = str_replace(',','.',$amount);		
		
		}
		
		$query['no_shipping'] = 1;		
		
		$query['item_name'] = $orderDesc;
		//$query = $this->generateSignedQuery();
		$result = '<form method="post" action="'.$this->url.'" name="paypal">';
		foreach($query as $k => $value){
			$result .= "<input type='hidden' name='".$k."' value='".$value."'/>";
		}
		$result .= '<input type="submit" value="'.$this->buttonText.'"/></form>';
		return $result;
	}
	
	function checkIPN($array,$transaction=array()){
		$paypal_response = $this->postback($_POST);
		var_dump($paypal_response);
		if($paypal_response != "VERIFIED"){
			//problem
	///		var_dump('response :'.$paypal_response);
			$this->error='response :'.$paypal_response;
			return false;
		}

		if(($stamp = $_POST["custom"]) == ''){
		//	var_dump('no custom');
			//no custom
			$this->error='no custom';
			return false;
		}


		$amount = $_POST['mc_gross'];
		$state_info = "";
		if(count($transaction)>0){
			if(isset($transaction['mc_gross']) && $amount != $transaction['mc_gross']){
				$state = "error";
				$state_info = "Amount do not match";
			}
			else if(isset($transaction['mc_currency']) && $_POST['mc_currency'] != $transaction['mc_currency']){
				$state = "error";
				$state_info = "Currency do not match";
			}
		}
		
		if(strtolower($_POST["payment_status"]) != 'completed'){
			$state = "error";
			$state_info = "Payment status returned: ".$_POST["payment_status"];
		}
		else{
			$state = "ok";
		}

		$this->error = $state_info;
		if($state == 'ok'){
			return true;
		}
		return false;
	}
	
	function checkTransaction($array){
		if(!($tx = $array['tx'])){
			return false;
		}

	/*	$this->transaction = $this->db->toArray("Select * from twist_billing_transactions where provider_trx_id = :tx OR stamp = :tx", array(
			"tx" => $tx 
		));

		if(!empty($this->transaction)){
			return;
		}
*/
	//	var_dump($array);
	//	var_dump($this->transferToken);
		$post = "cmd=_notify-synch&tx=".$tx."&at=".$this->transferToken."&charset=".$this->charset;
//var_dump( $this->url);
//var_dump($array);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this->url);
		curl_setopt ($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);

		$result = curl_exec($ch);

		curl_close($ch);
//	var_dump($result);
		$lines = preg_split("((\r)*\n)", $result);
//		var_dump($lines);
		if(stristr($lines[0], "success") === false){
			return false;
		}

		$tx_data = array();
		foreach($lines as $line){
			$tmp = explode("=", $line);
			if(!is_array($tmp) || count($tmp) != 2)
				continue;

			$tx_data[$tmp[0]] = urldecode($tmp[1]);
		}

		$stamp =$tx_data['custom'];

		//Mandatory! 
		/*$this->transaction = $this->db->toArray("Select * from twist_billing_transactions where stamp = :stamp", array(
			"stamp" => $stamp
		));*/
		if($stamp){
			return true;
		}

		return false;
	}
	
	function postback(array $params)
	{
		//$url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
		$url = $this->url;
	
		$post_data = "cmd=_notify-validate";
		foreach ($params as $name => $value)
			$post_data .= "&".$name."=".urlencode($value);
	
		$curl = curl_init( $url );
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		//curl_setopt($curl, CURLOPT_CAINFO, _SITE_ROOT_ . "var/certs/api_cert_chain.crt");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
	
		$paypalResponse = curl_exec($curl);
		
		$err = curl_error($curl);
		
		curl_close($curl);
	
		if ($err) throw new Exception($err);
		
		return $paypalResponse;
	}

}