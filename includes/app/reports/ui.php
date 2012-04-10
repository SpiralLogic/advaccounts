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
    static public function print_profiles_row($label, $name, $selected_id = NULL, $spec_opt = FALSE, $submit_on_change = TRUE) {
      $sql = "SELECT profile FROM print_profiles GROUP BY profile";
      $result = DB::query($sql, 'cannot get all profile names');
      $profiles = array();
      while ($myrow = DB::fetch($result)) {
        $profiles[$myrow['profile']] = $myrow['profile'];
      }
      echo "<tr>";
      if ($label != NULL) {
        echo "<td class='label'>$label</td>\n";
      }
      echo "<td>";
      echo array_selector($name, $selected_id, $profiles, array(
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
    static public function printers($name, $selected_id = NULL, $spec_opt = FALSE, $submit_on_change = FALSE) {
      static $printers; // query only once for page display
      if (!$printers) {
        $sql = "SELECT id, name, description FROM printers";
        $result = DB::query($sql, 'cannot get all printers');
        $printers = array();
        while ($myrow = DB::fetch($result)) {
          $printers[$myrow['id']] = $myrow['name'] . '&nbsp;-&nbsp;' . $myrow['description'];
        }
      }
      return array_selector($name, $selected_id, $printers, array(
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
    static public function pagesizes_row($label, $name, $value = NULL) {
      $items = array();
      foreach (Config::get('print_paper_sizes') as $pz) {
        $items[$pz] = $pz;
      }
      echo "<tr><td class='label'>$label</td>\n<td>";
      echo array_selector($name, $value, $items);
      echo "</td></tr>\n";
    }
  }
