<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:53 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Sales_UI
	{
		// SALES PERSONS
		public static function	persons($name, $selected_id = null, $spec_opt = false) {
			$sql = "SELECT salesman_code, salesman_name, inactive FROM salesman";
			return combo_input($name, $selected_id, $sql, 'salesman_code', 'salesman_name', array(
																																													 'order' => array('salesman_name'), 'spec_option' => $spec_opt, 'spec_id' => ALL_NUMERIC));
		}

		public static function	persons_cells($label, $name, $selected_id = null, $spec_opt = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>\n";
			echo Sales_UI::persons($name, $selected_id, $spec_opt);
			echo "</td>\n";
		}

		public static function	persons_row($label, $name, $selected_id = null, $spec_opt = false) {
			echo "<tr><td class='label'>$label</td>";
			Sales_UI::persons_cells(null, $name, $selected_id, $spec_opt);
			echo "</tr>\n";
		}

		// SALES AREA
		public static function	areas($name, $selected_id = null) {
			$sql = "SELECT area_code, description, inactive FROM areas";
			return combo_input($name, $selected_id, $sql, 'area_code', 'description', array());
		}

		public static function	areas_cells($label, $name, $selected_id = null) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Sales_UI::areas($name, $selected_id);
			echo "</td>\n";
		}

		public static function	areas_row($label, $name, $selected_id = null) {
			echo "<tr><td class='label'>$label</td>";
			Sales_UI::areas_cells(null, $name, $selected_id);
			echo "</tr>\n";
		}

		public static function	groups($name, $selected_id = null, $special_option = false) {
			$sql = "SELECT id, description, inactive FROM groups";
			return combo_input($name, $selected_id, $sql, 'id', 'description', array(
																																							'spec_option' => $special_option === true ? ' ' :
																																							 $special_option, 'order' => 'description', 'spec_id' => 0,));
		}

		public static function	groups_cells($label, $name, $selected_id = null, $special_option = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Sales_UI::groups($name, $selected_id, $special_option);
			echo "</td>\n";
		}

		public static function	groups_row($label, $name, $selected_id = null, $special_option = false) {
			echo "<tr><td class='label'>$label</td>";
			Sales_UI::groups_cells(null, $name, $selected_id, $special_option);
			echo "</tr>\n";
		}

		// SALES TYPES
		public static function	types($name, $selected_id = null, $submit_on_change = false, $special_option = false) {
			$sql = "SELECT id, sales_type, inactive FROM sales_types";
			return combo_input($name, $selected_id, $sql, 'id', 'sales_type', array(
																																						 'spec_option' => $special_option === true ? _("All Sales Types") :
																																							$special_option, 'spec_id' => 0, 'select_submit' => $submit_on_change, //	  'async' => false,
																																				));
		}

		public static function	types_cells($label, $name, $selected_id = null, $submit_on_change = false, $special_option = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Sales_UI::types($name, $selected_id, $submit_on_change, $special_option);
			echo "</td>\n";
		}

		public static function	types_row($label, $name, $selected_id = null, $submit_on_change = false, $special_option = false) {
			echo "<tr><td class='label'>$label</td>";
			Sales_UI::types_cells(null, $name, $selected_id, $submit_on_change, $special_option);
			echo "</tr>\n";
		}

		public static function	shippers($name, $selected_id = null) {
			$sql = "SELECT shipper_id, shipper_name, inactive FROM shippers";
			return combo_input($name, $selected_id, $sql, 'shipper_id', 'shipper_name', array('order' => array('shipper_name')));
		}

		public static function	shippers_cells($label, $name, $selected_id = null) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Sales_UI::shippers($name, $selected_id);
			echo "</td>\n";
		}

		public static function	shippers_row($label, $name, $selected_id = null) {
			echo "<tr><td class='label'>$label</td>";
			Sales_UI::shippers_cells(null, $name, $selected_id);
			echo "</tr>\n";
		}

		public static function	policy_cells($label, $name, $selected = null) {
			if ($label != null) {
				label_cell($label);
			}
			echo "<td>\n";
			echo array_selector($name, $selected, array(
																								 '' => _("Automatically put balance on back order"), 'CAN' => _("Cancel any quantites not delivered")));
			echo "</td>\n";
		}

		public static function	policy_row($label, $name, $selected = null) {
			echo "<tr><td class='label'>$label</td>";
			Sales_UI::policy_cells(null, $name, $selected);
			echo "</tr>\n";
		}

	// TEMPLATES


		public static function templates($name, $selected_id = null, $special_option = false) {
			$sql = "SELECT sorder.order_no,	Sum(line.unit_price*line.quantity*(1-line.discount_percent)) AS OrderValue
				FROM sales_orders as sorder, sales_order_details as line
				WHERE sorder.order_no = line.order_no AND sorder.type = 1 GROUP BY line.order_no";
			return combo_input($name, $selected_id, $sql, 'order_no', 'OrderValue', array(
																																									 'format' => '_format_template_items', 'spec_option' => $special_option === true ?
				 ' ' : $special_option, 'order' => 'order_no', 'spec_id' => 0,));
		}

		public static function templates_cells($label, $name, $selected_id = null, $special_option = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Sales_UI::templates($name, $selected_id, $special_option);
			echo "</td>\n";
		}

		public static function templates_row($label, $name, $selected_id = null, $special_option = false) {
			echo "<tr><td class='label'>$label</td>";
			Sales_UI::templates_cells(null, $name, $selected_id, $special_option);
			echo "</tr>\n";
		}
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
		public static function  items($name, $selected_id = null, $all_option = false, $submit_on_change = false, $type = '', $opts = array(), $legacy = false) {
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

		public static function  items_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $opts) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo Sales_UI::items($name, $selected_id, $all_option, $submit_on_change, '', array_merge(array(
																																																			'cells' => true, 'description' => ''), $opts));
		}

		public static function  kits($name, $selected_id = null, $all_option = false, $submit_on_change = false, $legacy = true) {
			return Sales_UI::items($name, $selected_id, $all_option, $submit_on_change, 'kits', array('cells' => false), $legacy);
		}

		public static function  local_items_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $legacy = true) {
			echo "<tr>";
			if ($label != null) {
				echo "<td class='label'>$label</td>\n<td>";
			}
			echo Sales_UI::items($name, $selected_id, $all_option, $submit_on_change, 'local', array('cells' => false), $legacy);
			echo "</td></tr>";
		}

	}