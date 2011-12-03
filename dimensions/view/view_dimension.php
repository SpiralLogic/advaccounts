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
	$page_security = 'SA_DIMTRANSVIEW';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	$js = "";
	Page::start(_($help_context = "View Dimension"), true);

	if (isset($_GET['trans_no']) && $_GET['trans_no'] != "") {
		$id = $_GET['trans_no'];
	}
	if (isset($_POST['Show'])) {
		$id = $_POST['trans_no'];
	}
	Display::heading($systypes_array[ST_DIMENSION] . " # " . $id);
	Display::br(1);
	$myrow = Dimensions::get($id);
	if (strlen($myrow[0]) == 0) {
		echo _("The dimension number sent is not valid.");
		exit;
	}
	Display::start_table(Config::get('tables_style'));
	$th = array(_("#"), _("Reference"), _("Name"), _("Type"), _("Date"), _("Due Date"));
	Display::table_header($th);
	Display::start_row();
	label_cell($myrow["id"]);
	label_cell($myrow["reference"]);
	label_cell($myrow["name"]);
	label_cell($myrow["type_"]);
	label_cell(Dates::sql2date($myrow["date_"]));
	label_cell(Dates::sql2date($myrow["due_date"]));
	Display::end_row();
	DB_Comments::display_row(ST_DIMENSION, $id);
	Display::end_table();
	if ($myrow["closed"] == true) {
		Errors::warning(_("This dimension is closed."));
	}
	Display::start_form();
	Display::start_table("class='tablestyle_noborder'");
	Display::start_row();
	if (!isset($_POST['TransFromDate'])) {
		$_POST['TransFromDate'] = Dates::begin_fiscalyear();
	}
	if (!isset($_POST['TransToDate'])) {
		$_POST['TransToDate'] = Dates::Today();
	}
	date_cells(_("from:"), 'TransFromDate');
	date_cells(_("to:"), 'TransToDate');
	submit_cells('Show', _("Show"), '', false, 'default');
	Display::end_row();
	Display::end_table();
	hidden('trans_no', $id);
	Display::end_form();
	Dimensions::display_balance($id, $_POST['TransFromDate'], $_POST['TransToDate']);
	Display::br(1);
	end_page(true);

?>
