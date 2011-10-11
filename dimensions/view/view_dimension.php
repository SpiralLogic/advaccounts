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
	$page_security = 'SA_DIMTRANSVIEW';

	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");

	$js = "";
	page(_($help_context = "View Dimension"), true, false, "", $js);

	include_once(APP_PATH . "dimensions/includes/dimensions_db.php");
	include_once(APP_PATH . "dimensions/includes/dimensions_ui.php");

	//-------------------------------------------------------------------------------------------------

	if (isset($_GET['trans_no']) && $_GET['trans_no'] != "") {
		$id = $_GET['trans_no'];
	}

	if (isset($_POST['Show'])) {
		$id = $_POST['trans_no'];
	}

	ui_msgs::display_heading($systypes_array[ST_DIMENSION] . " # " . $id);

	br(1);
	$myrow = get_dimension($id);

	if (strlen($myrow[0]) == 0) {
		echo _("The dimension number sent is not valid.");
		exit;
	}

	start_table(Config::get('tables.style'));

	$th = array(_("#"), _("Reference"), _("Name"), _("Type"), _("Date"), _("Due Date"));
	table_header($th);

	start_row();
	label_cell($myrow["id"]);
	label_cell($myrow["reference"]);
	label_cell($myrow["name"]);
	label_cell($myrow["type_"]);
	label_cell(Dates::sql2date($myrow["date_"]));
	label_cell(Dates::sql2date($myrow["due_date"]));
	end_row();

	ui_view::comments_display_row(ST_DIMENSION, $id);

	end_table();

	if ($myrow["closed"] == true) {
		ui_msgs::display_note(_("This dimension is closed."));
	}

	start_form();

	start_table("class='tablestyle_noborder'");
	start_row();

	if (!isset($_POST['TransFromDate']))
		$_POST['TransFromDate'] = Dates::begin_fiscalyear();
	if (!isset($_POST['TransToDate']))
		$_POST['TransToDate'] = Dates::Today();
	date_cells(_("from:"), 'TransFromDate');
	date_cells(_("to:"), 'TransToDate');
	submit_cells('Show', _("Show"), '', false, 'default');

	end_row();

	end_table();
	hidden('trans_no', $id);
	end_form();

	display_dimension_balance($id, $_POST['TransFromDate'], $_POST['TransToDate']);

	br(1);

	end_page(true);

?>
