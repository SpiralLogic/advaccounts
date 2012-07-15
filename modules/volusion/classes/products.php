<?php
  namespace Modules\Volusion;
  /**

   */
  class Products
  {
    /**
     * @var array
     */
    public $products = array();
    public function __construct() {
    }
    /**
     * @return bool
     */
    public function get() {
      $productsXML = $this->getXML();
      if (!$productsXML) {
        return false;
      }
      $this->products = \XMLParser::XMLtoArray($productsXML);

      return true;
    }
    /**
     * @return string
     */
    public function getXML() {
      $apiuser = \Config::get('modules.webstore')['apiuser'];
      $apikey  = \Config::get('modules.webstore')['apikey'];
      $url     = \Config::get('modules.webstore')['apiurl'];
      $url .= "Login=" . $apiuser;
      $url .= '&EncryptedPassword=' . $apikey;
      $url .= '&EDI_Name=Generic\Products';
      $url .= '&SELECT_Columns=*&LIMIT=1';
      if (!$result = file_get_contents($url)) {
        \Event::warning('Could not retrieve web products');
      }
      ;

      return $result;
    }
  }
