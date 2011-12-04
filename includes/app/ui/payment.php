<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 1:52 PM
	 * To change this template use File | Settings | File Templates.
	 */
	function payment_methods_list($name, $selected_id = null) {
		$result = DB::select('name')->from('payment_methods')->where('inactive=', 0);
		while ($row = DB::fetch($result)) {
			$payment_methods[] = $row['name'];
		}
		return array_selector($name, $selected_id, $payment_methods);
	}

	function payment_methods_list_cells($label, $name, $selected_id = null) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo payment_methods_list($name, $selected_id);
		echo "</td>\n";
	}

	function payment_methods_list_row($label, $name, $selected_id = null) {
		echo "<tr><td class='label'>$label</td>";
		Bank_UI::accounts_type_cells(null, $name, $selected_id);
		echo "</tr>\n";
	}

	function payment_person_types_list($name, $selected_id = null, $submit_on_change = false) {
		global $payment_person_types;
		$items = array();
		foreach ($payment_person_types as $key => $type) {
			if ($key != PT_WORKORDER) {
				$items[$key] = $type;
			}
		}
		return array_selector($name, $selected_id, $items, array('select_submit' => $submit_on_change));
	}

	function payment_person_types_list_cells($label, $name, $selected_id = null, $related = null) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo payment_person_types_list($name, $selected_id, $related);
		echo "</td>\n";
	}

	function payment_person_types_list_row($label, $name, $selected_id = null, $related = null) {
		echo "<tr><td class='label'>$label</td>";
		payment_person_types_list_cells(null, $name, $selected_id, $related);
		echo "</tr>\n";
	}

	//  CURRENCIES
	function currencies_list($name, $selected_id = null, $submit_on_change = false) {
		$sql = "SELECT curr_abrev, currency, inactive FROM currencies";
		// default to the company currency
		return combo_input($name, $selected_id, $sql, 'curr_abrev', 'currency', array(
																																								 'select_submit' => $submit_on_change, 'default' => Banking::get_company_currency(), 'async' => false));
	}

	function currencies_list_cells($label, $name, $selected_id = null, $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo currencies_list($name, $selected_id, $submit_on_change);
		echo "</td>\n";
	}

	function currencies_list_row($label, $name, $selected_id = null, $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		currencies_list_cells(null, $name, $selected_id, $submit_on_change);
		echo "</tr>\n";
	}

	// PAYMENT TERMS
	function payment_terms_list($name, $selected_id = null, $disabled = null) {
		if ($disabled === null) {
			$disabled = (!$_SESSION['current_user']->can_access('SA_CUSTOMER_CREDIT'));
		}
		$sql = "SELECT terms_indicator, terms, inactive FROM payment_terms";
		return combo_input($name, $selected_id, $sql, 'terms_indicator', 'terms_indicator', array('disabled' => $disabled));
	}

	function payment_terms_list_cells($label, $name, $selected_id = null, $disabled = null) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo payment_terms_list($name, $selected_id, $disabled);
		echo "</td>\n";
	}

	function payment_terms_list_row($label, $name, $selected_id = null, $disabled = null) {
		echo "<tr><td class='label'>$label</td>";
		payment_terms_list_cells(null, $name, $selected_id, $disabled);
		echo "</tr>\n";
	}

	function pos_list_row($label, $name, $selected_id = null, $spec_option = false, $submit_on_change = false) {
		$sql = "SELECT id, pos_name, inactive FROM sales_pos";
		JS::default_focus($name);
		echo '<tr>';
		if ($label != null) {
			echo "<td class='label'>$label</td>\n";
		}
		echo "<td>";
		echo combo_input($name, $selected_id, $sql, 'id', 'pos_name', array(
																																			 'select_submit' => $submit_on_change, 'async' => true, 'spec_option' => $spec_option, 'spec_id' => -1, 'order' => array('pos_name')));
		echo "</td></tr>\n";
	}

	function sale_payment_list_cells($label, $name, $selected_id = null, $submit_on_change = false) {
		if ($label != null) {
			echo "<td class='label'>$label</td>\n";
		}
		echo "<td>";
		echo yesno_list($name, $selected_id, _('Cash'), _('Delayed'), $submit_on_change);
		echo "</td>\n";
	}
