<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  /**
   * @param $label
   * @param string $params
   * @param null $id
   */
  function amount_decimal_cell($label, $params = "", $id = NULL) {
    $dec = 0;
    label_cell(Num::price_decimal($label, $dec), ' class="right nowrap"' . $params, $id);
  }

  /**
   * @param        $label
   * @param bool   $bold
   * @param string $params
   * @param null   $id
   */
  function amount_cell($label, $bold = FALSE, $params = "", $id = NULL) {
    if ($bold) {
      label_cell("<span class='bold'>" . Num::price_format($label) . "</span>", "class='amount'" . $params, $id);
    }
    else {
      label_cell(Num::price_format($label), "class='amount'" . $params, $id);
    }
  }

  /**
   * @param        $label
   * @param string $params
   * @param null   $id
   */
  function description_cell($label, $params = "", $id = NULL) {
    label_cell($label, $params . " class='desc'", $id);
  }

  /**
   * @param $qty
   */
  function empty_cells($qty) {
    echo "<td colspan=$qty></td>";
  }

  /**
   * @param        $label
   * @param string $params
   * @param null   $id
   */
  function email_cell($label, $params = "", $id = NULL) {
    label_cell("<a href='mailto:$label'>$label</a>", $params, $id);
  }

  /**
   * @param        $label
   * @param        $value
   * @param string $params
   * @param string $params2
   * @param null   $id
   */
  function label_cells($label, $value, $params = "", $params2 = "", $id = NULL) {
    if ($label != NULL) {
      echo "<td class='label' {$params}>{$label}</td>\n";
    }
    label_cell($value, $params2, $id);
  }

  /**
   * @param        $label
   * @param        $value
   * @param string $params
   * @param string $params2
   * @param int    $leftfill
   * @param null   $id
   */
  function label_row($label, $value, $params = "", $params2 = "", $leftfill = 0, $id = NULL) {
    echo "<tr>";
    if ($params == "") {
      echo "<td class='label'>$label</td>";
      $label = NULL;
    }
    elseif (stristr($params, 'class')) {
      echo "<td $params>$label</td>";
      $label = NULL;
    }
    label_cells($label, $value, $params, $params2, $id);
    if ($leftfill != 0) {
      echo "<td colspan=$leftfill></td>";
    }
    echo "</tr>\n";
  }

  /**
   * @param        $label
   * @param string $params
   */
  function labelheader_cell($label, $params = "") {
    echo "<th $params>$label</th>\n";
  }

  /**
   * @param        $label
   * @param string $params
   * @param null   $id
   *
   * @return mixed
   */
  function label_cell($label, $params = "", $id = NULL) {

    if (!empty($id)) {
      $params .= " id='$id'";
      Ajax::i()->addUpdate($id, $id, $label);
    }
    echo "<td $params >$label</td>\n";
    return $label;
  }

  /**
   * @param      $label
   * @param bool $bold
   * @param null $id
   */
  function percent_cell($label, $bold = FALSE, $id = NULL) {
    if ($bold) {
      label_cell("<span class='bold'>" . Num::percent_format($label) . "</span>", ' class="right nowrap"', $id);
    }
    else {
      label_cell(Num::percent_format($label), ' class="right nowrap"', $id);
    }
  }

  /**
   * @param      $label
   * @param bool $bold
   * @param null $dec
   * @param null $id
   */
  function qty_cell($label, $bold = FALSE, $dec = NULL, $id = NULL) {
    if (!isset($dec)) {
      $dec = User::qty_dec();
    }
    if ($bold) {
      label_cell("<span class='bold'>" . Num::format($label, $dec) . "</span>", ' class="right nowrap"', $id);
    }
    else {
      label_cell(Num::format(Num::round($label), $dec), ' class="right nowrap"', $id);
    }
  }

  /**
   * @param        $label
   * @param bool   $bold
   * @param string $params
   * @param null   $id
   */
  function unit_amount_cell($label, $bold = FALSE, $params = "", $id = NULL) {
    if ($bold) {
      label_cell("<span class='bold'>" . unit_price_format($label) . "</span>", ' class="right nowrap"' . $params, $id);
    }
    else {
      label_cell(unit_price_format($label), ' class="right nowrap"' . $params, $id);
    }
  }

  /**
   * @param $k
   */
  function alt_table_row_color(&$k) {
    if ($k == 1) {
      echo "<tr class='oddrow grid'>\n";
      $k = 0;
    }
    else {
      echo "<tr class='evenrow grid'>\n";
      $k++;
    }
  }

  /**
   * @param        $msg
   * @param int    $colspan
   * @param string $class
   */
  function table_section_title($msg, $colspan = 2, $class = 'tablehead') {
    echo "<tr class='$class'><td colspan=$colspan class='$class'>$msg</td></tr>\n";
  }

  /**
   * @param        $labels
   * @param string $params
   */
  function table_header($labels, $params = '') {
    echo '<thead><tr>';
    $labels = (array) $labels;
    foreach ($labels as $label) {
      labelheader_cell($label, $params);
    }
    echo '</tr></thead>';
  }

  /**
   * @param string $param
   */
  function start_row($param = "") {
    if ($param != "") {
      echo "<tr $param>\n";
    }
    else {
      echo "<tr>\n";
    }
  }

  function end_row() {
    echo "</tr>\n";
  }

  /**
   * @param $value
   */
  function debit_or_credit_cells($value) {
    $value = Num::round($value, User::price_dec());
    if ($value >= 0) {
      amount_cell($value);
      label_cell("");
    }
    elseif ($value < 0) {
      label_cell("");
      amount_cell(abs($value));
    }
  }

  /**
   * @param string $class
   */
  function start_table($class = "") {
    echo "<div class='center'><table";
    if ($class != "") {
      echo " class='$class'";
    }
    echo " >\n";
  }

  /**
   * @param int $breaks
   */
  function end_table($breaks = 0) {
    echo "</table></div>\n";
    if ($breaks) {
      Display::br($breaks);
    }
  }

  /**
   * @param string $class
   */
  function start_outer_table($class = "") {
    start_table($class);
    echo "<tr class='top'><td>\n"; // outer table
  }

  /**
   * @param int    $number
   * @param bool   $width
   * @param string $class
   */
  function table_section($number = 1, $width = FALSE, $class = '') {
    if ($number > 1) {
      echo "</table>\n";
      $width = ($width ? "width:$width" : "");
      //echo "</td><td class='tableseparator' $width>\n"; // outer table
      echo "</td><td style='border-left:1px solid #cccccc; $width'>\n"; // outer table
    }
    echo "<table class='tablestyle_inner $class'>\n";
  }

  /**
   * @param int  $breaks
   * @param bool $close_table
   */
  function end_outer_table($breaks = 0, $close_table = TRUE) {
    if ($close_table) {
      echo "</table>\n";
    }
    echo "</td></tr>\n";
    end_table($breaks);
  }

