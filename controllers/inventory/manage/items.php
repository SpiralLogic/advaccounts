<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  Page::start(_($help_context = "Items"), SA_ITEM, Input::request('frame'));
  $user_comp = '';
  $new_item = Input::post('stock_id') == '' || Input::post('cancel') || Input::post('clone');
  if (isset($_GET['stock_id'])) {
    $_POST['stock_id'] = $stock_id = $_GET['stock_id'];
  }
  elseif (isset($_POST['stock_id'])) {
    $stock_id = $_POST['stock_id'];
  }
  if (Forms::isListUpdated('stock_id')) {
    $_POST['NewStockID'] = Input::post('stock_id');
    clear_data();
    Ajax::i()->activate('details');
    Ajax::i()->activate('controls');
  }
  if (Input::post('cancel')) {
    $_POST['NewStockID'] = $_POST['stock_id'] = '';
    clear_data();
    JS::set_focus('stock_id');
    Ajax::i()->activate('_page_body');
  }
  if (Forms::isListUpdated('category_id') || Forms::isListUpdated('mb_flag')) {
    Ajax::i()->activate('details');
  }
  $upload_file = "";
  if (isset($_FILES['pic']) && $_FILES['pic']['name'] != '') {
    $stock_id = $_POST['NewStockID'];
    $result = $_FILES['pic']['error'];
    $upload_file = 'Yes'; //Assume all is well to start off with
    $filename = COMPANY_PATH . "$user_comp/images";
    if (!file_exists($filename)) {
      mkdir($filename);
    }
    $filename .= "/" . Item::img_name($stock_id) . ".jpg";
    //But check for the worst
    if (strtoupper(substr(trim($_FILES['pic']['name']), strlen($_FILES['pic']['name']) - 3)) != 'JPG') {
      Event::warning(_('Only jpg files are supported - a file extension of .jpg is expected'));
      $upload_file = 'No';
    }
    elseif ($_FILES['pic']['size'] > (Config::get('item_images_max_size') * 1024)) { //File Size Check
      Event::warning(_('The file size is over the maximum allowed. The maximum size allowed in KB is') . ' ' . Config::get('item_images_max_size'));
      $upload_file = 'No';
    }
    elseif ($_FILES['pic']['type'] == "text/plain") { //File type Check
      Event::warning(_('Only graphics files can be uploaded'));
      $upload_file = 'No';
    }
    elseif (file_exists($filename)) {
      $result = unlink($filename);
      if (!$result) {
        Event::error(_('The existing image could not be removed'));
        $upload_file = 'No';
      }
    }
    if ($upload_file == 'Yes') {
      $result = move_uploaded_file($_FILES['pic']['tmp_name'], $filename);
    }
    Ajax::i()->activate('details');
    /* EOF Add Image upload for New Item - by Ori */
  }
  Validation::check(Validation::STOCK_CATEGORIES, _("There are no item categories defined in the system. At least one item category is required to add a item."));
  Validation::check(Validation::ITEM_TAX_TYPES, _("There are no item tax types defined in the system. At least one item tax type is required to add a item."));
  function clear_data() {
    unset($_POST['long_description'], $_POST['description'], $_POST['category_id'], $_POST['tax_type_id'], $_POST['units'], $_POST['mb_flag'], $_POST['NewStockID'], $_POST['dimension_id'], $_POST['dimension2_id'], $_POST['no_sale']);
  }

  if (isset($_POST['addupdate']) || isset($_POST['addupdatenew'])) {
    $input_error = 0;
    if ($upload_file == 'No') {
      $input_error = 1;
    }
    if (strlen($_POST['description']) == 0) {
      $input_error = 1;
      Event::error(_('The item name must be entered.'));
      JS::set_focus('description');
    }
    elseif (empty($_POST['NewStockID'])) {
      $input_error = 1;
      Event::error(_('The item code cannot be empty'));
      JS::set_focus('NewStockID');
    }
    elseif (strstr($_POST['NewStockID'], " ") || strstr($_POST['NewStockID'], "'") || strstr($_POST['NewStockID'], "+") || strstr($_POST['NewStockID'], "\"") || strstr($_POST['NewStockID'], "&") || strstr($_POST['NewStockID'], "\t")
    ) {
      $input_error = 1;
      Event::error(_('The item code cannot contain any of the following characters - & + OR a space OR quotes'));
      JS::set_focus('NewStockID');
    }
    elseif ($new_item && DB::num_rows(Item_Code::get_kit($_POST['NewStockID']))) {
      $input_error = 1;
      Event::error(_("This item code is already assigned to stock item or sale kit."));
      JS::set_focus('NewStockID');
    }
    if ($input_error != 1) {
      if (Forms::hasPost('del_image')) {
        $filename = COMPANY_PATH . "$user_comp/images/" . Item::img_name($_POST['NewStockID']) . ".jpg";
        if (file_exists($filename)) {
          unlink($filename);
        }
      }
      if (!$new_item) { /*so its an existing one */
        Item::update($_POST['NewStockID'], $_POST['description'], $_POST['long_description'], $_POST['category_id'], $_POST['tax_type_id'], Input::post('units'), Input::post('mb_flag'), $_POST['sales_account'], $_POST['inventory_account'], $_POST['cogs_account'], $_POST['adjustment_account'],
          $_POST['assembly_account'], $_POST['dimension_id'], $_POST['dimension2_id'], Forms::hasPost('no_sale'), Forms::hasPost('editable'));
        DB::update_record_status($_POST['NewStockID'], $_POST['inactive'], 'stock_master', 'stock_id');
        DB::update_record_status($_POST['NewStockID'], $_POST['inactive'], 'item_codes', 'item_code');
        Ajax::i()->activate('stock_id'); // in case of status change
        Event::success(_("Item has been updated."));
      }
      else { //it is a NEW part
        Item::add($_POST['NewStockID'], $_POST['description'], $_POST['long_description'], $_POST['category_id'], $_POST['tax_type_id'], $_POST['units'], $_POST['mb_flag'], $_POST['sales_account'], $_POST['inventory_account'], $_POST['cogs_account'], $_POST['adjustment_account'],
          $_POST['assembly_account'], $_POST['dimension_id'], $_POST['dimension2_id'], Forms::hasPost('no_sale'), Forms::hasPost('editable'));
        Event::success(_("A new item has been added."));
        JS::set_focus('NewStockID');
      }
      if (isset($_POST['addupdatenew'])) {
        $_POST['NewStockID'] = $_POST['stock_id'] = '';
        clear_data();
        $new_item = TRUE;
        Display::meta_forward($_SERVER['DOCUMENT_URI']);
      }
      else {
        Session::i()->setGlobal('stock_id', $_POST['NewStockID']);
        $_POST['stock_id'] = $_POST['NewStockID'];
      }
      Ajax::i()->activate('_page_body');
    }
  }
  if (Input::post('clone')) {
    unset($_POST['stock_id'], $_POST['inactive']);
    JS::set_focus('NewStockID');
    Ajax::i()->activate('_page_body');
  }
  /**
   * @param      $stock_id
   * @param bool $dispmsg
   *
   * @return bool
   */
  function check_usage($stock_id, $dispmsg = TRUE) {
    $sqls = array(
      "SELECT COUNT(*) FROM stock_moves WHERE stock_id=" => _('Cannot delete this item because there are stock movements that refer to this item.'),
      "SELECT COUNT(*) FROM bom WHERE component=" => _('Cannot delete this item record because there are bills of material that require this part as a component.'),
      "SELECT COUNT(*) FROM sales_order_details WHERE stk_code=" => _('Cannot delete this item because there are existing purchase order items for it.'),
      "SELECT COUNT(*) FROM purch_order_details WHERE item_code=" => _('Cannot delete this item because there are existing purchase order items for it.')
    );
    $msg = '';
    foreach ($sqls as $sql => $err) {
      $result = DB::query($sql . DB::escape($stock_id), "could not query stock usage");
      $myrow = DB::fetch_row($result);
      if ($myrow[0] > 0) {
        $msg = $err;
        break;
      }
    }
    if ($msg == '') {
      $kits = Item_Code::get_where_used($stock_id);
      $num_kits = DB::num_rows($kits);
      if ($num_kits) {
        $msg = _("This item cannot be deleted because some code aliases or foreign codes was entered for it, or there are kits defined using this item as component") . ':<br>';
        while ($num_kits--) {
          $kit = DB::fetch($kits);
          $msg .= "'" . $kit[0] . "'";
          if ($num_kits) {
            $msg .= ',';
          }
        }
      }
    }
    if ($msg != '') {
      if ($dispmsg) {
        Event::error($msg);
      }
      return FALSE;
    }
    return TRUE;
  }

  if (isset($_POST['delete']) && strlen($_POST['delete']) > 1) {
    if (check_usage($_POST['NewStockID'])) {
      $stock_id = $_POST['NewStockID'];
      Item::del($stock_id);
      $filename = COMPANY_PATH . "$user_comp/images/" . Item::img_name($stock_id) . ".jpg";
      if (file_exists($filename)) {
        unlink($filename);
      }
      Event::notice(_("Selected item has been deleted."));
      $_POST['stock_id'] = '';
      clear_data();
      $new_item = TRUE;
      Ajax::i()->activate('_page_body');
    }
  }
  Forms::start(TRUE);
  if (Validation::check(Validation::STOCK_ITEMS)) {
    Table::start('tablestyle_noborder');
    Row::start();
    if ($new_item) {
      Item::cells(_("Select an item:"), 'stock_id', NULL, _('New item'), TRUE, Forms::hasPost('show_inactive'), FALSE);
       Forms::checkCells(_("Show inactive:"), 'show_inactive', NULL, TRUE);
    }
    else {
      Forms::hidden('stock_id', $_POST['stock_id']);
    }
    $new_item = Input::post('stock_id') == '';
    Row::end();
    Table::end();
    if (Input::post('_show_inactive_update')) {
      $_SESSION['options']['stock_id']['inactive'] = Forms::hasPost('show_inactive');
      Ajax::i()->activate('stock_id');
    }
  }
  Display::div_start('details');
  Table::startOuter('tablestyle2');
  Table::section(1);
  Table::sectionTitle(_("Item"));
  if ($new_item) {
     Forms::textRow(_("Item Code:"), 'NewStockID', NULL, 21, 20);
    $_POST['inactive'] = 0;
  }
  else { // Must be modifying an existing item
    if (Input::post('NewStockID') != Input::post('stock_id') || Input::post('addupdate')) { // first item display
      $_POST['NewStockID'] = $_POST['stock_id'];
      $myrow = Item::get($_POST['NewStockID']);
      $_POST['long_description'] = $myrow["long_description"];
      $_POST['description'] = $myrow["description"];
      $_POST['category_id'] = $myrow["category_id"];
      $_POST['tax_type_id'] = $myrow["tax_type_id"];
      $_POST['units'] = $myrow["units"];
      $_POST['mb_flag'] = $myrow["mb_flag"];
      $_POST['sales_account'] = $myrow['sales_account'];
      $_POST['inventory_account'] = $myrow['inventory_account'];
      $_POST['cogs_account'] = $myrow['cogs_account'];
      $_POST['adjustment_account'] = $myrow['adjustment_account'];
      $_POST['assembly_account'] = $myrow['assembly_account'];
      $_POST['dimension_id'] = $myrow['dimension_id'];
      $_POST['dimension2_id'] = $myrow['dimension2_id'];
      $_POST['no_sale'] = $myrow['no_sale'];
      $_POST['del_image'] = 0;
      $_POST['inactive'] = $myrow["inactive"];
      $_POST['editable'] = $myrow["editable"];
    }
    Row::label(_("Item Code:"), $_POST['NewStockID']);
    Forms::hidden('NewStockID', $_POST['NewStockID']);
    JS::set_focus('description');
  }
   Forms::textRow(_("Name:"), 'description', NULL, 52, 200);
   Forms::textareaRow(_('Description:'), 'long_description', NULL, 42, 3);
  Item_Category::row(_("Category:"), 'category_id', NULL, FALSE, $new_item);
  if ($new_item && (Forms::isListUpdated('category_id') || !isset($_POST['units']))) {
    $category_record = Item_Category::get($_POST['category_id']);
    $_POST['tax_type_id'] = $category_record["dflt_tax_type"];
    $_POST['units'] = $category_record["dflt_units"];
    $_POST['mb_flag'] = $category_record["dflt_mb_flag"];
    $_POST['inventory_account'] = $category_record["dflt_inventory_act"];
    $_POST['cogs_account'] = $category_record["dflt_cogs_act"];
    $_POST['sales_account'] = $category_record["dflt_sales_act"];
    $_POST['adjustment_account'] = $category_record["dflt_adjustment_act"];
    $_POST['assembly_account'] = $category_record["dflt_assembly_act"];
    $_POST['dimension_id'] = $category_record["dflt_dim1"];
    $_POST['dimension2_id'] = $category_record["dflt_dim2"];
    $_POST['no_sale'] = $category_record["dflt_no_sale"];
    $_POST['editable'] = 0;
  }
  $fresh_item = !isset($_POST['NewStockID']) || $new_item || check_usage($_POST['stock_id'], FALSE);
  Tax_ItemType::row(_("Item Tax Type:"), 'tax_type_id', NULL);
  Item_UI::type_row(_("Item Type:"), 'mb_flag', NULL, $fresh_item);
  Item_Unit::row(_('Units of Measure:'), 'units', NULL, $fresh_item);
   Forms::checkRow(_("Editable description:"), 'editable');
   Forms::checkRow(_("Exclude from sales:"), 'no_sale');
  Table::section(2);
  $dim = DB_Company::get_pref('use_dimension');
  if ($dim >= 1) {
    Table::sectionTitle(_("Dimensions"));
    Dimensions::select_row(_("Dimension") . " 1", 'dimension_id', NULL, TRUE, " ", FALSE, 1);
    if ($dim > 1) {
      Dimensions::select_row(_("Dimension") . " 2", 'dimension2_id', NULL, TRUE, " ", FALSE, 2);
    }
  }
  if ($dim < 1) {
    Forms::hidden('dimension_id', 0);
  }
  if ($dim < 2) {
    Forms::hidden('dimension2_id', 0);
  }
  Table::section(2);
  Table::sectionTitle(_("GL Accounts"));
  GL_UI::all_row(_("Sales Account:"), 'sales_account', $_POST['sales_account']);
  if (!$_POST['mb_flag'] == STOCK_SERVICE) {
    GL_UI::all_row(_("Inventory Account:"), 'inventory_account', $_POST['inventory_account']);
    GL_UI::all_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
    GL_UI::all_row(_("Inventory Adjustments Account:"), 'adjustment_account', $_POST['adjustment_account']);
  }
  else {
    GL_UI::all_row(_("C.O.G.S. Account:"), 'cogs_account', $_POST['cogs_account']);
    Forms::hidden('inventory_account', $_POST['inventory_account']);
    Forms::hidden('adjustment_account', $_POST['adjustment_account']);
  }
  if (STOCK_MANUFACTURE == $_POST['mb_flag']) {
    GL_UI::all_row(_("Item Assembly Costs Account:"), 'assembly_account', $_POST['assembly_account']);
  }
  else {
    Forms::hidden('assembly_account', $_POST['assembly_account']);
  }
  Table::sectionTitle(_("Other"));
  // Add image for New Item - by Joe
   Forms::fileRow(_("Image File (.jpg)") . ":", 'pic', 'pic');
  // Add Image upload for New Item - by Joe
  $stock_img_link = "";
  $check_remove_image = FALSE;
  if (isset($_POST['NewStockID']) && file_exists(COMPANY_PATH . "$user_comp/images/" . Item::img_name($_POST['NewStockID']) . ".jpg")) {
    // 31/08/08 - rand() call is necessary here to avoid caching problems. Thanks to Peter D.
    $stock_img_link .= "<img id='item_img' alt = '[" . $_POST['NewStockID'] . ".jpg]' src='" . COMPANY_PATH . "$user_comp/images/" . Item::img_name($_POST['NewStockID']) . ".jpg?nocache=" . rand() . "' height='" . Config::get('item_images_height') . "' >";
    $check_remove_image = TRUE;
  }
  else {
    $stock_img_link .= _("No image");
  }
  Row::label("&nbsp;", $stock_img_link);
  if ($check_remove_image) {
     Forms::checkRow(_("Delete Image:"), 'del_image');
  }
   Forms::checkRow(_("Exclude from sales:"), 'no_sale');
   Forms::checkRow(_("Item status:"), 'inactive');
  Table::endOuter(1);
  Display::div_end();
  Display::div_start('controls');
  if (!isset($_POST['NewStockID']) || $new_item) {
    Forms::submitCenter('addupdate', _("Insert New Item"), TRUE, '', 'default');
  }
  else {
    Forms::submitCenterBegin('addupdate', _("Update Item"), '', Input::request('frame') ? TRUE : 'default');
    Forms::submitReturn('select', Input::post('stock_id'), _("Select this items and return to document entry."), 'default');
    Forms::submit('clone', _("Clone This Item"), TRUE, '', TRUE);
    Forms::submit('delete', _("Delete This Item"), TRUE, '', TRUE);
    Forms::submit('addupdatenew', _("Save & New"), TRUE, '', TRUE);
    Forms::submitCenterEnd('cancel', _("Cancel"), _("Cancel Edition"), 'cancel');
  }
  if (Input::post('stock_id')) {
    Session::i()->setGlobal('stock_id', $_POST['stock_id']);
    echo "<iframe src='/inventory/purchasing_data.php?frame=1' style='width:48%;height:450px;overflow-x: hidden; overflow-y: scroll; ' frameborder='0'></iframe> ";
  }
  if (Input::post('stock_id')) {
    Session::i()->setGlobal('stock_id', $_POST['stock_id']);
    echo "<iframe src='/inventory/prices.php?frame=1' style='float:right;width:48%;height:450px;overflow-x: hidden; overflow-y: scroll; ' frameborder='0'></iframe> ";
  }
  Display::div_end();
  Forms::hidden('frame', Input::request('frame'));
  Forms::end();
  Page::end();

