<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: advanced
   * Date: 9/05/12
   * Time: 2:42 PM
   * To change this template use File | Settings | File Templates.
   */
  class Table
  {
    /**
     * @param        $msg
     * @param int    $colspan
     * @param string $class
     */
    public static function sectionTitle($msg, $colspan = 2, $class = 'tablehead')
    {
      echo "<tr class='$class'><td colspan=$colspan class='$class'>$msg</td></tr>\n";
    }
    /**
     * @param        $labels
     * @param string $params
     */
    public static function header($labels, $params = '')
    {
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
    public static function start($class = "")
    {
      echo "<div class='center'><table";
      if ($class != "") {
        echo " class='$class'";
      }
      echo " >\n";
    }
    /**
     * @param int $breaks
     */
    public static function end($breaks = 0)
    {
      echo "</table></div>\n";
      if ($breaks) {
        Display::br($breaks);
      }
    }
    /**
     * @param string $class
     */
    public static function startOuter($class = "")
    {
      Table::start($class);
      echo "<tr class='top'><td>\n"; // outer table
    }
    /**
     * @param int    $number
     * @param bool   $width
     * @param string $class
     */
    public static function section($number = 1, $width = false, $class = '')
    {
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
    public static function endOuter($breaks = 0, $close_table = true)
    {
      if ($close_table) {
        echo "</table>\n";
      }
      echo "</td></tr>\n";
      Table::end($breaks);
    }
    public static function foot($class = '')
    {
      if ($class) {
        $class = " class='$class' ";
      }
      echo "<tfoot $class>";
    }
    public static function footEnd()
    {
      echo "</tfoot>";
    }
  }
