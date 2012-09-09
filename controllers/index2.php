<?php

  phpinfo();
  use ADV\Core\DB\DB;

  /**
   * Object hashing tests.
   */
  class test
  {

    public $function;
    function __construct() {
      echo '<pre >';
      $cols         = array(
        _("ID"),
        ['type'=> "skip"],
        _("Name"),
        _("User"),
        _("Phone"),
        _("Fax"),
        _("Email"),
        _("Provision"),
        _("Break Pt."),
        _("Provision") . " 2",
        _('Inactive')=> ['type'=> "active"],
      );
      $pager_name   = '_table';
      $table        = DB_Pager::new_db_pager($pager_name, \ADV\App\Sales\Person::getAll(), $cols);
      $table->class = 'width50';
      $table->display();
    }
  }

  new test();

  unset($_SESSION);
