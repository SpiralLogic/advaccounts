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
	$page_security = 'SA_GLTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "General Ledger Transaction Details"), true);
	if (!isset($_GET['type_id']) || !isset($_GET['trans_no'])) { /*Script was not passed the correct parameters */
		echo "<p>" . _("The script must be called with a valid transaction type and transaction number to review the general ledger postings for.") . "</p>";
		exit;
	}
	function display_gl_heading($myrow)
		{
			global $systypes_array;
			$trans_name = $systypes_array[$_GET['type_id']];
			start_table('tablestyle width95');
			$th = array(
				_("General Ledger Transaction Details"), _("Reference"), _("Date"), _("Person/Item"));
			table_header($th);
			start_row();
			label_cell("$trans_name #" . $_GET['trans_no']);
			label_cell($myrow["reference"]);
			label_cell(Dates::sql2date($myrow["tran_date"]));
			label_cell(Bank::payment_person_name($myrow["person_type_id"], $myrow["person_id"]));
			end_row();
			DB_Comments::display_row($_GET['type_id'], $_GET['trans_no']);
			end_table(1);
		}

	$sql = "SELECT gl.*, cm.account_name, IF(ISNULL(refs.reference), '', refs.reference) AS reference FROM gl_trans as gl
	LEFT JOIN chart_master as cm ON gl.account = cm.account_code
	LEFT JOIN refs as refs ON (gl.type=refs.type AND gl.type_no=refs.id)" . " WHERE gl.type= " . DB::escape($_GET['type_id']) . " AND gl.type_no = " . DB::escape($_GET['trans_no']) . " ORDER BY counter";
	$result = DB::query($sql, "could not get transactions");
	//alert("sql = ".$sql);
	if (DB::num_rows($result) == 0) {
		echo "<p><div class='center'>" . _("No general ledger transactions have been created for") . " " . $systypes_array[$_GET['type_id']] . " " . _("number") . " " . $_GET['trans_no'] . "</div></p><br><br>";
		end_page(true);
		exit;
	}
	/*show a table of the transactions returned by the sql */
	$dim = DB_Company::get_pref('use_dimension');
	if ($dim == 2) {
		$th = array(
			_("Account Code"), _("Account Name"), _("Dimension") . " 1", _("Dimension") . " 2", _("Debit"), _("Credit"), _("Memo"));
	} else if ($dim == 1) {
		$th = array(
			_("Account Code"), _("Account Name"), _("Dimension"), _("Debit"), _("Credit"), _("Memo"));
	} else {
		$th = array(
			_("Account Code"), _("Account Name"), _("Debit"), _("Credit"), _("Memo"));
	}
	$k = 0; //row colour counter
	$heading_shown = false;
	while ($myrow = DB::fetch($result)) {
		if ($myrow['amount'] == 0) {
			continue;
		}
		if (!$heading_shown) {
			display_gl_heading($myrow);
			start_table('tablestyle width95');
			table_header($th);
			$heading_shown = true;
		}
		Display::alt_table_row_color($k);
		label_cell($myrow['account']);
		label_cell($myrow['account_name']);
		if ($dim >= 1) {
			label_cell(Dimensions::get_string($myrow['dimension_id'], true));
		}
		if ($dim > 1) {
			label_cell(Dimensions::get_string($myrow['dimension2_id'], true));
		}
		debit_or_credit_cells($myrow['amount']);
		label_cell($myrow['memo_']);
		end_row();
	}
	//end of while loop
	if ($heading_shown) {
		end_table(1);
	}
	Display::is_voided($_GET['type_id'], $_GET['trans_no'], _("This transaction has been voided."));
	end_page(true);

?>
