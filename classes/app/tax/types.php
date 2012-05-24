<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Tax_Types {

    /**
     * @static
     *
     * @param $name
     * @param $sales_gl_code
     * @param $purchasing_gl_code
     * @param $rate
     */
    static public function add($name, $sales_gl_code, $purchasing_gl_code, $rate) {
      $sql = "INSERT INTO tax_types (name, sales_gl_code, purchasing_gl_code, rate)
		VALUES (" . DB::escape($name) . ", " . DB::escape($sales_gl_code)
        . ", " . DB::escape($purchasing_gl_code) . ", $rate)";
      DB::query($sql, "could not add tax type");
    }
    /**
     * @static
     *
     * @param $type_id
     * @param $name
     * @param $sales_gl_code
     * @param $purchasing_gl_code
     * @param $rate
     */
    static public function update($type_id, $name, $sales_gl_code, $purchasing_gl_code, $rate) {
      $sql = "UPDATE tax_types SET name=" . DB::escape($name) . ",
		sales_gl_code=" . DB::escape($sales_gl_code) . ",
		purchasing_gl_code=" . DB::escape($purchasing_gl_code) . ",
		rate=$rate
		WHERE id=" . DB::escape($type_id);
      DB::query($sql, "could not update tax type");
    }
    /**
     * @static
     *
     * @param bool $all
     *
     * @return null|PDOStatement
     */
    static public function get_all($all = FALSE) {
      $sql = "SELECT tax_types.*,
		Chart1.account_name AS SalesAccountName,
		Chart2.account_name AS PurchasingAccountName
		FROM tax_types, chart_master AS Chart1,
		chart_master AS Chart2
		WHERE tax_types.sales_gl_code = Chart1.account_code
		AND tax_types.purchasing_gl_code = Chart2.account_code";
      if (!$all) {
        $sql .= " AND !tax_types.inactive";
      }
      return DB::query($sql, "could not get all tax types");
    }
    /**
     * @static
     * @return null|PDOStatement
     */
    static public function get_all_simple() {
      $sql = "SELECT * FROM tax_types";
      return DB::query($sql, "could not get all tax types");
    }
    /**
     * @static
     *
     * @param $type_id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($type_id) {
      $sql    = "SELECT tax_types.*,
		Chart1.account_name AS SalesAccountName,
		Chart2.account_name AS PurchasingAccountName
		FROM tax_types, chart_master AS Chart1,
		chart_master AS Chart2
		WHERE tax_types.sales_gl_code = Chart1.account_code
		AND tax_types.purchasing_gl_code = Chart2.account_code AND id=" . DB::escape($type_id);
      $result = DB::query($sql, "could not get tax type");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $type_id
     *
     * @return mixed
     */
    static public function get_default_rate($type_id) {
      $sql    = "SELECT rate FROM tax_types WHERE id=" . DB::escape($type_id);
      $result = DB::query($sql, "could not get tax type rate");
      $row    = DB::fetch_row($result);
      return $row[0];
    }
    /**
     * @static
     *
     * @param $type_id
     *
     * @return bool
     */
    static public function delete($type_id) {
      if (static::can_delete($type_id)) {
        return FALSE;
      }

      DB::begin();
      $sql = "DELETE FROM tax_types WHERE id=" . DB::escape($type_id);
      DB::query($sql, "could not delete tax type");
      // also delete any item tax exemptions associated with this type
      $sql = "DELETE FROM item_tax_type_exemptions WHERE tax_type_id=$type_id";
      DB::query($sql, "could not delete item tax type exemptions");
      DB::commit();
      Event::notice(_('Selected tax type has been deleted'));
    }
    /**
    Check if gl_code is used by more than 2 tax types,
    or check if the two gl codes are not used by any other
    than selected tax type.
    Necessary for pre-2.2 installations.
     * @param $gl_code
     * @param $gl_code2
     * @param $selected_id
     *
     * @return bool
     */
    static public function is_tax_gl_unique($gl_code, $gl_code2 = -1, $selected_id = -1) {
      $purch_code = $gl_code2 == -1 ? $gl_code : $gl_code2;
      $sql        = "SELECT count(*) FROM "
        . "tax_types
		WHERE (sales_gl_code=" . DB::escape($gl_code)
        . " OR purchasing_gl_code=" . DB::escape($purch_code) . ")";
      if ($selected_id != -1) {
        $sql .= " AND id!=" . DB::escape($selected_id);
      }
      $res = DB::query($sql, "could not query gl account uniqueness");
      $row = DB::fetch($res);
      return $gl_code2 == -1 ? ($row[0] <= 1) : ($row[0] == 0);
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $none_option
     * @param bool $submit_on_change
     *
     * @return string
     */
    static public function select($name, $selected_id = NULL, $none_option = FALSE, $submit_on_change = FALSE) {
      $sql = "SELECT id, CONCAT(name, ' (',rate,'%)') as name FROM tax_types";
      return select_box($name, $selected_id, $sql, 'id', 'name', array(
        'spec_option' => $none_option, 'spec_id' => ALL_NUMERIC, 'select_submit' => $submit_on_change, 'async' => FALSE,
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $none_option
     * @param bool $submit_on_change
     */
    static public function cells($label, $name, $selected_id = NULL, $none_option = FALSE, $submit_on_change = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Tax_Types::select($name, $selected_id, $none_option, $submit_on_change);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $none_option
     * @param bool $submit_on_change
     */
    static public function row($label, $name, $selected_id = NULL, $none_option = FALSE, $submit_on_change = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      Tax_Types::cells(NULL, $name, $selected_id, $none_option, $submit_on_change);
      echo "</tr>\n";
    }

    /**
     * @static
     *
     * @param $selected_id
     *
     * @return bool
     */
    static public function can_delete($selected_id) {
      $sql    = "SELECT COUNT(*) FROM tax_group_items	WHERE tax_type_id=" . DB::escape($selected_id);
      $result = DB::query($sql, "could not query tax groups");
      $myrow  = DB::fetch_row($result);
      if ($myrow[0] > 0) {
        Event::error(_("Cannot delete this tax type because tax groups been created referring to it."));
        return FALSE;
      }
      return TRUE;
    }
    /**
     * @static
     *
     * @param $selected_id
     *
     * @return bool
     */
    static public function can_process($selected_id) {
      if (strlen($_POST['name']) == 0) {
        Event::error(_("The tax type name cannot be empty."));
        JS::set_focus('name');
        return FALSE;
      }
      elseif (!Validation::post_num('rate', 0)) {
        Event::error(_("The default tax rate must be numeric and not less than zero."));
        JS::set_focus('rate');
        return FALSE;
      }
      if (!Tax_Types::is_tax_gl_unique(get_post('sales_gl_code'), get_post('purchasing_gl_code'), $selected_id)) {
        Event::error(_("Selected GL Accounts cannot be used by another tax type."));
        JS::set_focus('sales_gl_code');
        return FALSE;
      }
      return TRUE;
    }
  }


