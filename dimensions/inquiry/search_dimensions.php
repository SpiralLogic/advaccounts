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
	JS::open_window(800, 500);
	if (isset($_GET['outstanding_only']) && $_GET['outstanding_only']) {
		$outstanding_only = 1;
		Page::start(_($help_context = "Search Outstanding Dimensions"));
	} else {
		$outstanding_only = 0;
		Page::start(_($help_context = "Search Dimensions"));
	}

	// Ajax updates
	//
	if (Display::get_post('SearchOrders')) {
		$Ajax->activate('dim_table');
	} elseif (Display::get_post('_OrderNumber_changed'))
	{
		$disable = Display::get_post('OrderNumber') !== '';
		$Ajax->addDisable(true, 'FromDate', $disable);
		$Ajax->addDisable(true, 'ToDate', $disable);
		$Ajax->addDisable(true, 'type_', $disable);
		$Ajax->addDisable(true, 'OverdueOnly', $disable);
		$Ajax->addDisable(true, 'OpenOnly', $disable);
		if ($disable) {
			//		$Ajax->addFocus(true, 'OrderNumber');
			JS::set_focus('OrderNumber');
		} else
		{
			JS::set_focus('type_');
		}
		$Ajax->activate('dim_table');
	}

	if (isset($_GET["stock_id"])) {
		$_POST['SelectedStockItem'] = $_GET["stock_id"];
	}

	Display::start_form(false, false, $_SERVER['PHP_SELF'] . "?outstanding_only=$outstanding_only");
	Display::start_table("class='tablestyle_noborder'");
	Display::start_row();
	ref_cells(_("Reference:"), 'OrderNumber', '', null, '', true);
	number_list_cells(_("Type"), 'type_', null, 1, 2, _("All"));
	date_cells(_("From:"), 'FromDate', '', null, 0, 0, -5);
	date_cells(_("To:"), 'ToDate');
	check_cells(_("Only Overdue:"), 'OverdueOnly', null);
	if (!$outstanding_only) {
		check_cells(_("Only Open:"), 'OpenOnly', null);
	} else {
		$_POST['OpenOnly'] = 1;
	}
	submit_cells('SearchOrders', _("Search"), '', '', 'default');
	Display::end_row();
	Display::end_table();
	$dim = DB_Company::get_pref('use_dimension');
	function view_link($row)
	{
		return get_dimensions_trans_view_str(ST_DIMENSION, $row["id"]);
	}

	function is_closed($row)
	{
		return $row['closed'] ? _('Yes') : _('No');
	}

	function sum_dimension($row)
	{
		$sql = "SELECT SUM(amount) FROM gl_trans WHERE tran_date >= '" .
		 Dates::date2sql($_POST['FromDate']) . "' AND
		tran_date <= '" . Dates::date2sql($_POST['ToDate']) . "' AND (dimension_id = " .
		 $row['id'] . " OR dimension2_id = " . $row['id'] . ")";
		$res = DB::query($sql, "Sum of transactions could not be calculated");
		$row = DB::fetch_row($res);
		return $row[0];
	}

	function is_overdue($row)
	{
		return Dates::date_diff2(Dates::Today(), Dates::sql2date($row["due_date"]), "d") > 0;
	}

	function edit_link($row)
	{
		//return $row["closed"] ?  '' :
		//	DB_Pager::link(_("Edit"),
		//		"/dimensions/dimension_entry.php?trans_no=" . $row["id"], ICON_EDIT);
		return DB_Pager::link(
			_("Edit"),
		 "/dimensions/dimension_entry.php?trans_no=" . $row["id"], ICON_EDIT
		);
	}

	$sql
	 = "SELECT dim.id,
	dim.reference,
	dim.name,
	dim.type_,
	dim.date_,
	dim.due_date,
	dim.closed
	FROM dimensions as dim WHERE id > 0";
	if (isset($_POST['OrderNumber']) && $_POST['OrderNumber'] != "") {
		$sql .= " AND reference LIKE " . DB::escape("%" . $_POST['OrderNumber'] . "%",false,false);
	} else {
		if ($dim == 1) {
			$sql .= " AND type_=1";
		}
		if (isset($_POST['OpenOnly'])) {
			$sql .= " AND closed=0";
		}
		if (isset($_POST['type_']) && ($_POST['type_'] > 0)) {
			$sql .= " AND type_=" . DB::escape($_POST['type_'],false,false);
		}
		if (isset($_POST['OverdueOnly'])) {
			$today = Dates::date2sql(Dates::Today());
			$sql .= " AND due_date < '$today'";
		}
		$sql .= " AND date_ >= '" . Dates::date2sql($_POST['FromDate']) . "'
		AND date_ <= '" . Dates::date2sql($_POST['ToDate']) . "'";
	}
	$cols = array(
		_("#") => array('fun' => 'view_link'),
		_("Reference"),
		_("Name"),
		_("Type"),
		_("Date") => 'date',
		_("Due Date") => array(
			'name' => 'due_date',
			'type' => 'date',
			'ord' => 'asc'
		),
		_("Closed") => array('fun' => 'is_closed'),
		_("Balance") => array(
			'type' => 'amount',
			'insert' => true,
			'fun' => 'sum_dimension'
		),
		array(
			'insert' => true,
			'fun' => 'edit_link'
		)
	);
	if ($outstanding_only) {
		$cols[_("Closed")] = 'skip';
	}
	$table =& db_pager::new_db_pager('dim_tbl', $sql, $cols);
	$table->set_marker('is_overdue', _("Marked dimensions are overdue."));
	$table->width = "80%";
	DB_Pager::display($table);
	Display::end_form();
	end_page();

?>
