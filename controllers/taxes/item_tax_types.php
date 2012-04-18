<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/

  Page::start(_($help_context = "Item Tax Types"), SA_ITEMTAXTYPE);
  list($Mode, $selected_id) = Page::simple_mode(TRUE);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    $input_error = 0;
    if (strlen($_POST['name']) == 0) {
      $input_error = 1;
      Event::error(_("The item tax type description cannot be empty."));
      JS::set_focus('name');
    }
    if ($input_error != 1) {
      // create an array of the exemptions
      $exempt_from = array();
      $tax_types = Tax_Types::get_all_simple();
      $i = 0;
      while ($myrow = DB::fetch($tax_types)) {
        if (check_value('ExemptTax' . $myrow["id"])) {
          $exempt_from[$i] = $myrow["id"];
          $i++;
        }
      }
      if ($selected_id != -1) {
        Tax_ItemType::update($selected_id, $_POST['name'], $_POST['exempt'], $exempt_from);
        Event::success(_('Selected item tax type has been updated'));
      }
      else {
        Tax_ItemType::add($_POST['name'], $_POST['exempt'], $exempt_from);
        Event::success(_('New item tax type has been added'));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
      Tax_ItemType::delete($selected_id);
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav = get_post('show_inactive');
    unset($_POST);
    $_POST['show_inactive'] = $sav;
  }
  $result2 = $result = Tax_ItemType::get_all(check_value('show_inactive'));
  start_form();
  start_table('tablestyle width30');
  $th = array(_("Name"), _("Tax exempt"), '', '');
  inactive_control_column($th);
  table_header($th);
  $k = 0;
  while ($myrow = DB::fetch($result2)) {
    alt_table_row_color($k);
    if ($myrow["exempt"] == 0) {
      $disallow_text = _("No");
    }
    else {
      $disallow_text = _("Yes");
    }
    label_cell($myrow["name"]);
    label_cell($disallow_text);
    inactive_control_cell($myrow["id"], $myrow["inactive"], 'item_tax_types', 'id');
    edit_button_cell("Edit" . $myrow["id"], _("Edit"));
    delete_button_cell("Delete" . $myrow["id"], _("Delete"));
    end_row();
  }
  inactive_control_row($th);
  end_table(1);
  start_table('tablestyle2');
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      $myrow = Tax_ItemType::get($selected_id);
      unset($_POST); // clear exemption checkboxes
      $_POST['name'] = $myrow["name"];
      $_POST['exempt'] = $myrow["exempt"];
      // read the exemptions and check the ones that are on
      $exemptions = Tax_ItemType::get_exemptions($selected_id);
      if (DB::num_rows($exemptions) > 0) {
        while ($exmp = DB::fetch($exemptions)) {
          $_POST['ExemptTax' . $exmp["tax_type_id"]] = 1;
        }
      }
    }
    hidden('selected_id', $selected_id);
  }
  text_row_ex(_("Description:"), 'name', 50);
  yesno_list_row(_("Is Fully Tax-exempt:"), 'exempt', NULL, "", "", TRUE);
  end_table(1);
  if (!isset($_POST['exempt']) || $_POST['exempt'] == 0) {
    Event::warning(_("Select which taxes this item tax type is exempt from."), 0, 1);
    start_table('tablestyle2');
    $th = array(_("Tax Name"), _("Rate"), _("Is exempt"));
    table_header($th);
    $tax_types = Tax_Types::get_all_simple();
    while ($myrow = DB::fetch($tax_types)) {
      alt_table_row_color($k);
      label_cell($myrow["name"]);
      label_cell(Num::percent_format($myrow["rate"]) . " %", ' class="right nowrap"');
      check_cells("", 'ExemptTax' . $myrow["id"], NULL);
      end_row();
    }
    end_table(1);
  }
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  Page::end();
  /**
   * @param $selected_id
   *
   * @return bool
   */



