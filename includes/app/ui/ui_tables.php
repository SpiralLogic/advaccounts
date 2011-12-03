<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 3/12/11
	 * Time: 5:52 AM
	 * To change this template use File | Settings | File Templates.
	 */
	function amount_decimal_cell($label, $params = "", $id = null) {
		$dec = 0;
		label_cell(Num::price_decimal($label, $dec), "nowrap class=right " . $params, $id);
	}

	function amount_cell($label, $bold = false, $params = "", $id = null) {
		if ($bold) {
			label_cell("<b>" . Num::price_format($label) . "</b>", "class='amount'" . $params, $id);
		} else {
			label_cell(Num::price_format($label), "class='amount'" . $params, $id);
		}
	}

	function description_cell($label, $params = "", $id = null) {
		label_cell($label, $params . " class='desc'", $id);
	}

	function empty_cells($qty) {
		echo "<td colspan=$qty></td>";
	}

	function email_cell($label, $params = "", $id = null) {
		label_cell("<a href='mailto:$label'>$label</a>", $params, $id);
	}

	function label_cells($label, $value, $params = "", $params2 = "", $id = null) {
		if ($label != null) {
			echo "<td class='label' {$params}>{$label}</td>\n";
		}
		label_cell($value, $params2, $id);
	}

	function label_row($label, $value, $params = "", $params2 = "", $leftfill = 0, $id = null) {
		echo "<tr>";
		if ($params == "") {
			echo "<td class='label'>$label</td>";
			$label = null;
		}
		label_cells($label, $value, $params, $params2, $id);
		if ($leftfill != 0) {
			echo "<td colspan=$leftfill></td>";
		}
		echo "</tr>\n";
	}

	function labelheader_cell($label, $params = "") {
		echo "<th  $params>$label</th>\n";
	}

	function label_cell($label, $params = "", $id = null) {
		$Ajax = Ajax::i();
		if (!empty($id)) {
			$params .= " id='$id'";
			$Ajax->addUpdate($id, $id, $label);
		}
		echo "<td $params >$label</td>\n";
		return $label;
	}

	function percent_cell($label, $bold = false, $id = null) {
		if ($bold) {
			label_cell("<b>" . Num::percent_format($label) . "</b>", "nowrap class=right", $id);
		} else {
			label_cell(Num::percent_format($label), "nowrap class=right", $id);
		}
	}

	function qty_cell($label, $bold = false, $dec = null, $id = null) {
		if (!isset($dec)) {
			$dec = User::qty_dec();
		}
		if ($bold) {
			label_cell("<b>" . Num::format($label, $dec) . "</b>", "nowrap class=right", $id);
		} else {
			label_cell(Num::format(Num::round($label), $dec), "nowrap class=right", $id);
		}
	}

	function unit_amount_cell($label, $bold = false, $params = "", $id = null) {
		if ($bold) {
			label_cell("<b>" . unit_price_format($label) . "</b>", "nowrap class=right " . $params, $id);
		} else {
			label_cell(unit_price_format($label), "nowrap class=right " . $params, $id);
		}
	}
