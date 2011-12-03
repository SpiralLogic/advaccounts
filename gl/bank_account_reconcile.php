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
	/* Author Rob Mallon */
	$page_security = 'SA_RECONCILE';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	JS::open_window(800, 500);
	JS::footerFile('/js/reconcile.js');
	Page::start(_($help_context = "Reconcile Bank Account"));
	Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
	function check_date()
	{
		if (!Dates::is_date(Display::get_post('reconcile_date'))) {
			Errors::error(_("Invalid reconcile date format"));
			JS::set_focus('reconcile_date');
			return false;
		}
		return true;
	}

	//
	//	This function can be used directly in table pager
	//	if we would like to change page layout.
	//
	function rec_checkbox($row)
	{
		$name = "rec_" . $row['id'];
		$hidden = 'last[' . $row['id'] . ']';
		$value = $row['reconciled'] != '';
		// save also in hidden field for testing during 'Reconcile'
		return checkbox(null, $name, $value, true, _('Reconcile this transaction')) . hidden($hidden, $value, false);
	}

	function ungroup($row)
	{
		if ($row['type'] != 15) {
			return;
		}
		return "<button value='" . $row['id'] . '\' onclick="JsHttpRequest.request(\'_ungroup_' . $row['id'] . '\', this.form)" name="_ungroup_' . $row['id'] . '" type="submit" title="Ungroup"
    class="ajaxsubmit">Ungroup</button>' . hidden("ungroup_" . $row['id'], $row['ref'], true);
	}

	function systype_name($dummy, $type)
	{
		global $systypes_array;
		return $systypes_array[$type];
	}

	function trans_view($trans)
	{
		return get_trans_view_str($trans["type"], $trans["trans_no"]);
	}

	function gl_view($row)
	{
		if ($row['type'] != 15) {
			return get_gl_view_str($row["type"], $row["trans_no"]);
		}
	}

	function fmt_debit($row)
	{
		$value = $row["amount"];
		return $value >= 0 ? Num::price_format($value) : '';
	}

	function fmt_credit($row)
	{
		$value = -$row["amount"];
		return $value > 0 ? Num::price_format($value) : '';
	}

	function fmt_person($row)
	{

		return Banking::payment_person_name($row["person_type_id"], $row["person_id"],true, $row["trans_no"]);
	}

	$update_pager = false;
	function update_data()
	{
		global $update_pager;
		$Ajax = Ajax::i();
		unset($_POST["beg_balance"]);
		unset($_POST["end_balance"]);
		$Ajax->activate('summary');
		$update_pager = true;
	}


	// Update db record if respective checkbox value has changed.
	//
	function change_tpl_flag($reconcile_id)
	{
		$Ajax = Ajax::i();
		if (!check_date() && check_value("rec_" . $reconcile_id)) // temporary fix
		{
			return false;
		}
		if (Display::get_post('bank_date') == '') // new reconciliation
		{
			$Ajax->activate('bank_date');
		}
		$_POST['bank_date'] = Dates::date2sql(Display::get_post('reconcile_date'));
		$reconcile_value = check_value("rec_" . $reconcile_id) ? ("'" . $_POST['bank_date'] . "'") : 'NULL';
		GL_Account::update_reconciled_values($reconcile_id, $reconcile_value, $_POST['reconcile_date'], Validation::input_num('end_balance'), $_POST['bank_account']);
		$Ajax->activate('reconciled');
		$Ajax->activate('difference');
		return true;
	}

	if (Input::post('reset')) {
		GL_Account::reset_sql_for_reconcile($_POST['bank_account'], Display::get_post('reconcile_date'));
		update_data();
	}
	$groupid = find_submit("_ungroup_");
	if (isset($groupid) && $groupid > 1) {
		$grouprefs = $_POST['ungroup_' . $groupid];
		$trans = explode(',', $grouprefs);
		reset($trans);
		foreach ($trans as $tran) {
			$sql = "UPDATE bank_trans SET undeposited=1, reconciled=NULL WHERE ref=" . DB::escape($tran);
			DB::query($sql, 'Couldn\'t update undesposited status');
		}
		$sql = "UPDATE bank_trans SET ref=" . DB::escape('Removed group: ' . $grouprefs) . ", amount=0, reconciled='" . Dates::date2sql(Dates::Today()) . "',
    undeposited=" . $groupid . " WHERE id=" . $groupid;
		DB::query($sql, "Couldn't update removed group data");
		update_data();
	}
	if (isset($_SESSION['wa_current_reconcile_date']) && count($_POST) < 1) {
		if ($_SESSION['wa_current_reconcile_date'] != '') {
			$_POST['bank_date'] = $_SESSION['wa_current_reconcile_date'];
			$_POST['_bank_date_update'] = $_POST['bank_date'];
			update_data();
		}
	}
	if (!isset($_POST['reconcile_date'])) { // init page
		$_POST['reconcile_date'] = Dates::new_doc_date();
		//	$_POST['bank_date'] = Dates::date2sql(Dates::Today());
	}
	if (list_updated('bank_account')) {
		$Ajax->activate('bank_date');
		update_data();
	}
	if (list_updated('bank_date')) {
		$_POST['reconcile_date'] = Display::get_post('bank_date') == '' ? Dates::Today() : Dates::sql2date($_POST['bank_date']);
		update_data();
	}
	if (Display::get_post('_reconcile_date_changed')) {
		$_POST['bank_date'] = check_date() ? Dates::date2sql(Display::get_post('reconcile_date')) : '';
		$Ajax->activate('bank_date');
		update_data();
	}
	$id = find_submit('_rec_');
	if ($id != -1) {
		change_tpl_flag($id);
	}
	if (isset($_POST['Reconcile'])) {
		JS::set_focus('bank_date');
		foreach ($_POST['last'] as $id => $value) {
			if ($value != check_value('rec_' . $id)) {
				if (!change_tpl_flag($id)) {
					break;
				}
			}
		}
		$Ajax->activate('_page_body');
	}

	Display::start_form();
	Display::start_table();
	Display::start_row();
	Bank_UI::accounts_list_cells(_("Account:"), 'bank_account', null, true);
	Bank_UI::reconciliation_list_cells(_("Bank Statement:"), Display::get_post('bank_account'), 'bank_date', null, true, _("New"));
	//button_cell("reset", "reset", "reset");
	Display::end_row();
	Display::end_table();
	$_SESSION['wa_current_reconcile_date'] = $_POST['bank_date'];
	$result = GL_Account::get_max_reconciled(Display::get_post('reconcile_date'), $_POST['bank_account']);
	if ($row = DB::fetch($result)) {
		$_POST["reconciled"] = Num::price_format($row["end_balance"] - $row["beg_balance"]);
		$total = $row["total"];
		if (!isset($_POST["beg_balance"])) { // new selected account/statement
			$_POST["last_date"] = Dates::sql2date($row["last_date"]);
			$_POST["beg_balance"] = Num::price_format($row["beg_balance"]);
			$_POST["end_balance"] = Num::price_format($row["end_balance"]);
			if (Display::get_post('bank_date')) {
				// if it is the last updated bank statement retrieve ending balance
				$row = GL_Account::get_ending_reconciled($_POST['bank_account'], $_POST['bank_date']);
				if ($row) {
					$_POST["end_balance"] = Num::price_format($row["ending_reconcile_balance"]);
				}
			}
		}
	}
	echo "<hr>";
	Display::div_start('summary');
	Display::start_table();
	Display::table_header(_("Reconcile Date"));
	Display::start_row();
	date_cells("", "reconcile_date", _('Date of bank statement to reconcile'), Display::get_post('bank_date') == '', 0, 0, 0, null, true);
	Display::end_row();
	Display::table_header(_("Beginning Balance"));
	Display::start_row();
	amount_cells_ex("", "beg_balance", 15);
	Display::end_row();
	Display::table_header(_("Ending Balance"));
	Display::start_row();
	amount_cells_ex("", "end_balance", 15);
	$reconciled = Validation::input_num('reconciled');
	$difference = Validation::input_num("end_balance") - Validation::input_num("beg_balance") - $reconciled;
	Display::end_row();
	Display::table_header(_("Account Total"));
	Display::start_row();
	amount_cell($total);
	Display::end_row();
	Display::table_header(_("Reconciled Amount"));
	Display::start_row();
	amount_cell($reconciled, false, '', "reconciled");
	Display::end_row();
	Display::table_header(_("Difference"));
	Display::start_row();
	amount_cell($difference, false, '', "difference");
	Display::end_row();
	Display::end_table();
	Display::div_end();
	echo "<hr>";

	if (!isset($_POST['bank_account'])) {
		$_POST['bank_account'] = "";
	}
	$sql = GL_Account::get_sql_for_reconcile($_POST['bank_account'], Display::get_post('reconcile_date'));
	$act = Bank_Account::get($_POST["bank_account"]);
	Display::heading($act['bank_account_name'] . " - " . $act['bank_curr_code']);
	$cols = array(
		_("Type") => array(
			'fun' => 'systype_name', 'ord' => ''), _("#") => array(
			'fun' => 'trans_view', 'ord' => ''), _("Reference"), _("Date") => array('date', 'ord' => ''), _("Debit") => array(
			'align' => 'right', 'fun' => 'fmt_debit', 'ord' => ''), _("Credit") => array(
			'align' => 'right', 'insert' => true, 'fun' => 'fmt_credit', 'ord' => ''), _("Person/Item") => array('fun' => 'fmt_person'), array(
			'insert' => true, 'fun' => 'gl_view'), "X" => array(
			'insert' => true, 'fun' => 'rec_checkbox'), array(
			'insert' => true, 'fun' => 'ungroup'));
	$table =& db_pager::new_db_pager('trans_tbl', $sql, $cols);
	$table->width = "80%";
	DB_Pager::display($table);
	Display::br(1);
	submit_center('Reconcile', _("Reconcile"), true, '', null);
	Display::end_form();

	$js = <<<JS
	$(function() {
		$("th:nth-child(9)").click(function() {
	jQuery("#_trans_tbl_span").find("input").value("")
	})
	})
JS;
	JS::onload($js);
	end_page();

?>