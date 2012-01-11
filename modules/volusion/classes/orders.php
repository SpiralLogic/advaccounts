<?php
	namespace Modules\Volusion;
	class Orders
	{
		protected $orders;
		function __construct() {
			$this->get();
		}
		function getXML() {
			$apiuser = \Config::get('webstore.apiuser');
			$apikey = \Config::get('webstore.apikey');
			$url = \Config::get('webstore.apiurl');
			$url .= "Login=" . $apiuser;
			$url .= '&EncryptedPassword=' . $apikey;
			$url .= '&EDI_Name=Generic\Orders';
			$url .= '&SELECT_Columns=*&LIMIT=1';
			return file_get_contents($url);
		}
		function store() {
			$_SESSION['weborders'] = $this->orders;
		}
		function get() {
			$XML = $this->getXML();
			if (!$XML) {
				return false;
			}
			$this->orders = \XMLParser::XMLtoArray($XML);
		}
	}
