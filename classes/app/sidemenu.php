<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Sidemenu
  {
    protected $menu;
    static function render()
    {
      return (new static)->menu->render(true);
    }
    /**

     */
    protected function __construct()
    {
      $this->menu         = new View('sidemenu');
      $this->menu['bank'] = User::i()->can_access(SS_GL);
    }
  }
