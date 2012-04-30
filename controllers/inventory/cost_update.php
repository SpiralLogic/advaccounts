<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/


  JS::open_window(900, 500);
  Page::start(_($help_context = "Inventory Item Cost Update"), SA_STANDARDCOST);
  Validation::check(Validation::COST_ITEMS, _("There are no costable inventory items defined in the system (Purchased or manufactured items)."), STOCK_SERVICE);
  if (isset($_GET['stock_id'])) {
    $_POST['stock_id'] = $_GET['stock_id'];
  }
  if (isset($_POST['UpdateData'])) {
    $old_cost = $_POST['OldMaterialCost'] + $_POST['OldLabourCost'] + $_POST['OldOverheadCost'];
    $new_cost = Validation::input_num('material_cost') + Validation::input_num('labour_cost') + Validation::input_num('overhead_cost');
    $should_update = TRUE;
    if (!Validation::post_num('material_cost') || !Validation::post_num('labour_cost') || !Validation::post_num('overhead_cost')
    ) {
      Event::error(_("The entered cost is not numeric."));
      JS::set_focus('material_cost');
      $should_update = FALSE;
    }
    elseif ($old_cost == $new_cost) {
      Event::error(_("The new cost is the same as the old cost. Cost was not updated."));
      $should_update = FALSE;
    }
    if ($should_update) {
      $update_no = Item_Price::update_cost($_POST['stock_id'], Validation::input_num('material_cost'), Validation::input_num('labour_cost'), Validation::input_num('overhead_cost'), $old_cost);
      Event::success(_("Cost has been updated."));
      if ($update_no > 0) {
        Display::note(GL_UI::view(ST_COSTUPDATE, $update_no, _("View the GL Journal Entries for this Cost Update")), 0, 1);
      }
    }
  }
  if (list_updated('stock_id')) {
    Ajax::i()->activate('cost_table');
  }
  start_form();
  if (!Input::post('stock_id')) {
    $_POST['stock_id'] = Session::i()->global_stock_id;
  }
  echo "<div class='center'>" . _("Item:") . "&nbsp;";
  echo Item_UI::costable('stock_id', $_POST['stock_id'], FALSE, TRUE);
  echo "</div><hr>";
  Session::i()->global_stock_id = $_POST['stock_id'];
  $sql = "SELECT description, units, material_cost, labour_cost,
	overhead_cost, mb_flag
	FROM stock_master
	WHERE stock_id=" . DB::escape($_POST['stock_id']) . "
	GROUP BY description, units, material_cost, labour_cost, overhead_cost, mb_flag";
  $result = DB::query($sql, "The cost details for the item could not be retrieved");
  $myrow = DB::fetch($result);
  Display::div_start('cost_table');
  hidden("OldMaterialCost", $myrow["material_cost"]);
  hidden("OldLabourCost", $myrow["labour_cost"]);
  hidden("OldOverheadCost", $myrow["overhead_cost"]);
  start_table('tablestyle2');
  $dec1 = $dec2 = $dec3 = 0;
  $_POST['material_cost'] = Num::price_decimal($myrow["material_cost"], $dec1);
  $_POST['labour_cost'] = Num::price_decimal($myrow["labour_cost"], $dec2);
  $_POST['overhead_cost'] = Num::price_decimal($myrow["overhead_cost"], $dec3);
  amount_row(_("Standard Material Cost Per Unit"), "material_cost", NULL, "class='tablerowhead'", NULL, $dec1);
  if ($myrow["mb_flag"] == STOCK_MANUFACTURE) {
    amount_row(_("Standard Labour Cost Per Unit"), "labour_cost", NULL, "class='tablerowhead'", NULL, $dec2);
    amount_row(_("Standard Overhead Cost Per Unit"), "overhead_cost", NULL, "class='tablerowhead'", NULL, $dec3);
  }
  else {
    hidden("labour_cost", 0);
    hidden("overhead_cost", 0);
  }
  end_table(1);
  Display::div_end();
  submit_center('UpdateData', _("Update"), TRUE, FALSE, 'default');
  end_form();
  Page::end();

