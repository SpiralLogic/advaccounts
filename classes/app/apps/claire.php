<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Apps_Claire extends Application {
    /**
     *
     */
    function __construct() {
      parent::__construct("Claire", _($this->help_context = "&Claire"));
    }
  }
