<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:53 PM
	 * To change this template use File | Settings | File Templates.
	 */
	//  STOCK ITEMS
	function stock_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false, $opts = array(), $editkey = false, $legacy = false) {
		if (!$legacy) {
			return Item::addSearchBox($name, array_merge(array(
																												'submitonselect' => $submit_on_change, 'selected' => $selected_id, 'purchase' => true, 'cells' => true),
				$opts));
		}
		$sql = "SELECT stock_id, s.description, c.description, s.inactive, s.editable, s.long_description
				FROM stock_master s,stock_category c WHERE s.category_id=c.category_id";
		if ($editkey) {
			Display::set_editor('item', $name, $editkey);
		}
		return combo_input($name, $selected_id, $sql, 'stock_id', 's.description', array_merge(array(
																																																'format' => '_format_stock_items', 'spec_option' => $all_option === true ?
				 _("All Items") :
				 $all_option, 'spec_id' => ALL_TEXT, 'search_box' => false, 'search' => array("stock_id", "c.description", "s.description"), 'search_submit' => DB_Company::get_pref('no_item_list') != 0, 'size' => 10, 'select_submit' => $submit_on_change, 'category' => 2, 'order' => array('c.description', 'stock_id'), 'editable' => 30, 'max' => 50),
			$opts));
	}


	function stock_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false, $legacy = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
																																										'submitonselect' => $submit_on_change, 'cells' => true, 'purchase' => false, 'show_inactive' => $all, 'editable' => $editkey),
			$editkey, $legacy);
	}

	// MANUFACTURED ITEMS
	function stock_manufactured_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false) {
		return stock_items_list($name, $selected_id, $all_option, $submit_on_change, array('where' => array("mb_flag= '" . STOCK_MANUFACTURE . "'")));
	}

	function stock_manufactured_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo stock_manufactured_items_list($name, $selected_id, $all_option, $submit_on_change, array('cells' => true));
		echo "\n";
	}

	function stock_manufactured_items_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		stock_manufactured_items_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
		echo "</tr>\n";
	}

	function stock_component_items_list($name, $parent_stock_id, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false) {
		return stock_items_list($name, $selected_id, $all_option, $submit_on_change, array('where' => " stock_id != '$parent_stock_id' "));
	}

	function stock_component_items_list_cells($label, $name, $parent_stock_id, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
																																										'where' => "stock_id != '$parent_stock_id'", 'cells' => true));
	}

	function stock_costable_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false) {
		return stock_items_list($name, $selected_id, $all_option, $submit_on_change, array('where' => "mb_flag!='" . STOCK_SERVICE . "'"));
	}

	function stock_costable_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
																																										'where' => "mb_flag!='" . STOCK_SERVICE . "'", 'cells' => true, 'description' => ''));
	}

	// STOCK PURCHASEABLE
	function stock_purchasable_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false, $legacy = false) {
		return stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
																																											'where' => "mb_flag!= '" . STOCK_MANUFACTURE . "'", 'show_inactive' => $all, 'editable' => false),
			false, $legacy);
	}

	function stock_purchasable_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
																																										'where' => "mb_flag!= '" . STOCK_MANUFACTURE . "'", 'editable' => 30, 'cells' => true, 'description' => ''));
	}

	function stock_purchasable_items_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false) {
		echo "<tr><td class='label'>$label</td>";
		stock_purchasable_items_list_cells(null, $name, $selected_id = null, $all_option, $submit_on_change, $editkey);
		echo "</tr>\n";
	}

	function stock_item_types_list_row($label, $name, $selected_id = null, $enabled = true) {
		global $stock_types;
		echo "<tr>";
		if ($label != null) {
			echo "<td class='label'>$label</td>\n";
		}
		echo "<td>";
		echo array_selector($name, $selected_id, $stock_types, array(
																																'select_submit' => true, 'disabled' => !$enabled));
		echo "</td></tr>\n";
	}

	// STOCK UNITS
	function stock_units_list_row($label, $name, $value = null, $enabled = true) {
		$result = Item_Unit::get_all();
		echo "<tr>";
		if ($label != null) {
			echo "<td class='label'>$label</td>\n";
		}
		echo "<td>";
		while ($unit = DB::fetch($result)) {
			$units[$unit['abbr']] = $unit['name'];
		}
		echo array_selector($name, $value, $units, array('disabled' => !$enabled));
		echo "</td></tr>\n";
	}

	function stock_categories_list($name, $selected_id = null, $spec_opt = false, $submit_on_change = false) {
		$sql = "SELECT category_id, description, inactive FROM stock_category";
		return combo_input($name, $selected_id, $sql, 'category_id', 'description', array(
																																										 'order' => 'category_id', 'spec_option' => $spec_opt, 'spec_id' => -1, 'select_submit' => $submit_on_change, 'async' => true));
	}

	function stock_categories_list_cells($label, $name, $selected_id = null, $spec_opt = false, $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo stock_categories_list($name, $selected_id, $spec_opt, $submit_on_change);
		echo "</td>\n";
	}

	function stock_categories_list_row($label, $name, $selected_id = null, $spec_opt = false, $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		stock_categories_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
		echo "</tr>\n";
	}

	function get_inventory_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
		$viewer = "inventory/view/";
		switch ($type) {
			case ST_INVADJUST:
				$viewer .= "view_adjustment.php";
				break;
			case ST_LOCTRANSFER:
				$viewer .= "view_transfer.php";
				break;
			default:
				return null;
		}
		$viewer .= "?trans_no=$trans_no";
		if ($label == "") {
			$label = $trans_no;
		}
		return Display::viewer_link($label, $viewer, $class, $id, $icon);
	}

	function stock_status($stock_id, $description = null, $echo = true) {
		if ($description) //Display::link_params_separate( "/inventory/inquiry/stock_status.php", (User::show_codes()?$stock_id . " - ":"") . $description, "stock_id=$stock_id");
		{
			$preview_str = "<a class='openWindow'  target='_blank' href='/inventory/inquiry/stock_status.php?stock_id=$stock_id' >" . (User::show_codes()
			 ? $stock_id . " - " : "") . $description . "</a>";
		}
		else //Display::link_params_separate( "/inventory/inquiry/stock_status.php", $stock_id, "stock_id=$stock_id");
		{
			$preview_str = "<a class='openWindow' target='_blank' href='/inventory/inquiry/stock_status.php?stock_id=$stock_id' >$stock_id</a>";
		}
		if ($echo) {
			echo $preview_str;
		}
		return $preview_str;
	}

	function stock_status_cell($stock_id, $description = null) {
		echo "<td>";
		stock_status($stock_id, $description);
		echo "</td>";
	}

	function locations_list($name, $selected_id = null, $all_option = false, $submit_on_change = false) {
		$sql = "SELECT loc_code, location_name, inactive FROM locations";
		return combo_input($name, $selected_id, $sql, 'loc_code', 'location_name', array(
																																										'spec_option' => $all_option === true ? _("All Locations") :
																																										 $all_option, 'spec_id' => ALL_TEXT, 'select_submit' => $submit_on_change));
	}

	function locations_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo locations_list($name, $selected_id, $all_option, $submit_on_change);
		echo "</td>\n";
	}

	function locations_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		locations_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
		echo "</tr>\n";
	}
