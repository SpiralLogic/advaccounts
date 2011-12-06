<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:49 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class Debtor_UI {
		// BRANCHES
		public static function branches_list($customer_id, $name, $selected_id = null, $spec_option = true, $enabled = true, $submit_on_change = false, $editkey = false) {
			$sql = "SELECT branch_code, branch_ref FROM cust_branch
			WHERE branch_ref <> 'accounts' AND debtor_no='" . $customer_id . "' ";
			if ($editkey) {
				Display::set_editor('branch', $name, $editkey);
			}
			$where = $enabled ? array("disable_trans = 0") : array();
			return select_box($name, $selected_id, $sql, 'branch_code', 'br_name', array(
				'where' => $where, 'order' => array('branch_ref'), 'spec_option' => $spec_option === true ?
				 _('All branches') : $spec_option, 'spec_id' => ALL_TEXT, 'select_submit' => $submit_on_change, 'sel_hint' => _('Select customer branch')));
		}

		public static function branches_list_cells($label, $customer_id, $name, $selected_id = null, $all_option = true, $enabled = true, $submit_on_change = false, $editkey = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo Debtor_UI::branches_list($customer_id, $name, $selected_id, $all_option, $enabled, $submit_on_change, $editkey);
			echo "</td>\n";
		}

		public static function branches_list_row($label, $customer_id, $name, $selected_id = null, $all_option = true, $enabled = true, $submit_on_change = false, $editkey = false) {
			echo "<tr><td class='label'>$label</td>";
			Debtor_UI::branches_list_cells(null, $customer_id, $name, $selected_id, $all_option, $enabled, $submit_on_change, $editkey);
			echo "</tr>";
		}

		// CUSTOMERS
		public static function credit_row($customer, $credit, $parms = '') {
			label_row(_("Current Credit:"), "<a target='_blank' " . ($credit < 0 ? 'class="redfg openWindow"' :
			 '') . "href='/sales/inquiry/customer_inquiry.php?frame=1&customer_id=" . $customer . "'" . " >" . Num::price_format($credit) . "</a>", $parms);
		}

		public static function select($name, $selected_id = null, $spec_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false, $async = false) {
			$sql = "SELECT debtor_no, debtor_ref, curr_code, inactive FROM debtors_master ";
			$mode = DB_Company::get_pref('no_customer_list');
			if ($editkey) {
				Display::set_editor('customer', $name, $editkey);
			}
			return select_box($name, $selected_id, $sql, 'debtor_no', 'name', array(
				'format' => '_format_add_curr', 'order' => array('debtor_ref'), 'search_box' => $mode != 0, 'type' => 1, 'size' => 20, 'spec_option' => $spec_option === true ?
				 _("All Customers") : $spec_option, 'spec_id' => ALL_TEXT, 'select_submit' => $submit_on_change, 'async' => $async, 'sel_hint' => $mode ?
				 _('Press Space tab to filter by name fragment; F2 - entry new customer') : _('Select customer'), 'show_inactive' => $show_inactive));
		}

		public static function cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false, $async = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td nowrap>";
			echo Debtor_UI::select($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey, $async);
			echo "</td>\n";
		}

		public static function select_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false) {
			echo "<tr><td id='customer_id_label' class='label pointer'>$label</td><td nowrap>";
			echo Debtor_UI::select($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey);
			echo "</td>\n</tr>\n";
		}

		public static function allocations_select($label, $name, $selected = null) {
			if ($label != null) {
				label_cell($label);
			}
			echo "<td>\n";
			$allocs = array(
				ALL_TEXT => _("All Types"), '1' => _("Sales Invoices"), '2' => _("Overdue Invoices"), '3' => _("Payments"), '4' => _("Credit Notes"), '5' => _("Delivery Notes"), '6' => _("Invoices Only"));
			echo array_selector($name, $selected, $allocs);
			echo "</td>\n";
		}

		public static function trans_view($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			$viewer = "sales/view/";
			switch ($type) {
				case ST_SALESINVOICE:
					$viewer .= "view_invoice.php";
					break;
				case ST_CUSTCREDIT:
					$viewer .= "view_credit.php";
					break;
				case ST_CUSTPAYMENT:
					$viewer .= "view_receipt.php";
					break;
				case ST_CUSTREFUND:
					$viewer .= "view_receipt.php";
					break;
				case ST_CUSTDELIVERY:
					$viewer .= "view_dispatch.php";
					break;
				case ST_SALESORDER:
				case ST_SALESQUOTE:
					$viewer .= "view_sales_order.php";
					break;
				default:
					return null;
			}
			if (!is_array($trans_no)) {
				$trans_no = array($trans_no);
			}
			$lbl = $label;
			$preview_str = '';
			foreach (
				$trans_no as $trans
			) {
				if ($label == "") {
					$lbl = $trans;
				}
				if ($preview_str != '') {
					$preview_str .= ',';
				}
				$preview_str .= Display::viewer_link($lbl, $viewer . "?trans_no=$trans&trans_type=$type", $class, $id, $icon);
			}
			return $preview_str;
		}
	}