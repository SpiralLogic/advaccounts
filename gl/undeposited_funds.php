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
/* Author Rob Mallon */
$page_security = 'SA_RECONCILE';
$path_to_root = "..";
include($path_to_root . "/includes/db_pager.inc");
include_once($path_to_root . "/includes/session.inc");

include_once($path_to_root . "/includes/date_functions.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/data_checks.inc");

include_once($path_to_root . "/gl/includes/gl_db.inc");
include_once($path_to_root . "/includes/banking.inc");

$js = "";
if ($use_popup_windows) {
    $js .= get_js_open_window(800, 500);
}
if ($use_date_picker) {
    $js .= get_js_date_picker();
}

add_js_file('reconcile.js');

page(_($help_context = "Undeposited Funds"), @$_REQUEST['frame'], false, "", $js);

check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

function check_date() {
    if (!is_date(get_post('deposit_date'))) {
        display_error(_("Invalid deposit date format"));
        set_focus('deposit_date');
        return false;
    }
    return true;
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
    return get_trans_view_str($trans["type"], $trans["trans_no"]);
}

function gl_view($row) {
    return get_gl_view_str($row["type"], $row["trans_no"]);
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
    global $Ajax, $update_pager;
    $Ajax->activate('summary');
    $update_pager = true;
}

//---------------------------------------------------------------------------------------------
// Update db record if respective checkbox value has changed.
//
function change_tpl_flag($deposit_id) {
    global $Ajax;

    if (!check_date() && check_value("dep_" . $deposit_id)) // temporary fix
    {
        return false;
    }

    if (get_post('bank_date') == '') // new reconciliation
    {
        $Ajax->activate('bank_date');
    }

    $_POST['bank_date'] = date2sql(get_post('deposited_date'));

/*	$sql = "UPDATE ".TB_PREF."bank_trans SET undeposited=0"
		." WHERE id=".db_escape($deposit_id);

  	db_query($sql, "Can't change undeposited status");*/
    // save last reconcilation status (date, end balance)
    if (check_value("dep_" . $deposit_id)) {
        $_POST['deposited'] = $_POST['to_deposit'] + get_post('amount_' . $deposit_id);
    } else {
        $_POST['deposited'] = $_POST['to_deposit'] - get_post('amount_' . $deposit_id);
    }

    $Ajax->activate('summary');

    return true;
}

if (list_updated('deposit_date')) {
    $_POST['deposit_date'] = get_post('deposit_date') == '' ? Today() : ($_POST['deposit_date']);
    update_data();
}
if (get_post('_deposit_date_changed')) {
    $_POST['deposited'] = 0;
    $_POST['deposit_date'] = check_date() ? (get_post('deposit_date')) : '';
    foreach ($_SESSION['trans_tbl']->data as $row) {
        if (strtotime(sql2date($row['trans_date'])) <= strtotime($_POST['deposit_date']) && $_POST['dep_' . $row['id']] == 1) {
            $_POST['deposited'] += $row['amount'];
        }
    }
    $_POST['to_deposit'] = $_POST['deposited'];

    $Ajax->activate('summary');
    update_data();

} elseif ($_POST['to_deposit'] == '') {
    $_POST['to_deposit'] = 0;
}


$id = find_submit('_dep_');
if ($id != -1) {
    change_tpl_flag($id);
}

if (isset($_POST['Deposit'])) {
    $sql = "SELECT * FROM " . TB_PREF . "bank_trans WHERE undeposited=1 AND trans_date <= '" . date2sql($_POST['deposit_date']) . "' AND reconciled IS NULL";
    $query = db_query($sql);
    $undeposited = array();
    while ($row = db_fetch($query)) {
        $undeposited[$row['id']] = $row;
    }
    $togroup = array();
    foreach ($_POST as $key => $value) {
        $key = explode('_', $key);
        if ($key[0] == 'dep') {
            $togroup[$key[1]] = $undeposited[$key[1]];
        }
    }

    $total_amount = 0;
    $ref = array();
    foreach ($togroup as $row) {
        $total_amount += $row['amount'];
        $ref[] = $row['ref'];
    }
    $sql = "INSERT INTO " . TB_PREF . "bank_trans (type, bank_act, amount, ref, trans_date, person_type_id, person_id, undeposited) VALUES (15, 5, $total_amount," . db_escape(implode($ref,
        ',')) . ",'" . date2sql($_POST['deposit_date']) . "', 6, '" . $_SESSION['wa_current_user']->user . "',0)";
    $query = db_query($sql, "Undeposited Cannot be Added");
    
    $sql = "SELECT LAST_INSERT_ID()";
    $order_no = db_query($sql);
    $order_no = db_fetch_row($order_no);
    $order_no = $order_no[0];

    foreach ($togroup as $row) {

        $sql = "UPDATE " . TB_PREF . "bank_trans SET undeposited=" . $order_no . " WHERE id=" . db_escape($row['id']);
        db_query($sql, "Can't change undeposited status");
    }

}
start_form();
start_table("class='tablestyle_noborder'");
start_row();
end_row();
end_table();


echo "<hr>";

div_start('summary');

start_table($table_style);
$th = array(_("Deposit Date"), _("Total Deposit<br>Amount"));
table_header($th);
start_row();

date_cells("", "deposit_date", _('Date of funds to deposit'), get_post('deposit_date') == '', 0, 0, 0, null, true);

amount_cell($_POST['deposited'], false, '', "deposited");
hidden("to_deposit", $_POST['deposited'], true);
end_row();
end_table();
div_end();
echo "<hr>";

$date = $_POST['deposit_date'];
$sql = "SELECT	type, trans_no, ref, trans_date,
				amount,	person_id, person_type_id, reconciled, id
		FROM " . TB_PREF . "bank_trans
		WHERE undeposited=1 AND trans_date <= '" . date2sql($date) . "' AND reconciled IS NULL
		ORDER BY trans_date," . TB_PREF . "bank_trans.id";


$cols = array(
    _("Type") => array('fun' => 'systype_name', 'ord' => ''),
    _("#") => array('fun' => 'trans_view', 'ord' => ''),
    _("Reference"),
    _("Date") => 'date', 
    _("Debit") => array('align' => 'right', 'fun' => 'fmt_debit'),
    _("Credit") => array('align' => 'right', 'insert' => true, 'fun' => 'fmt_credit'),
    _("Person/Item") => array('fun' => 'fmt_person'),
    array('insert' => true, 'fun' => 'gl_view'),
    "X" => array('insert' => true, 'fun' => 'dep_checkbox'));
$table =& new_db_pager('trans_tbl', $sql, $cols);

$table->width = "80%";
display_db_pager($table);

br(1);
submit_center('Deposit', _("Deposit"), true, '', false);

end_form();
end_page();

?>