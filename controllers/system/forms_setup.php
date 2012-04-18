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
  start_form();
  start_outer_table('tablestyle2');
  $systypes = SysTypes::get();
  table_section(1);
  $th = array(_("Form"), _("Next Reference"));
  table_header($th);
  $i = 0;
  while ($type = DB::fetch($systypes)) {
    if ($i++ == ST_CUSTCREDIT) {
      table_section(2);
      table_header($th);
    }
    ref_row($systypes_array[$type["type_id"]], 'id' . $type["type_id"], '', $type["next_reference"]);
  }
  end_outer_table(1);
  submit_center('setprefs', _("Update"), TRUE, '', 'default');
  end_form(2);
  Page::end();


