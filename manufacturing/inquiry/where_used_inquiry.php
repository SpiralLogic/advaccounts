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
	$page_security = 'SA_WORKORDERANALYTIC';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Inventory Item Where Used Inquiry"));
	Validation::check(Validation::STOCK_ITEMS, _("There are no items defined in the system."));
	start_form(false);
	if (!Input::post('stock_id')) {
		$_POST['stock_id'] = Session::i()->global_stock_id;
	}
	echo "<div class='center'>" . _("Select an item to display its parent item(s).") . "&nbsp;";
	echo Item::select('stock_id', $_POST['stock_id'], false, true);
	echo "<hr></div>";
	Session::i()->global_stock_id = $_POST['stock_id'];
	function select_link($row) {
		return DB_Pager::link($row["parent"] . " - " . $row["description"],
		 "/manufacturing/manage/bom_edit.php?stock_id=" . $row["parent"]);
	}

	$sql
	 = "SELECT
		bom.parent,
		workcentre.name As WorkCentreName,
		location.location_name,
		bom.quantity,
		parent.description
		FROM bom as bom, stock_master as parent, workcentres as workcentre, locations as location
		WHERE bom.parent = parent.stock_id 
			AND bom.workcentre_added = workcentre.id
			AND bom.loc_code = location.loc_code
			AND bom.component=" . DB::escape($_POST['stock_id'], false, false);
	$cols = array(
		_("Parent Item") => array('fun' => 'select_link'),
		_("Work Centre"),
		_("Location"),
		_("Quantity Required")
	);
	$table =& db_pager::new_db_pager('usage_table', $sql, $cols);
	$table->width = "80%";
	DB_Pager::display($table);
	end_form();
	Renderer::end_page();

?>