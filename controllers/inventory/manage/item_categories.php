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
  list($Mode, $selected_id) = Page::simple_mode(true);
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
          Form::hasPost('no_sale'));
        Event::success(_('Selected item category has been updated'));
      } else {
        Item_Category::add($_POST['description'], $_POST['tax_type_id'], $_POST['sales_account'], $_POST['cogs_account'], $_POST['inventory_account'], $_POST['adjustment_account'], $_POST['assembly_account'], $_POST['units'], $_POST['mb_flag'], $_POST['dim1'], $_POST['dim2'],
          Form::hasPost('no_sale'));
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
  function edit_link($row)
  {
    return Form::button("Edit" . $row["category_id"], _("Edit"));
  }

  /**
   * @param $row
   *
   * @return string
   */
  function delete_link($row)
  {
    return Form::button("Delete" . $row["category_id"], _("Delete"));
  }

  if ($Mode == MODE_DELETE) {
    // PREVENT DELETES IF DEPENDENT RECORDS IN 'stock_master'
    $sql    = "SELECT COUNT(*) FROM stock_master WHERE category_id=" . DB::escape($selected_id);
    $result = DB::query($sql, "could not query stock master");
    $myrow  = DB::fetch_row($result);
    if ($myrow[0] > 0) {
      Event::error(_("Cannot delete this item category because items have been created using this item category."));
    } else {
      Item_Category::delete($selected_id);
      Event::notice(_('Selected item category has been deleted'));
    }
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav         = Form::getPost('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  if (Form::isListUpdated('mb_flag')) {
    Ajax::i()->activate('details');
  }
  $sql = "SELECT c.*, t.name as tax_name FROM stock_category c, item_tax_types t WHERE c.dflt_tax_type=t.id";
  if (!Form::hasPost('show_inactive')) {
    $sql .= " AND !c.inactive";
  }
  /*$result = DB::query($sql, "could not get stock categories");
    Form::start();
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
      'insert' => true, 'fun' => 'delete_link'
    )
  );
  /*	 Form::inactiveControlCol($th);
 Table::header($th);
 $k = 0; //row colour counter
 while ($myrow = DB::fetch($result)) {

   Cell::label($myrow["description"]);
   Cell::label($myrow["tax_name"]);
   Cell::label($myrow["dflt_units"], "class=center");
   Cell::label($stock_types[$myrow["dflt_mb_flag"]]);
   Cell::label($myrow["dflt_sales_act"], "class=center");
   Cell::label($myrow["dflt_inventory_act"], "class=center");
   Cell::label($myrow["dflt_cogs_act"], "class=center");
   Cell::label($myrow["dflt_adjustment_act"], "class=center");
   Cell::label($myrow["dflt_assembly_act"], "class=center");
    Form::inactiveControlCell($myrow["category_id"], $myrow["inactive"], 'stock_category', 'category_id');
   Form::buttonEditCell("Edit" . $myrow["category_id"], _("Edit"));
   Form::buttonDeleteCell("Delete" . $myrow["category_id"], _("Delete"));
   Row::end();
 }
  Form::inactiveControlRow($th);*/
  $table =& db_pager::new_db_pager('cat_tbl', $sql, $th);
  //$table->width = "92%";
  DB_Pager::display($table);
  echo '<br>';
  Display::div_start('details');
  Table::start('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing item category
      $myrow                       = Item_Category::get($selected_id);
      $_POST['category_id']        = $myrow["category_id"];
      $_POST['description']        = $myrow["description"];
      $_POST['tax_type_id']        = $myrow["dflt_tax_type"];
      $_POST['sales_account']      = $myrow["dflt_sales_act"];
      $_POST['cogs_account']       = $myrow["dflt_cogs_act"];
      $_POST['inventory_account']  = $myrow["dflt_inventory_act"];
      $_POST['adjustment_account'] = $myrow["dflt_adjustment_act"];
      $_POST['assembly_account']   = $myrow["dflt_assembly_act"];
      $_POST['units']              = $myrow["dflt_units"];
      $_POST['mb_flag']            = $myrow["dflt_mb_flag"];
      $_POST['dim1']               = $myrow["dflt_dim1"];
      $_POST['dim2']               = $myrow["dflt_dim2"];
      $_POST['no_sale']            = $myrow["dflt_no_sale"];
    }
    Form::hidden('selected_id', $selected_id);
    Form::hidden('category_id');
  } else {
    if ($Mode != MODE_CLONE) {
      $_POST['long_description'] = '';
      $_POST['description']      = '';
      $_POST['no_sale']          = 0;
      $company_record            = DB_Company::get_prefs();
      if (Form::getPost('inventory_account') == "") {
        $_POST['inventory_account'] = $company_record["default_inventory_act"];
      }
      if (Form::getPost('cogs_account') == "") {
        $_POST['cogs_account'] = $company_record["default_cogs_act"];
      }
      if (Form::getPost('sales_account') == "") {
        $_POST['sales_account'] = $company_record["default_inv_sales_act"];
      }
      if (Form::getPost('adjustment_account') == "") {
        $_POST['adjustment_account'] = $company_record["default_adj_act"];
      }
      if (Form::getPost('assembly_account') == "") {
        $_POST['assembly_account'] = $company_record["default_assembly_act"];
      }
    }
  }
   Form::textRow(_("Category Name:"), 'description', null, 30, 30);
  Table::sectionTitle(_("Default values for new items"));
  Tax_ItemType::row(_("Item Tax Type:"), 'tax_type_id', null);
  Item_UI::type_row(_("Item Type:"), 'mb_flag', null, true);
  Item_Unit::row(_("Units of Measure:"), 'units', null);
   Form::checkRow(_("Exclude from sales:"), 'no_sale');
  GL_UI::all_row(_("Sales Account:"), 'sales_account', $_POST['sales_account']);
  if (Input::post('mb_flag') == STOCK_SERVICE) {
    GL_UI::all_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
    Form::hidden('inventory_account', $_POST['inventory_account']);
    Form::hidden('adjustment_account', $_POST['adjustment_account']);
  } else {
    GL_UI::all_row(_("Inventory Account:"), 'inventory_account', $_POST['inventory_account']);
    GL_UI::all_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
    GL_UI::all_row(_("Inventory Adjustments Account:"), 'adjustment_account', $_POST['adjustment_account']);
  }
  if (STOCK_MANUFACTURE == $_POST['mb_flag']) {
    GL_UI::all_row(_("Item Assembly Costs Account:"), 'assembly_account', $_POST['assembly_account']);
  } else {
    Form::hidden('assembly_account', $_POST['assembly_account']);
  }
  $dim = DB_Company::get_pref('use_dimension');
  if ($dim >= 1) {
    Dimensions::select_row(_("Dimension") . " 1", 'dim1', null, true, " ", false, 1);
    if ($dim > 1) {
      Dimensions::select_row(_("Dimension") . " 2", 'dim2', null, true, " ", false, 2);
    }
  }
  if ($dim < 1) {
    Form::hidden('dim1', 0);
  }
  if ($dim < 2) {
    Form::hidden('dim2', 0);
  }
  Table::end(1);
  Display::div_end();
  Form::submitAddUpdateCenter($selected_id == -1, '', 'both', true);
  Form::end();
  Page::end();

