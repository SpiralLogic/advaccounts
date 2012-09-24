<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @date      20/09/12
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers;
  use ADV\App\Controller\Base;
  use ADV\App\Item\Item;

  /**

   */
  class Search extends Base
  {

    protected function before() {
      if (!REQUEST_AJAX) {
        header('Location: /');
      }
    }
    /**

     */
    protected function index() {
      $type=  $this->Input->request('type');
      $searchclass = '\\' . $type;
      if (isset($_GET['term'])) {
        $uniqueID = $this->Input->get('UniqueID');
        if ($uniqueID) {
          $data = Item::searchOrder($_GET['term'], $uniqueID);
        } else {
          $data = $searchclass::search($_GET['term']);
        }
        $this->JS->renderJSON($data);
      }
    }
  }

