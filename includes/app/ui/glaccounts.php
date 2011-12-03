<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:51 PM
	 * To change this template use File | Settings | File Templates.
	 */
	function gl_account_types_list($name, $selected_id = null, $all_option = false, $all_option_numeric = true) {
		$sql = "SELECT id, name FROM chart_types";
		return combo_input($name, $selected_id, $sql, 'id', 'name', array(
																																		 'order' => 'id', 'spec_option' => $all_option, 'spec_id' => $all_option_numeric ?
			 0 : ALL_TEXT));
	}

	function gl_account_types_list_cells($label, $name, $selected_id = null, $all_option = false, $all_option_numeric = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo gl_account_types_list($name, $selected_id, $all_option, $all_option_numeric);
		echo "</td>\n";
	}

	function gl_account_types_list_row($label, $name, $selected_id = null, $all_option = false, $all_option_numeric = false) {
		echo "<tr><td class='label'>$label</td>";
		gl_account_types_list_cells(null, $name, $selected_id, $all_option, $all_option_numeric);
		echo "</tr>\n";
	}

	function gl_all_accounts_list($name, $selected_id = null, $skip_bank_accounts = false, $cells = false, $all_option = false, $submit_on_change = false, $all = false) {
		if ($skip_bank_accounts) {
			$sql = "SELECT chart.account_code, chart.account_name, type.name, chart.inactive, type.id
				FROM (chart_master chart,chart_types type) LEFT JOIN bank_accounts acc ON chart.account_code=acc.account_code
					WHERE acc.account_code  IS NULL
				AND chart.account_type=type.id";
		} else {
			$sql = "SELECT chart.account_code, chart.account_name, type.name, chart.inactive, type.id
				FROM chart_master chart,chart_types type
				WHERE chart.account_type=type.id";
		}
		return combo_input($name, $selected_id, $sql, 'chart.account_code', 'chart.account_name', array(
																																																	 'format' => '_format_account', 'type' => 2, 'spec_option' => $all_option === true ?
			 _("Use Item Sales Accounts") :
			 $all_option, 'spec_id' => '', 'order' => array('type.id', 'account_code'), 'search_box' => $cells, 'search_submit' => false, 'size' => 12, 'max' => 10, 'cells' => true, 'select_submit' => $submit_on_change, 'async' => false, 'category' => 2, 'show_inactive' => $all));
	}


	function gl_all_accounts_list_cells($label, $name, $selected_id = null, $skip_bank_accounts = false, $cells = false, $all_option = false, $submit_on_change = false, $all = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo gl_all_accounts_list($name, $selected_id, $skip_bank_accounts, $cells, $all_option, $submit_on_change, $all);
		echo "</td>\n";
	}

	function gl_all_accounts_list_row($label, $name, $selected_id = null, $skip_bank_accounts = false, $cells = false, $all_option = false) {
		echo "<tr><td class='label'>$label</td>";
		gl_all_accounts_list_cells(null, $name, $selected_id, $skip_bank_accounts, $cells, $all_option);
		echo "</tr>\n";
	}

	function class_list($name, $selected_id = null, $submit_on_change = false) {
		$sql = "SELECT cid, class_name FROM chart_class";
		return combo_input($name, $selected_id, $sql, 'cid', 'class_name', array(
																																						'select_submit' => $submit_on_change, 'async' => false));
	}

	function class_list_cells($label, $name, $selected_id = null, $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo class_list($name, $selected_id, $submit_on_change);
		echo "</td>\n";
	}

	function class_list_row($label, $name, $selected_id = null, $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		class_list_cells(null, $name, $selected_id, $submit_on_change);
		echo "</tr>\n";
	}

	function journal_types_list_cells($label, $name, $value = null, $submit_on_change = false) {
		global $systypes_array;
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		$items = $systypes_array;
		// exclude quotes, orders and dimensions
		foreach (array(ST_PURCHORDER, ST_WORKORDER, ST_SALESORDER, ST_DIMENSION, ST_SALESQUOTE) as $excl) {
			unset($items[$excl]);
		}
		echo array_selector($name, $value, $items, array(
																										'spec_option' => _("All"), 'spec_id' => -1, 'select_submit' => $submit_on_change, 'async' => false));
		echo "</td>\n";
	}

	function get_gl_view_str($type, $trans_no, $label = "", $force = false, $class = '', $id = '') {
		if (!$force && !User::show_gl_info()) {
			return "";
		}
		$icon = false;
		if ($label == "") {
			$label = _("GL");
			$icon = ICON_GL;
		}
		return Display::viewer_link($label, "gl/view/gl_trans_view.php?type_id=$type&trans_no=$trans_no", $class, $id, $icon);
	}

	function get_gl_view_str_cell($type, $trans_no, $label = "") {
		$str = get_gl_view_str($type, $trans_no, $label);
		if ($str != "") {
			return "<td>$str</td>";
		}
		return $str;
	}

	function get_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
		$view_str = Debtor_UI::trans_view($type, $trans_no, $label, $icon, $class, $id);
		if ($view_str != null) {
			return $view_str;
		}
		$view_str = get_supplier_trans_view_str($type, $trans_no, $label, $icon, $class, $id);
		if ($view_str != null) {
			return $view_str;
		}
		$view_str = Bank_UI::trans_view($type, $trans_no, $label, $icon, $class, $id);
		if ($view_str != null) {
			return $view_str;
		}
		$view_str = get_inventory_trans_view_str($type, $trans_no, $label, $icon, $class, $id);
		if ($view_str != null) {
			return $view_str;
		}
		$view_str = get_manufacturing_trans_view_str($type, $trans_no, $label, $icon, $class, $id);
		if ($view_str != null) {
			return $view_str;
		}
		$view_str = get_dimensions_trans_view_str($type, $trans_no, $label, $icon, $class, $id);
		if ($view_str != null) {
			return $view_str;
		}
		$view_str = get_journal_trans_view_str($type, $trans_no, $label, $icon, $class, $id);
		if ($view_str != null) {
			return $view_str;
		}
		return null;
	}

	function class_types_list_row($label, $name, $selected_id = null, $submit_on_change = false) {
		global $class_types;
		echo "<tr><td class='label'>$label</td><td>";
		echo array_selector($name, $selected_id, $class_types, array('select_submit' => $submit_on_change));
		echo "</td></tr>\n";
	}

	function get_journal_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
		if ($type == ST_JOURNAL || $type == ST_COSTUPDATE) {
			$viewer = "gl/view/gl_trans_view.php?type_id=$type&trans_no=$trans_no";
		}
		else {
			return null;
		}
		if ($label == "") {
			$label = $trans_no;
		}
		return Display::viewer_link($label, $viewer, $class, $id, $icon);
	}
