<?php
	namespace Modules\Volusion;
	class Products {
		public $products = array();

		function __construct() {
		}

		function get() {
			$productsXML = $this->getXML();
			if (!$productsXML) {
				return false;
			}
			$this->products = \XMLParser::XMLtoArray($productsXML);
			return true;
		}

		function getXML() {
			$apiuser = \Config::get('webstore.apiuser');
			$apikey = \Config::get('webstore.apikey');
			$url = \Config::get('webstore.apiurl');
			$url .= "Login=" . $apiuser;
			$url .= '&EncryptedPassword=' . $apikey;
			$url .= '&EDI_Name=Generic\Products';
			$url .= '&SELECT_Columns=*&LIMIT=1';
			if (!$result = file_get_contents($url)) {
							\Event::warning('Could not retrieve web products');
						};
						return $result;		}
	}
