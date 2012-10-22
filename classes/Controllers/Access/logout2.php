<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  namespace ADV\Controllers\Access;

  use ADV\Core\View;

  /**
   *
   */
  class Logout extends \ADV\App\Controller\Base
  {
    protected function index() {
      $this->Page->start('Logout', SA_OPEN, true);
      (new View('logout'))->render();
      $this->Page->end(true);
    }
    public function run() {
      $this->index();
    }
    protected function after() {
      $this->User->_logout();
    }
  }
