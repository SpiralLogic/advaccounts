<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Forms Setup"), SA_FORMSETUP);
  if (isset($_POST['setprefs'])) {
    $systypes = SysTypes::get();
    DB::begin();
    while ($type = DB::fetch($systypes)) {
      Ref::save($type["type_id"], $_POST['id' . $type["type_id"]]);
    }
    DB::commit();
    Event::success(_("Forms settings have been updated."));
  }
  Form::start();
  Table::startOuter('tablestyle2');
  $systypes = SysTypes::get();
  Table::section(1);
  $th = array(_("Form"), _("Next Reference"));
  Table::header($th);
  $i = 0;
  while ($type = DB::fetch($systypes)) {
    if ($i++ == ST_CUSTCREDIT) {
      Table::section(2);
      Table::header($th);
    }
     Form::refRow($systypes_array[$type["type_id"]], 'id' . $type["type_id"], '', $type["next_reference"]);
  }
  Table::endOuter(1);
  Form::submitCenter('setprefs', _("Update"), true, '', 'default');
  Form::end(2);
  Page::end();

