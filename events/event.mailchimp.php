<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Error</h2><p>You cannot directly access this file</p>');

	include_once(EXTENSIONS . '/mailchimp/lib/class.mcapi.php');

	Class eventMailchimp extends Event
	{		
		protected $_driver = null;
		
		public function __construct(&$parent, $env = null) {
			parent::__construct($parent, $env);
			
			$this->_driver = Frontend::instance()->ExtensionManager->create('mailchimp');
		}
		
		public static function about()
		{								
			return array(
						 'name' => 'MailChimp',
						 'author' => array('name' => 'Mark Lewis',
										   'website' => 'http://www.casadelewis.com',
										   'email' => 'mark@casadelewis.com'),
						 'version' => '1.1',
						 'release-date' => '2011-03-10',
						 'trigger-condition' => 'action[subscribe]'
						 );						 
		}
				
		public function load()
		{	
			if(isset($_POST['action']['signup']))
				return $this->__trigger();
		}

		public static function documentation()
		{
			return new XMLElement('p', 'Subscribes user to a MailChimp list.');
		}
		
		protected function __trigger()
		{			
			$email = $_POST['email'];
			
			$merge = $_POST['merge'];

			$result = new XMLElement("mailchimp");			
			
			$api = new MCAPI($this->_driver->getUser(), $this->_driver->getPass());

			$cookies = new XMLElement("cookies");	
			foreach($merge as $key => $val)
			{
				if(!empty($val))
				{
				$cookie = new XMLElement('cookie', $val);
				$cookie->setAttribute("handle", $key);		

				$cookies->appendChild($cookie);
				}
			}
			
			$cookie = new XMLElement('cookie', $email);
			$cookie->setAttribute("handle", 'email');		

			$cookies->appendChild($cookie);
							
			$result->appendChild($cookies);
			
			$mergeVars = $api->listMergeVars($this->_driver->getList());
			
			if(!ereg('^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*\.(([0-9]{1,3})|([a-zA-Z]{2,3})|(aero|coop|info|museum|name))$', $email))
			{
				$error = new XMLElement('error', 'E-mail is invalid.');
				$error->setAttribute("handle", 'email');		

				$result->appendChild($error);
				$result->setAttribute("result", "error");		
				
				return $result;
			}	
																		
			if(!$api->listSubscribe($this->_driver->getList(), $email, $merge))
			{
				$result->setAttribute("result", "error");
				
				foreach($mergeVars as $var) {
					
					$errorMessage = str_replace($var['tag'], $var['name'], $api->errorMessage, $count);
				    if($count == 1) {
						$error = new XMLElement("error", $errorMessage);	
						break;								
					} else {
						$error = new XMLElement("error", $api->errorMessage);									
					}
				}
				$result->appendChild($error);
			}
			else
			{
				$result->setAttribute("result", "success");	
			}
			
			return $result;
		}		
	}

?>