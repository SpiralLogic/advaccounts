<?php
  use ADV\App\User;
  use ADV\App\Page;
  use ADV\Core\View;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_("Logout"), SA_OPEN, true);
  (new View('logout'))->render();
  User::logout();
  Page::end(true);

