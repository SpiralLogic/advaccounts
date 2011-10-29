<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/

	$page_security = 'SA_RECONCILE';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	$js = "";
	if (Config::get('ui_windows_popups')) {
		$js .= ui_view::get_js_open_window(800, 500);
	}

	JS::footerFile('/js/reconcile.js');
	page(_($help_context = "Undeposited Funds"), Input::request('frame'), false, "", $js);
	Validation::check(Validation::BANK_ACCOUNTS, _("There are no bank accounts defined in the system."));
	function check_date() {
		if (!Dates::is_date(get_post('deposit_date'))) {
			ui_msgs::display_error(_("Invalid deposit date format"));
			JS::setFocus('deposit_date');
			return false;
		}
		return true;
	}

	if (isset($_SESSION['undeposited'])) {
		foreach ($_SESSION['undeposited'] as $rowid => $row) {
			if (isset($_POST["_" . $rowid . '_update'])) {
				continue;
			}
			$amountid = substr($rowid, 4);
			$_POST['amount_' . $rowid] = $row;
			$_POST[$rowid] = 1;
		}
	}
	//
	//	This function can be used directly in table pager
	//	if we would like to change page layout.
	//	if we would like to change page layout.
	//
	function dep_checkbox($row) {
		$name = "dep_" . $row['id'];
		$hidden = 'amount_' . $row['id'];
		$value = $row['amount'];
		$chk_value = check_value("dep_" . $row['id']);
		// save also in hidden field for testing during 'Reconcile'
		return checkbox(null, $name, $chk_value, true, _('Deposit this transaction')) . hidden($hidden, $value, false);
	}

	function systype_name($dummy, $type) {
		global $systypes_array;
		return $systypes_array[$type];
	}

	function trans_view($trans) {
		return ui_view::get_trans_view_str($trans["type"], $trans["trans_no"]);
	}

	function gl_view($row) {
		return ui_view::get_gl_view_str($row["type"], $row["trans_no"]);
	}

	function fmt_debit($row) {
		$value = $row["amount"];
		return $value >= 0 ? price_format($value) : '';
	}

	function fmt_credit($row) {
		$value = -$row["amount"];
		return $value > 0 ? price_format($value) : '';
	}

	function fmt_person($row) {
		return payment_person_name($row["person_type_id"], $row["person_id"]);
	}

	$update_pager = false;
	function update_data() {
		global $update_pager;
		$Ajax = Ajax::instance();
		$Ajax->activate('summary');
		$update_pager = true;
	}

	//---------------------------------------------------------------------------------------------
	// Update db record if respective checkbox value has changed.
	//
	function change_tpl_flag($deposit_id) {
		$Ajax = Ajax::instance();
		if (!check_date() && check_value("dep_" . $deposit_id)) // temporary fix
		{
			return false;
		}
		if (get_post('bank_date') == '') // new reconciliation
		{
			$Ajax->activate('bank_date');
		}
		$_POST['bank_date'] = Dates::date2sql(get_post('deposited_date'));
		/*	$sql = "UPDATE ".''."bank_trans SET undeposited=0"
							 ." WHERE id=".DBOld::escape($deposit_id);

							DBOld::query($sql, "Can't change undeposited status");*/
		// save last reconcilation status (date, end balance)
		if (check_value("dep_" . $deposit_id)) {
			$_SESSION['undeposited']["dep_" . $deposit_id] = get_post('amount_' . $deposit_id);
			$_POST['deposited'] = $_POST['to_deposit'] + get_post('amount_' . $deposit_id);
		}
		else {
			unset($_SESSION['undeposited']["dep_" . $deposit_id]);
			$_POST['deposited'] = $_POST['to_deposit'] - get_post('amount_' . $deposit_id);
		}
		return true;
	}

	if (list_updated('deposit_date')) {
		$_POST['deposit_date'] = get_post('deposit_date') == '' ? Dates::Today() : ($_POST['deposit_date']);
		update_data();
	}
	if (get_post('_deposit_date_changed')) {
		$_POST['deposited'] = 0;
		$_SESSION['undeposited'] = array();
		$_POST['deposit_date'] = check_date() ? (get_post('deposit_date')) : '';
		foreach ($_POST as $rowid => $row) {
			if (substr($rowid, 0, 4) == 'dep_') {
				unset($_POST[$rowid]);
			}
		}
		update_data();
	}
	$id = find_submit('_dep_');
	if ($id != -1) {
		change_tpl_flag($id);
	}
	if (isset($_POST['Deposit'])) {
		$sql = "SELECT * FROM bank_trans WHERE undeposited=1  AND reconciled IS NULL";
		$query = DBOld::query($sql);
		$undeposited = array();
		while ($row = DBOld::fetch($query)) {
			$undeposited[$row['id']] = $row;
		}
		$togroup = array();
		foreach ($_POST as $key => $value) {
			$key = explode('_', $key);
			if ($key[0] == 'dep') {
				$togroup[$key[1]] = $undeposited[$key[1]];
			}
		}

		if (count($togroup) > 1) {
			$total_amount = 0;
			$ref = array();
			foreach ($togroup as $row) {
				$total_amount += $row['amount'];
				$ref[] = $row['ref'];
			}
			$sql = "INSERT INTO bank_trans (type, bank_act, amount, ref, trans_date, person_type_id, person_id, undeposited) VALUES (15, 5, $total_amount,"
			 . DBOld::escape(implode($ref, ',')) . ",'" . Dates::date2sql($_POST['deposit_date']) . "', 6, '" . $_SESSION['wa_current_user']->user . "',0)";
			$query = DBOld::query($sql, "Undeposited Cannot be Added");
			$order_no = DBOld::insert_id($query);
			if (!isset($order_no) || !empty($order_no) || $order_no == 127) {
				$sql = "SELECT LAST_INSERT_ID()";
				$order_no = DBOld::query($sql);
				$order_no = DBOld::fetch_row($order_no);
				$order_no = $order_no[0];
			}
			foreach ($togroup as $row) {
				$sql = "UPDATE bank_trans SET undeposited=" . $order_no . " WHERE id=" . DBOld::escape($row['id']);
				DBOld::query($sql, "Can't change undeposited status");
			}
		} else {
			$row = reset($togroup);

			$sql = "UPDATE bank_trans SET undeposited=0, trans_date='" . Dates::date2sql($_POST['deposit_date']) . "',deposit_date='" . Dates::date2sql($_POST['deposit_date']) . "'  WHERE id=" . DBOld::escape($row['id']);
			DBOld::query($sql, "Can't change undeposited status");
		}
		unset($_POST);
		unset($_SESSION['undeposited']);
		meta_forward($_SERVER['PHP_SELF']);
	}
	$_POST['to_deposit'] = 0;
	if (isset ($_SESSION['undeposited']) && $_SESSION['undeposited']) {
		foreach ($_SESSION['undeposited'] as $rowid => $row) {
			if (substr($rowid, 0, 4) == 'dep_') {
				$_POST['to_deposit'] += $row;
			}
		}
	}
	$_POST['deposited'] = $_POST['to_deposit'];
	$Ajax->activate('summary');

	start_form();
	start_table("class='tablestyle_noborder'");
	start_row();
	end_row();
	end_table();
	echo "<hr>";
	div_start('summary');
	start_table(Config::get('tables.style'));
	$th = array(_("Deposit Date"), _("Total Deposit<br>Amount"));
	table_header($th);
	start_row();
	date_cells("", "deposit_date", _('Date of funds to deposit'), get_post('deposit_date') == '', 0, 0, 0, null, true, array('rebind' => false));
	amount_cell($_POST['deposited'], false, '', "deposited");
	hidden("to_deposit", $_POST['to_deposit'], true);

	end_row();
	end_table();
	submit_center('Deposit', _("Deposit"), true, '', false);
	div_end();
	echo "<hr>";
	$date = Dates::add_days($_POST['deposit_date'], 10);
	$sql = "SELECT	type, trans_no, ref, trans_date,
				amount,	person_id, person_type_id, reconciled, id
		FROM bank_trans
		WHERE undeposited=1 AND trans_date <= '" . Dates::date2sql($date) . "' AND reconciled IS NULL
		ORDER BY trans_date DESC,bank_trans.id ";
	$cols = array(_("Type") => array('fun' => 'systype_name',
		'ord' => ''
	),
		_("#") => array('fun' => 'trans_view',
			'ord' => ''
		), _("Reference"),
		_("Date") => 'date',
		_("Debit") => array('align' => 'right',
			'fun' => 'fmt_debit'
		),
		_("Credit") => array('align' => 'right',
			'insert' => true,
			'fun' => 'fmt_credit'
		),
		_("Person/Item") => array('fun' => 'fmt_person'), array('insert' => true,
			'fun' => 'gl_view'
		),
		"X" => array('insert' => true,
			'fun' => 'dep_checkbox'
		)
	);
	$table =& db_pager::new_db_pager('trans_tbl', $sql, $cols);
	$table->width = "80%";
	display_db_pager($table);
	br(1);
	submit_center('Deposit', _("Deposit"), true, '', false);
	end_form();
	end_page();

?>
