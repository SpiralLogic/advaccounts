<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:49 PM
	 * To change this template use File | Settings | File Templates.
	 */
	//  SUPPLIERS
	function supplier_list($name, $selected_id = null, $spec_option = false, $submit_on_change = false, $all = false, $editkey = false) {
		$sql = "SELECT supplier_id, supp_ref, curr_code, inactive FROM suppliers ";
		$mode = DB_Company::get_pref('no_supplier_list');
		if ($editkey) {
			Display::set_editor('supplier', $name, $editkey);
		}
		return combo_input($name, $selected_id, $sql, 'supplier_id', 'supp_name', array(
																																									 'format' => '_format_add_curr', 'order' => array('supp_ref'), 'search_box' => $mode != 0, 'type' => 1, 'spec_option' => $spec_option === true ?
			 _("All Suppliers") : $spec_option, 'spec_id' => ALL_TEXT, 'select_submit' => $submit_on_change, 'async' => false, 'sel_hint' => $mode ?
			 _('Press Space tab to filter by name fragment') : _('Select supplier'), 'show_inactive' => $all));
	}

	function supplier_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false) {
		if ($label != null) {
			echo "<td class='label'>$label</td><td>\n";
		}
		echo supplier_list($name, $selected_id, $all_option, $submit_on_change, $all, $editkey);
		echo "</td>\n";
	}

	function supplier_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false) {
		echo "<tr><td class='label' name='supplier_name'>$label</td><td>";
		echo supplier_list($name, $selected_id, $all_option, $submit_on_change, $all, $editkey);
		echo "</td></tr>\n";
	}

	function supplier_credit_row($supplier, $credit, $parms = '') {
		label_row(_("Current Credit:"), "<a target='_blank' " . ($credit < 0 ? 'class="redfg openWindow"' :
		 '') . "href='/purchases/inquiry/supplier_inquiry.php?supplier_id=" . $supplier . "' >" . Num::price_format($credit) . "</a>", $parms);
	}

	function supp_allocations_list_cell($name, $selected = null) {
		echo "<td>\n";
		$allocs = array(
			ALL_TEXT => _("All Types"), '1' => _("Invoices"), '2' => _("Overdue Invoices"), '6' => _("Unpaid Invoices"), '3' => _("Payments"), '4' => _("Credit Notes"), '5' => _("Overdue Credit Notes"));
		echo array_selector($name, $selected, $allocs);
		echo "</td>\n";
	}

	function get_supplier_trans_view_str($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
		$viewer = "purchases/view/";
		switch ($type) {
			case ST_PURCHORDER:
				$viewer .= "view_po.php";
				break;
			case ST_SUPPINVOICE:
				$viewer .= "view_supp_invoice.php";
				break;
			case ST_SUPPCREDIT:
				$viewer .= "view_supp_credit.php";
				break;
			case ST_SUPPAYMENT:
				$viewer .= "view_supp_payment.php";
				break;
			case ST_SUPPRECEIVE:
				$viewer .= "view_grn.php";
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
