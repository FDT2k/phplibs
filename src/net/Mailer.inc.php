<?php
namespace ICE\lib\net;
use \ICE\Env as Env;

class Mailer extends \ICE\lib\net\PHPMailer
{
	
	public function __construct($exceptions = false) {
		parent::__construct($exceptions);
		$this->IsSMTP();
		$this->Host          = Env::getConfig('mail')->get('host');
		$this->SMTPAuth      = true;                  // enable SMTP authentication
		$this->SMTPKeepAlive = true;                  // SMTP connection will not close after each email sent
		$this->Port          = Env::getConfig('mail')->get('port');                    // set the SMTP port for the GMAIL server
		$this->Username      = Env::getConfig('mail')->get('username'); // SMTP account username
		$this->Password      = Env::getConfig('mail')->get('password');        // SMTP account password
		$this->IsSMTP();
		$this->CharSet = Env::getConfig('mail')->get('charset');
		$this->IsHTML(false);
		$this->Subject = Env::getConfig('mail')->get('defaultSubject');
		$this->From= Env::getConfig('mail')->get('from');
		$this->SMTPDebug  = 1;

		$this->FromName= Env::getConfig('mail')->get('fromName');
		
	}
	
	
	

}