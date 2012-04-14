<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  //require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");

  Page::start(_($help_context = "Costed Bill Of Material Inquiry"), SA_WORKORDERCOST);
  Validation::check(Validation::BOM_ITEMS, _("There are no manufactured or kit items defined in the system."), STOCK_MANUFACTURE);
  if (isset($_GET['stock_id'])) {
    $_POST['stock_id'] = $_GET['stock_id'];
  }
  if (list_updated('stock_id')) {
    Ajax::i()->activate('_page_body');
  }
  start_form(FALSE);
  start_table('tablestyle_noborder');
  Item_UI::manufactured_row(_("Select a manufacturable item:"), 'stock_id', NULL, FALSE, TRUE);
  end_table();
  Display::br();
  Display::heading(_("All Costs Are In:") . " " . Bank_Currency::for_company());
  WO::display_bom(Input::post('stock_id'));
  end_form();
  Page::end();

