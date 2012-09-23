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

  /**

   */
  class Search extends Base {
    protected function before() {
      if (REQUEST_GET) {
        header('Location: /');
      }
    }
    /**

     */
    protected function index() {
      $type = '\\' . $this->Input->request('type');
      if (REQUEST_AJAX) {
        if (isset($_GET['term'])) {
          $data = $type::search($_GET['term']);
          $this->JS->renderJSON($data);
        }
      }
    }
  }
