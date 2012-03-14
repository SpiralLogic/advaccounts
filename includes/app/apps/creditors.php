<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: advanced
   * Date: 14/03/12
   * Time: 2:52 PM
   * To change this template use File | Settings | File Templates.
   */
  class Apps_Creditors extends Application {

    function __construct() {
      $this->direct = 'contacts/suppliers.php';
      parent::__construct("Creditors", _($this->help_context = "&Suppliers"));
    }
  }
