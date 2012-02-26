<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:53 PM
	 * To change this template use File | Settings | File Templates.
	 */
	// STOCK ITEMS
class Item_UI {
	static public function manufactured($name, $selected_id = null, $all_option = false, $submit_on_change = false) {
		return Item::select($name, $selected_id, $all_option, $submit_on_change, array('where' => array("mb_flag= '" . STOCK_MANUFACTURE . "'")));
	}

	static public function manufactured_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo Item_UI::manufactured($name, $selected_id, $all_option, $submit_on_change, array('cells' => true));
		echo "\n";
	}

	static public function manufactured_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		Item_UI::manufactured_cells(null, $name, $selected_id, $all_option, $submit_on_change);
		echo "</tr>\n";
	}

	static public function component($name, $parent_stock_id, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false) {
		return Item::select($name, $selected_id, $all_option, $submit_on_change, array('where' => " stock_id != '$parent_stock_id' "));
	}

	static public function component_cells($label, $name, $parent_stock_id, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo Item::select($name, $selected_id, $all_option, $submit_on_change, array(
																																										'where' => "stock_id != '$parent_stock_id'", 'cells' => true));
	}

	static public function costable($name, $selected_id = null, $all_option = false, $submit_on_change = false) {
		return Item::select($name, $selected_id, $all_option, $submit_on_change, array('where' => "mb_flag!='" . STOCK_SERVICE . "'"));
	}

	static public function costable_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo Item::select($name, $selected_id, $all_option, $submit_on_change, array(
																																										'where' => "mb_flag!='" . STOCK_SERVICE . "'", 'cells' => true, 'description' => ''));
	}

	static public function type_row($label, $name, $selected_id = null, $enabled = true) {
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
	static public function type($name, $selected_id = null, $enabled = true) {
		global $stock_types;
		return array_selector($name, $selected_id, $stock_types, array(
																																'select_submit' => true, 'disabled' => !$enabled));
	}

	static public function trans_view($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
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

	static public function status($stock_id, $description = null, $echo = true) {
		if ($description) //Display::link_params_separate( "/inventory/inquiry/stock_status.php", (User::show_codes()?$stock_id . " - ":"") . $description, "stock_id=$stock_id");
		{
			$preview_str = "<a class='openWindow' target='_blank' href='/inventory/inquiry/stock_status.php?stock_id=$stock_id' >" . (User::show_codes()
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

	static public function status_cell($stock_id, $description = null) {
		echo "<td>";
		Item_UI::status($stock_id, $description);
		echo "</td>";
	}
}