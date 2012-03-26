<?php
  /**********************************************************************
  Copyright (C) Advanced Group PTY LTD
  Released under the terms of the GNU General Public License, GPL,
  as published by the Free Software Foundation, either version 3
  of the License, or (at your option) any later version.
  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
   ***********************************************************************/
  require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
  $js = "";
  Page::start(_($help_context = "Exchange Rates"), SA_EXCHANGERATE);
  list($Mode, $selected_id) = Page::simple_mode(FALSE);
  if ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM) {
    handle_submit($selected_id);
  }
  if ($Mode == MODE_DELETE) {
    handle_delete($selected_id);
  }
  start_form();
  if (!isset($_POST['curr_abrev'])) {
    $_POST['curr_abrev'] = Session::i()->global_curr_code;
  }
  echo "<div class='center'>";
  echo _("Select a currency :") . " ";
  echo GL_Currency::select('curr_abrev', NULL, TRUE);
  echo "</div>";
  // if currency sel has changed, clear the form
  if ($_POST['curr_abrev'] != Session::i()->global_curr_code) {
    clear_data();
    $selected_id = "";
  }
  Session::i()->global_curr_code = $_POST['curr_abrev'];
  $sql = "SELECT date_, rate_buy, id FROM exchange_rates " . "WHERE curr_code=" . DB::quote($_POST['curr_abrev']) . "
	 ORDER BY date_ DESC";
  $cols = array(
    _("Date to Use From") => 'date', _("Exchange Rate") => 'rate', array(
      'insert' => TRUE, 'fun' => 'edit_link'
    ), array(
      'insert' => TRUE, 'fun' => 'del_link'
    ),
  );
  $table =& db_pager::new_db_pager('orders_tbl', $sql, $cols);
  if (Bank_Currency::is_company($_POST['curr_abrev'])) {
    Event::warning(_("The selected currency is the company currency."), 2);
    Event::warning(_("The company currency is the base currency so exchange rates cannot be set for it."), 1);
  }
  else {
    Display::br(1);
    $table->width = "40%";
    if ($table->rec_count == 0) {
      $table->ready = FALSE;
    }
    DB_Pager::display($table);
    Display::br(1);
    display_rate_edit($selected_id);
  }
  end_form();
  Page::end();
  function check_data() {
    if (!Dates::is_date($_POST['date_'])) {
      Event::error(_("The entered date is invalid."));
      JS::set_focus('date_');
      return FALSE;
    }
    if (Validation::input_num('BuyRate') <= 0) {
      Event::error(_("The exchange rate cannot be zero or a negative number."));
      JS::set_focus('BuyRate');
      return FALSE;
    }
    if (GL_ExchangeRate::get_date($_POST['curr_abrev'], $_POST['date_'])) {
      Event::error(_("The exchange rate for the date is already there."));
      JS::set_focus('date_');
      return FALSE;
    }
    return TRUE;
  }

  function handle_submit(&$selected_id) {
    if (!check_data()) {
      return FALSE;
    }
    if ($selected_id != "") {
      GL_ExchangeRate::update($_POST['curr_abrev'], $_POST['date_'], Validation::input_num('BuyRate'), Validation::input_num('BuyRate'));
    }
    else {
      GL_ExchangeRate::add($_POST['curr_abrev'], $_POST['date_'], Validation::input_num('BuyRate'), Validation::input_num('BuyRate'));
    }
    $selected_id = '';
    clear_data();
  }

  function handle_delete(&$selected_id) {
    if ($selected_id == "") {
      return;
    }
    GL_ExchangeRate::delete($selected_id);
    $selected_id = '';
    clear_data();
  }

  function edit_link($row) {
    return button(MODE_EDIT . $row["id"], _("Edit"), TRUE, ICON_EDIT);
  }

  function del_link($row) {
    return button(MODE_DELETE . $row["id"], _("Delete"), TRUE, ICON_DELETE);
  }

  function display_rates($curr_code) {
  }

  function display_rate_edit(&$selected_id) {
    start_table('tablestyle2');
    if ($selected_id != "") {
      //editing an existing exchange rate
      $myrow = GL_ExchangeRate::get($selected_id);
      $_POST['date_'] = Dates::sql2date($myrow["date_"]);
      $_POST['BuyRate'] = Num::exrate_format($myrow["rate_buy"]);
      hidden('selected_id', $selected_id);
      hidden('date_', $_POST['date_']);
      label_row(_("Date to Use From:"), $_POST['date_']);
    }
    else {
      $_POST['date_'] = Dates::today();
      $_POST['BuyRate'] = '';
      date_row(_("Date to Use From:"), 'date_');
    }
    if (isset($_POST['get_rate'])) {
      $_POST['BuyRate'] = Num::exrate_format(GL_ExchangeRate::retrieve($_POST['curr_abrev'], $_POST['date_']));
      Ajax::i()->activate('BuyRate');
    }
    small_amount_row(_("Exchange Rate:"), 'BuyRate', NULL, '', submit('get_rate', _("Get"), FALSE, _('Get current ECB rate'), TRUE), User::exrate_dec());
    end_table(1);
    submit_add_or_update_center($selected_id == '', '', 'both');
    Event::warning(_("Exchange rates are entered against the company currency."), 1);
  }

  function clear_data() {
    unset($_POST['selected_id'], $_POST['date_'], $_POST['BuyRate']);
  }

?>
