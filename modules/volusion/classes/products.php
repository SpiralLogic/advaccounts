<?php
  namespace Modules\Volusion;
  /**

   */
  class Products {

    public $products = array();

    /**

     */
    function __construct() {
    }

    /**
     * @return bool
     */
    function get() {
      $productsXML = $this->getXML();
      if (!$productsXML) {
        return FALSE;
      }
      $this->products = \XMLParser::XMLtoArray($productsXML);
      return TRUE;
    }

    /**
     * @return string
     */
    function getXML() {
      $apiuser = \Config::get('modules.webstore')['apiuser'];
      $apikey = \Config::get('modules.webstore')['apikey'];
      $url = \Config::get('modules.webstore')['apiurl'];
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
