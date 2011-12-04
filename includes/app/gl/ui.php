<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:51 PM
	 * To change this template use File | Settings | File Templates.
	 */
	class GL_UI
	{
		public static function	types($name, $selected_id = null, $all_option = false, $all_option_numeric = true) {
			$sql = "SELECT id, name FROM chart_types";
			return combo_input($name, $selected_id, $sql, 'id', 'name', array(
																																			 'order' => 'id', 'spec_option' => $all_option, 'spec_id' => $all_option_numeric ?
				 0 : ALL_TEXT));
		}

		public static function	types_cells($label, $name, $selected_id = null, $all_option = false, $all_option_numeric = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo GL_UI::types($name, $selected_id, $all_option, $all_option_numeric);
			echo "</td>\n";
		}

		public static function	types_row($label, $name, $selected_id = null, $all_option = false, $all_option_numeric = false) {
			echo "<tr><td class='label'>$label</td>";
			GL_UI::types_cells(null, $name, $selected_id, $all_option, $all_option_numeric);
			echo "</tr>\n";
		}

		public static function	all($name, $selected_id = null, $skip_bank_accounts = false, $cells = false, $all_option = false, $submit_on_change = false, $all = false) {
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

		public static function	all_cells($label, $name, $selected_id = null, $skip_bank_accounts = false, $cells = false, $all_option = false, $submit_on_change = false, $all = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo GL_UI::all($name, $selected_id, $skip_bank_accounts, $cells, $all_option, $submit_on_change, $all);
			echo "</td>\n";
		}

		public static function	all_row($label, $name, $selected_id = null, $skip_bank_accounts = false, $cells = false, $all_option = false) {
			echo "<tr><td class='label'>$label</td>";
			GL_UI::all_cells(null, $name, $selected_id, $skip_bank_accounts, $cells, $all_option);
			echo "</tr>\n";
		}

		public static function	chart_class($name, $selected_id = null, $submit_on_change = false) {
			$sql = "SELECT cid, class_name FROM chart_class";
			return combo_input($name, $selected_id, $sql, 'cid', 'class_name', array(
																																							'select_submit' => $submit_on_change, 'async' => false));
		}

		public static function	chart_class_cells($label, $name, $selected_id = null, $submit_on_change = false) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo GL_UI::chart_class($name, $selected_id, $submit_on_change);
			echo "</td>\n";
		}

		public static function	chart_class_row($label, $name, $selected_id = null, $submit_on_change = false) {
			echo "<tr><td class='label'>$label</td>";
			GL_UI::chart_class_cells(null, $name, $selected_id, $submit_on_change);
			echo "</tr>\n";
		}

		public static function	journal_cells($label, $name, $value = null, $submit_on_change = false) {
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

		public static function	view($type, $trans_no, $label = "", $force = false, $class = '', $id = '') {
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

		public static function	view_cell($type, $trans_no, $label = "") {
			$str = GL_UI::view($type, $trans_no, $label);
			if ($str != "") {
				return "<td>$str</td>";
			}
			return $str;
		}

		public static function trans_view($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			$view_str = Debtor_UI::trans_view($type, $trans_no, $label, $icon, $class, $id);
			if ($view_str != null) {
				return $view_str;
			}
			$view_str = Purch_UI::trans_view($type, $trans_no, $label, $icon, $class, $id);
			if ($view_str != null) {
				return $view_str;
			}
			$view_str = Bank_UI::trans_view($type, $trans_no, $label, $icon, $class, $id);
			if ($view_str != null) {
				return $view_str;
			}
			$view_str = Item_UI::trans_view($type, $trans_no, $label, $icon, $class, $id);
			if ($view_str != null) {
				return $view_str;
			}
			$view_str = get_manufacturing_trans_view_str($type, $trans_no, $label, $icon, $class, $id);
			if ($view_str != null) {
				return $view_str;
			}
			$view_str = Dimensions::trans_view($type, $trans_no, $label, $icon, $class, $id);
			if ($view_str != null) {
				return $view_str;
			}
			$view_str = GL_UI::journal_trans($type, $trans_no, $label, $icon, $class, $id);
			if ($view_str != null) {
				return $view_str;
			}
			return null;
		}

		public static function	class_types_row($label, $name, $selected_id = null, $submit_on_change = false) {
			global $class_types;
			echo "<tr><td class='label'>$label</td><td>";
			echo array_selector($name, $selected_id, $class_types, array('select_submit' => $submit_on_change));
			echo "</td></tr>\n";
		}

		public static function	journal_trans($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
			if ($type == ST_JOURNAL || $type == ST_COSTUPDATE) {
				$viewer = "gl/view/gl_trans_view.php?type_id=$type&trans_no=$trans_no";
			} else {
				return null;
			}
			if ($label == "") {
				$label = $trans_no;
			}
			return Display::viewer_link($label, $viewer, $class, $id, $icon);
		}

		//  FISCALYEARS
		public static function fiscalyears($name, $selected_id = null, $submit_on_change = false) {
			$sql = "SELECT * FROM fiscal_year";
			// default to the company current fiscal year
			return combo_input($name, $selected_id, $sql, 'id', '', array(
																																	 'order' => 'begin', 'default' => DB_Company::get_pref('f_year'), 'format' => '_format_fiscalyears', 'select_submit' => $submit_on_change, 'async' => false));
		}

		public static function fiscalyears_cells($label, $name, $selected_id = null) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo GL_UI::fiscalyears($name, $selected_id);
			echo "</td>\n";
		}

		public static function fiscalyears_row($label, $name, $selected_id = null) {
			echo "<tr><td class='label'>$label</td>";
			GL_UI::fiscalyears_cells(null, $name, $selected_id);
			echo "</tr>\n";
		}

		public static function payment_person_type($name, $selected_id = null, $submit_on_change = false) {
			global $payment_person_types;
			$items = array();
			foreach ($payment_person_types as $key => $type) {
				if ($key != PT_WORKORDER) {
					$items[$key] = $type;
				}
			}
			return array_selector($name, $selected_id, $items, array('select_submit' => $submit_on_change));
		}

		public static function payment_person_type_cells($label, $name, $selected_id = null, $related = null) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo GL_UI::payment_person_type($name, $selected_id, $related);
			echo "</td>\n";
		}

		public static function payment_person_type_row($label, $name, $selected_id = null, $related = null) {
			echo "<tr><td class='label'>$label</td>";
			GL_UI::payment_person_type_cells(null, $name, $selected_id, $related);
			echo "</tr>\n";
		}

		// PAYMENT TERMS
		public static function payment_terms($name, $selected_id = null, $disabled = null) {
			if ($disabled === null) {
				$disabled = (!$_SESSION['current_user']->can_access('SA_CUSTOMER_CREDIT'));
			}
			$sql = "SELECT terms_indicator, terms, inactive FROM payment_terms";
			return combo_input($name, $selected_id, $sql, 'terms_indicator', 'terms_indicator', array('disabled' => $disabled));
		}

		public static function payment_terms_cells($label, $name, $selected_id = null, $disabled = null) {
			if ($label != null) {
				echo "<td>$label</td>\n";
			}
			echo "<td>";
			echo GL_UI::payment_terms($name, $selected_id, $disabled);
			echo "</td>\n";
		}

		public static function payment_terms_row($label, $name, $selected_id = null, $disabled = null) {
			echo "<tr><td class='label'>$label</td>";
			GL_UI::payment_terms_cells(null, $name, $selected_id, $disabled);
			echo "</tr>\n";
		}
	}