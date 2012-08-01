<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Reports_UI {

    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_opt
     * @param bool $submit_on_change
     */
    public static function print_profiles_row($label, $name, $selected_id = null, $spec_opt = false, $submit_on_change = true) {
      $sql      = "SELECT profile FROM print_profiles GROUP BY profile";
      $result   = DB::query($sql, 'cannot get all profile names');
      $profiles = [];
      while ($myrow = DB::fetch($result)) {
        $profiles[$myrow['profile']] = $myrow['profile'];
      }
      echo "<tr>";
      if ($label != null) {
        echo "<td class='label'>$label</td>\n";
      }
      echo "<td>";
      echo Forms::arraySelect($name, $selected_id, $profiles, array(
        'select_submit' => $submit_on_change, 'spec_option' => $spec_opt, 'spec_id' => ''
      ));
      echo "</td></tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $spec_opt
     * @param bool $submit_on_change
     *
     * @return string
     */
    public static function printers($name, $selected_id = null, $spec_opt = false, $submit_on_change = false) {
      static $printers; // query only once for page display
      if (!$printers) {
        $sql      = "SELECT id, name, description FROM printers";
        $result   = DB::query($sql, 'cannot get all printers');
        $printers = [];
        while ($myrow = DB::fetch($result)) {
          $printers[$myrow['id']] = $myrow['name'] . '&nbsp;-&nbsp;' . $myrow['description'];
        }
      }
      return Forms::arraySelect($name, $selected_id, $printers, array(
        'select_submit' => $submit_on_change, 'spec_option' => $spec_opt, 'spec_id' => ''
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $value
     */
    public static function pagesizes_row($label, $name, $value = null) {
      $items = [];
      foreach (Config::get('print_paper_sizes') as $pz) {
        $items[$pz] = $pz;
      }
      echo "<tr><td class='label'>$label</td>\n<td>";
      echo Forms::arraySelect($name, $value, $items);
      echo "</td></tr>\n";
    }
  }