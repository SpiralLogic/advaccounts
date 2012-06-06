<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: advanced
   * Date: 9/05/12
   * Time: 2:42 PM
   * To change this template use File | Settings | File Templates.
   */

  class Table {

    /**
     * @param        $msg
     * @param int    $colspan
     * @param string $class
     */
    static function sectionTitle($msg, $colspan = 2, $class = 'tablehead') {
      echo "<tr class='$class'><td colspan=$colspan class='$class'>$msg</td></tr>\n";
    }

    /**
     * @param        $labels
     * @param string $params
     */
    static function header($labels, $params = '') {
      echo '<thead><tr>';
      $labels = (array) $labels;
      foreach ($labels as $label) {
        Cell::labelHeader($label, $params);
      }
      echo '</tr></thead>';
    }

    /**
     * @param string $class
     */
    static function start($class = "") {
      echo "<div class='center'><table";
      if ($class != "") {
        echo " class='$class'";
      }
      echo " >\n";
    }

    /**
     * @param int $breaks
     */
    static function end($breaks = 0) {
      echo "</table></div>\n";
      if ($breaks) {
        Display::br($breaks);
      }
    }

    /**
     * @param string $class
     */
    static function startOuter($class = "") {
      Table::start($class);
      echo "<tr class='top'><td>\n"; // outer table
    }
    /**
     * @param int    $number
     * @param bool   $width
     * @param string $class
     */
    static function section($number = 1, $width = FALSE, $class = '') {
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
    static function endOuter($breaks = 0, $close_table = TRUE) {
      if ($close_table) {
        echo "</table>\n";
      }
      echo "</td></tr>\n";
      Table::end($breaks);
    }
    /**
     * @static
     *
     * @param string $class
     */
    static function foot($class = '') {
      if ($class) {
        $class = " class='$class' ";
      }
      echo "<tfoot $class>";
    }
    static function footEnd() {
      echo "</tfoot>";
    }
  }
