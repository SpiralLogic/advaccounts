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
		JS::open_window(800, 500);
	if (isset($_GET['outstanding_only']) && ($_GET['outstanding_only'] == true)) {
		// curently outstanding simply means not closed
		$outstanding_only = 1;
		Page::start(_($help_context = "Search Outstanding Work Orders"),SA_MANUFTRANSVIEW);
	}
	else {
		$outstanding_only = 0;
		Page::start(_($help_context = "Search Work Orders"),SA_MANUFTRANSVIEW);
	}
	// Ajax updates
	//
	if (get_post('SearchOrders')) {
		Ajax::i()->activate('orders_tbl');
	}
	elseif (get_post('_OrderNumber_changed')) {
		$disable = get_post('OrderNumber') !== '';
		Ajax::i()->addDisable(true, 'StockLocation', $disable);
		Ajax::i()->addDisable(true, 'OverdueOnly', $disable);
		Ajax::i()->addDisable(true, 'OpenOnly', $disable);
		Ajax::i()->addDisable(true, 'SelectedStockItem', $disable);
		if ($disable) {
			JS::set_focus('OrderNumber');
		}
		else {
			JS::set_focus('StockLocation');
		}
		Ajax::i()->activate('orders_tbl');
	}
	if (isset($_GET["stock_id"])) {
		$_POST['SelectedStockItem'] = $_GET["stock_id"];
	}
	start_form(false, $_SERVER['PHP_SELF'] . "?outstanding_only=$outstanding_only");
	start_table('tablestyle_noborder');
	start_row();
	ref_cells(_("Reference:"), 'OrderNumber', '', null, '', true);
	Inv_Location::cells(_("at Location:"), 'StockLocation', null, true);
	check_cells(_("Only Overdue:"), 'OverdueOnly', null);
	if ($outstanding_only == 0) {
		check_cells(_("Only Open:"), 'OpenOnly', null);
	}
	Item_UI::manufactured_cells(_("for item:"), 'SelectedStockItem', null, true);
	submit_cells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
	end_row();
	end_table();
	function check_overdue($row) {
		return (!$row["closed"] && Dates::date_diff2(Dates::Today(), Dates::sql2date($row["required_by"]), "d") > 0);
	}

	function view_link($dummy, $order_no) {
		return GL_UI::trans_view(ST_WORKORDER, $order_no);
	}

	function view_stock($row) {
		return Item_UI::status($row["stock_id"], $row["description"], false);
	}

	function wo_type_name($dummy, $type) {
		global $wo_types_array;
		return $wo_types_array[$type];
	}

	function edit_link($row) {
		return $row['closed'] ? '<i>' . _('Closed') . '</i>' : DB_Pager::link(_("Edit"), "/manufacturing/work_order_entry.php?trans_no=" . $row["id"], ICON_EDIT);
	}

	function release_link($row) {
		return $row["closed"] ? '' : ($row["released"] == 0 ? DB_Pager::link(_('Release'), "/manufacturing/work_order_release.php?trans_no=" . $row["id"]) :
		 DB_Pager::link(_('Issue'), "/manufacturing/work_order_issue.php?trans_no=" . $row["id"]));
	}

	function produce_link($row) {
		return $row["closed"] || !$row["released"] ? '' : DB_Pager::link(_('Produce'), "/manufacturing/work_order_add_finished.php?trans_no=" . $row["id"]);
	}

	function costs_link($row) {
		/*
											 return $row["closed"] || !$row["released"] ? '' :
												 DB_Pager::link(_('Costs'),
													 "/gl/gl_bank.php?NewPayment=1&PayType="
													 .PT_WORKORDER. "&PayPerson=" .$row["id"]);
										 */
		return $row["closed"] || !$row["released"] ? '' : DB_Pager::link(_('Costs'), "/manufacturing/work_order_costs.php?trans_no=" . $row["id"]);
	}

	function view_gl_link($row) {
		if ($row['closed'] == 0) {
			return '';
		}
		return GL_UI::view(ST_WORKORDER, $row['id']);
	}

	function dec_amount($row, $amount) {
		return Num::format($amount, $row['decimals']);
	}

	$sql = "SELECT
	workorder.id,
	workorder.wo_ref,
	workorder.type,
	location.location_name,
	item.description,
	workorder.units_reqd,
	workorder.units_issued,
	workorder.date_,
	workorder.required_by,
	workorder.released_date,
	workorder.closed,
	workorder.released,
	workorder.stock_id,
	unit.decimals
	FROM workorders as workorder," . "stock_master as item," . "item_units as unit," . "locations as location
	WHERE workorder.stock_id=item.stock_id 
		AND workorder.loc_code=location.loc_code
		AND item.units=unit.abbr";
	if (check_value('OpenOnly') || $outstanding_only != 0) {
		$sql .= " AND workorder.closed=0";
	}
	if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) {
		$sql .= " AND workorder.loc_code=" . DB::quote($_POST['StockLocation']);
	}
	if (isset($_POST['OrderNumber']) && $_POST['OrderNumber'] != "") {
		$sql .= " AND workorder.wo_ref LIKE " . DB::quote('%' . $_POST['OrderNumber'] . '%');
	}
	if (isset($_POST['SelectedStockItem']) && $_POST['SelectedStockItem'] != ALL_TEXT) {
		$sql .= " AND workorder.stock_id=" . DB::quote($_POST['SelectedStockItem']);
	}
	if (check_value('OverdueOnly')) {
		$Today = Dates::date2sql(Dates::Today());
		$sql .= " AND workorder.required_by < '$Today' ";
	}
	$cols = array(
		_("#") => array('fun' => 'view_link'), _("Reference"), // viewlink 2 ?
		_("Type") => array('fun' => 'wo_type_name'), _("Location"), _("Item") => array('fun' => 'view_stock'), _("Required") => array(
			'fun' => 'dec_amount', 'align' => 'right'
		), _("Manufactured") => array(
			'fun' => 'dec_amount', 'align' => 'right'
		), _("Date") => 'date', _("Required By") => array(
			'type' => 'date', 'ord' => ''
		), array(
			'insert' => true, 'fun' => 'edit_link'
		), array(
			'insert' => true, 'fun' => 'release_link'
		), array(
			'insert' => true, 'fun' => 'produce_link'
		), array(
			'insert' => true, 'fun' => 'costs_link'
		), array(
			'insert' => true, 'fun' => 'view_gl_link'
		)
	);
	$table =& db_pager::new_db_pager('orders_tbl', $sql, $cols);
	$table->set_marker('check_overdue', _("Marked orders are overdue."));
	$table->width = "90%";
	DB_Pager::display($table);
	end_form();
	Page::end();
?>
