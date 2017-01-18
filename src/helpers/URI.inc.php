<?php
namespace FDT2k\Helpers;

class URI{

	public $username = "";
	public $password = "";
	public $hostname = "";
	public $path = array();
	public $protocol = "";
	public $query = array();
	public $separator = "/";
	public $baseurl ="";

	function __construct($uri=''){
		//var_dump($uri);
		if(empty($uri)){
			$uri = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
		}

		if($uri[strlen($uri)-1] == '/'){//if the uri terminate with a / we remove it. We don't care.
			$uri = substr($uri,0,strlen($uri)-1);
		}
		$this->path=array();
		$this->uri = $uri;
		//var_dump($this->uri);
		$this->parse($this->uri);
	}

	public function getWebURL(){
		return $this->protocol."://".$this->hostname;
	}

	protected function parse($uri){
		/*
	possible outcome
	protocol://user:password@host:port/path1/path2?query1=query2
	protocol://user@host:port/path1/path2
	protocol://host:port/path1/path2
	protocol://host/path1
		*/
	//preparse url


		preg_match_all('/(?<protocol>(?:[^:]+)s?)?:\/\/(?:(?<user>[^:\n\r]+):(?<pass>[^@\n\r]+)@)?(?<host>(?:www\.)?(?:[^:\/\n\r]+))(?::(?<port>\d+))?\/?(?<request>[^?#\n\r]+)?\??(?<query>[^#\n\r]*)?\#?(?<anchor>[^\n\r]*)?/mx', $uri, $matches);

		//var_dump($matches);

		$this->username = $matches['user'][0];
		$this->password = $matches['pass'][0];
		$this->hostname = $matches['host'][0];
		$this->port = $matches['port'][0];
		#var_dump($matches);
		$this->baseurl=  $matches['request'][0];
		$this->path = explode('/',$this->baseurl);

		if($this->baseurl==''){
			$this->baseurl = '/';
		}

		$this->protocol = urldecode($matches['protocol'][0]);
		return $this;
	}

	function absolutePath(){
		return "/".implode('/',$this->path);
	}

	protected function addQueryVariable($variable,$value){
		$this->query[$variable]= $value;
	}


	function pathAsString(){
		return implode('/',$this->path);
	}
}
