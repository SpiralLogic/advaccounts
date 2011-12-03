<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:50 PM
	 * To change this template use File | Settings | File Templates.
	 */
	// SALES ITEMS
	/**
	 *	Select item via foreign code.
	 *
	 * @param				$name
	 * @param null	 $selected_id
	 * @param bool	 $all_option
	 * @param bool	 $submit_on_change
	 * @param string $type
	 * @param array	$opts
	 * @param bool	 $legacy
	 *
	 * @return string|void
	 */
	function sales_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false, $type = '', $opts = array(), $legacy = false) {
		// all sales codes
		if (!$legacy) {
			return Item::addSearchBox($name, array_merge(array(
																												'selected' => $selected_id, 'type' => $type, 'cells' => true, 'sale' => true), $opts));
		}
		$where = ($type == 'local') ? " AND !i.is_foreign" : ' ';
		if ($type == 'kits') {
			$where .= " AND !i.is_foreign AND i.item_code!=i.stock_id ";
		}
		$sql = "SELECT i.item_code, i.description, c.description, count(*)>1 as kit,
				 i.inactive, if(count(*)>1, '0', s.editable) as editable, s.long_description
				FROM stock_master s, item_codes i LEFT JOIN stock_category c ON i.category_id=c.category_id
				WHERE i.stock_id=s.stock_id $where AND !i.inactive AND !s.inactive AND !s.no_sale GROUP BY i.item_code";
		return combo_input($name, $selected_id, $sql, 'i.item_code', 'c.description', array_merge(array(
																																																	 'format' => '_format_stock_items', 'spec_option' => $all_option === true ?
				 _("All Items") :
				 $all_option, 'spec_id' => ALL_TEXT, 'search_box' => true, 'search' => array("i.item_code", "c.description", "i.description"), 'search_submit' => DB_Company::get_pref('no_item_list') != 0, 'size' => 15, 'select_submit' => $submit_on_change, 'category' => 2, 'order' => array('c.description', 'i.item_code'), 'editable' => 30, 'max' => 50),
			$opts));
	}

	function sales_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $opts) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo sales_items_list($name, $selected_id, $all_option, $submit_on_change, '', array_merge(array(
																																																		'cells' => true, 'description' => ''), $opts));
	}

	function sales_kits_list($name, $selected_id = null, $all_option = false, $submit_on_change = false, $legacy = true) {
		return sales_items_list($name, $selected_id, $all_option, $submit_on_change, 'kits', array('cells' => false), $legacy);
	}

	function sales_local_items_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $legacy = true) {
		echo "<tr>";
		if ($label != null) {
			echo "<td class='label'>$label</td>\n<td>";
		}
		echo sales_items_list($name, $selected_id, $all_option, $submit_on_change, 'local', array('cells' => false), $legacy);
		echo "</td></tr>";
	}
