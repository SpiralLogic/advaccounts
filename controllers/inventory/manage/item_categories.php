<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/


  Page::start(_($help_context = "Item Categories"), SA_ITEMCATEGORY);
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    //initialise no input errors assumed initially before we test
    $input_error = 0;
    if (strlen($_POST['description']) == 0) {
      $input_error = 1;
      Event::error(_("The item category description cannot be empty."));
      JS::set_focus('description');
    }
    if ($input_error != 1) {
      if ($selected_id != -1) {
        Item_Category::update($selected_id, $_POST['description'], $_POST['tax_type_id'], $_POST['sales_account'], $_POST['cogs_account'], $_POST['inventory_account'], $_POST['adjustment_account'], $_POST['assembly_account'], $_POST['units'], $_POST['mb_flag'], $_POST['dim1'], $_POST['dim2'],
          check_value('no_sale'));
        Event::success(_('Selected item category has been updated'));
      }
      else {
        Item_Category::add($_POST['description'], $_POST['tax_type_id'], $_POST['sales_account'], $_POST['cogs_account'], $_POST['inventory_account'], $_POST['adjustment_account'], $_POST['assembly_account'], $_POST['units'], $_POST['mb_flag'], $_POST['dim1'], $_POST['dim2'],
          check_value('no_sale'));
        Event::success(_('New item category has been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  /**
   * @param $row
   *
   * @return string
   */
  function edit_link($row) {
    return button("Edit" . $row["category_id"], _("Edit"));
  }

  /**
   * @param $row
   *
   * @return string
   */
  function delete_link($row) {
    return button("Delete" . $row["category_id"], _("Delete"));
  }

  if ($Mode == MODE_DELETE) {
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'stock_master'
    $sql = "SELECT COUNT(*) FROM stock_master WHERE category_id=" . DB::escape($selected_id);
    $result = DB::query($sql, "could not query stock master");
    $myrow = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this item category because items have been created using this item category."));
    }
    else {
      Item_Category::delete($selected_id);
      Event::notice(_('Selected item category has been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav = get_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  if (list_updated('mb_flag')) {
    Ajax::i()->activate('details');
  }
  $sql = "SELECT c.*, t.name as tax_name FROM stock_category c, item_tax_types t WHERE c.dflt_tax_type=t.id";
  if (!check_value('show_inactive')) {
    $sql .= " AND !c.inactive";
  }
  /*$result = DB::query($sql, "could not get stock categories");
    start_form();
    Table::start('tablestyle width90');*/
  $th = array(
    array('type' => 'skip'),
    _("Name"),
    array('type' => 'skip'),
    _("Tax type"),
    _("Units"),
    _("Type"),
    _("Sales Act"),
    _("Inventory Account"),
    _("COGS Account"),
    _("Adjustment Account"),
    _("Assembly Account"),
    array(
      'fun' => 'edit_link'
    ),
    array(
      'insert' => TRUE, 'fun' => 'delete_link'
    )
  );
  /*	inactive_control_column($th);
 Table::header($th);
 $k = 0; //row colour counter
 while ($myrow = DB::fetch($result))
 {

   Cell::label($myrow["description"]);
   Cell::label($myrow["tax_name"]);
   Cell::label($myrow["dflt_units"], "class=center");
   Cell::label($stock_types[$myrow["dflt_mb_flag"]]);
   Cell::label($myrow["dflt_sales_act"], "class=center");
   Cell::label($myrow["dflt_inventory_act"], "class=center");
   Cell::label($myrow["dflt_cogs_act"], "class=center");
   Cell::label($myrow["dflt_adjustment_act"], "class=center");
   Cell::label($myrow["dflt_assembly_act"], "class=center");
   inactive_control_cell($myrow["category_id"], $myrow["inactive"], 'stock_category', 'category_id');
   edit_button_cell("Edit" . $myrow["category_id"], _("Edit"));
   delete_button_cell("Delete" . $myrow["category_id"], _("Delete"));
   Row::end();
 }
 inactive_control_row($th);*/
  $table =& db_pager::new_db_pager('cat_tbl', $sql, $th);
  //$table->width = "92%";
  DB_Pager::display($table);
  echo '<br>';
  Display::div_start('details');
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing item category
      $myrow = Item_Category::get($selected_id);
      $_POST['category_id'] = $myrow["category_id"];
      $_POST['description'] = $myrow["description"];
      $_POST['tax_type_id'] = $myrow["dflt_tax_type"];
      $_POST['sales_account'] = $myrow["dflt_sales_act"];
      $_POST['cogs_account'] = $myrow["dflt_cogs_act"];
      $_POST['inventory_account'] = $myrow["dflt_inventory_act"];
      $_POST['adjustment_account'] = $myrow["dflt_adjustment_act"];
      $_POST['assembly_account'] = $myrow["dflt_assembly_act"];
      $_POST['units'] = $myrow["dflt_units"];
      $_POST['mb_flag'] = $myrow["dflt_mb_flag"];
      $_POST['dim1'] = $myrow["dflt_dim1"];
      $_POST['dim2'] = $myrow["dflt_dim2"];
      $_POST['no_sale'] = $myrow["dflt_no_sale"];
    }
    hidden('selected_id', $selected_id);
    hidden('category_id');
  }
  else {
    if ($Mode != MODE_CLONE) {
      $_POST['long_description'] = '';
      $_POST['description'] = '';
      $_POST['no_sale'] = 0;
      $company_record = DB_Company::get_prefs();
      if (get_post('inventory_account') == "") {
        $_POST['inventory_account'] = $company_record["default_inventory_act"];
      }
      if (get_post('cogs_account') == "") {
        $_POST['cogs_account'] = $company_record["default_cogs_act"];
      }
      if (get_post('sales_account') == "") {
        $_POST['sales_account'] = $company_record["default_inv_sales_act"];
      }
      if (get_post('adjustment_account') == "") {
        $_POST['adjustment_account'] = $company_record["default_adj_act"];
      }
      if (get_post('assembly_account') == "") {
        $_POST['assembly_account'] = $company_record["default_assembly_act"];
      }
    }
  }
  text_row(_("Category Name:"), 'description', NULL, 30, 30);
  Table::sectionTitle(_("Default values for new items"));
  Tax_ItemType::row(_("Item Tax Type:"), 'tax_type_id', NULL);
  Item_UI::type_row(_("Item Type:"), 'mb_flag', NULL, TRUE);
  Item_Unit::row(_("Units of Measure:"), 'units', NULL);
  check_row(_("Exclude from sales:"), 'no_sale');
  GL_UI::all_row(_("Sales Account:"), 'sales_account', $_POST['sales_account']);
  if (Input::post('mb_flag') == STOCK_SERVICE) {
    GL_UI::all_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
    hidden('inventory_account', $_POST['inventory_account']);
    hidden('adjustment_account', $_POST['adjustment_account']);
  }
  else {
    GL_UI::all_row(_("Inventory Account:"), 'inventory_account', $_POST['inventory_account']);
    GL_UI::all_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
    GL_UI::all_row(_("Inventory Adjustments Account:"), 'adjustment_account', $_POST['adjustment_account']);
  }
  if (STOCK_MANUFACTURE == $_POST['mb_flag']) {
    GL_UI::all_row(_("Item Assembly Costs Account:"), 'assembly_account', $_POST['assembly_account']);
  }
  else {
    hidden('assembly_account', $_POST['assembly_account']);
  }
  $dim = DB_Company::get_pref('use_dimension');
  if ($dim >= 1) {
    Dimensions::select_row(_("Dimension") . " 1", 'dim1', NULL, TRUE, " ", FALSE, 1);
    if ($dim > 1) {
      Dimensions::select_row(_("Dimension") . " 2", 'dim2', NULL, TRUE, " ", FALSE, 2);
    }
  }
  if ($dim < 1) {
    hidden('dim1', 0);
  }
  if ($dim < 2) {
    hidden('dim2', 0);
  }
  Table::end(1);
  Display::div_end();
  submit_add_or_update_center($selected_id == -1, '', 'both', TRUE);
  end_form();
  Page::end();


