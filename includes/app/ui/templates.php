<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 2:00 PM
	 * To change this template use File | Settings | File Templates.
	 */
	// TEMPLATES


	function templates_list($name, $selected_id = null, $special_option = false) {
		$sql = "SELECT sorder.order_no,	Sum(line.unit_price*line.quantity*(1-line.discount_percent)) AS OrderValue
			FROM sales_orders as sorder, sales_order_details as line
			WHERE sorder.order_no = line.order_no AND sorder.type = 1 GROUP BY line.order_no";
		return combo_input($name, $selected_id, $sql, 'order_no', 'OrderValue', array(
																																								 'format' => '_format_template_items', 'spec_option' => $special_option === true ?
			 ' ' : $special_option, 'order' => 'order_no', 'spec_id' => 0,));
	}

	function templates_list_cells($label, $name, $selected_id = null, $special_option = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo templates_list($name, $selected_id, $special_option);
		echo "</td>\n";
	}

	function templates_list_row($label, $name, $selected_id = null, $special_option = false) {
		echo "<tr><td class='label'>$label</td>";
		templates_list_cells(null, $name, $selected_id, $special_option);
		echo "</tr>\n";
	}
