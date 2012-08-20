<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Tax_Types
  {
    /**
     * @static
     *
     * @param $name
     * @param $sales_gl_code
     * @param $purchasing_gl_code
     * @param $rate
     */
    public static function add($name, $sales_gl_code, $purchasing_gl_code, $rate)
    {
      $sql
        = "INSERT INTO tax_types (name, sales_gl_code, purchasing_gl_code, rate)
        VALUES (" . DB::_escape($name) . ", " . DB::_escape($sales_gl_code) . ", " . DB::_escape($purchasing_gl_code) . ", $rate)";
      DB::_query($sql, "could not add tax type");
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
    public static function update($type_id, $name, $sales_gl_code, $purchasing_gl_code, $rate)
    {
      $sql = "UPDATE tax_types SET name=" . DB::_escape($name) . ",
        sales_gl_code=" . DB::_escape($sales_gl_code) . ",
        purchasing_gl_code=" . DB::_escape($purchasing_gl_code) . ",
        rate=$rate
        WHERE id=" . DB::_escape($type_id);
      DB::_query($sql, "could not update tax type");
    }
    /**
     * @static
     *
     * @param bool $all
     *
     * @return null|PDOStatement
     */
    public static function getAll($all = false)
    {
      $sql
        = "SELECT tax_types.*,
        Chart1.account_name AS SalesAccountName,
        Chart2.account_name AS PurchasingAccountName
        FROM tax_types, chart_master AS Chart1,
        chart_master AS Chart2
        WHERE tax_types.sales_gl_code = Chart1.account_code
        AND tax_types.purchasing_gl_code = Chart2.account_code";
      if (!$all) {
        $sql .= " AND !tax_types.inactive";
      }

      return DB::_query($sql, "could not get all tax types");
    }
    /**
     * @static
     * @return null|PDOStatement
     */
    public static function get_all_simple()
    {
      $sql = "SELECT * FROM tax_types";

      return DB::_query($sql, "could not get all tax types");
    }
    /**
     * @static
     *
     * @param $type_id
     *
     * @return \ADV\Core\DB\Query\Result|Array
     */
    public static function get($type_id)
    {
      $sql
              = "SELECT tax_types.*,
        Chart1.account_name AS SalesAccountName,
        Chart2.account_name AS PurchasingAccountName
        FROM tax_types, chart_master AS Chart1,
        chart_master AS Chart2
        WHERE tax_types.sales_gl_code = Chart1.account_code
        AND tax_types.purchasing_gl_code = Chart2.account_code AND id=" . DB::_escape($type_id);
      $result = DB::_query($sql, "could not get tax type");

      return DB::_fetch($result);
    }
    /**
     * @static
     *
     * @param $type_id
     *
     * @return mixed
     */
    public static function get_default_rate($type_id)
    {
      $sql    = "SELECT rate FROM tax_types WHERE id=" . DB::_escape($type_id);
      $result = DB::_query($sql, "could not get tax type rate");
      $row    = DB::_fetchRow($result);

      return $row[0];
    }
    /**
     * @static
     *
     * @param $type_id
     *
     * @return bool
     */
    public static function delete($type_id)
    {
      if (static::can_delete($type_id)) {
        return false;
      }

      DB::_begin();
      $sql = "DELETE FROM tax_types WHERE id=" . DB::_escape($type_id);
      DB::_query($sql, "could not delete tax type");
      // also delete any item tax exemptions associated with this type
      $sql = "DELETE FROM item_tax_type_exemptions WHERE tax_type_id=$type_id";
      DB::_query($sql, "could not delete item tax type exemptions");
      DB::_commit();
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
    public static function is_tax_gl_unique($gl_code, $gl_code2 = -1, $selected_id = -1)
    {
      $purch_code = $gl_code2 == -1 ? $gl_code : $gl_code2;
      $sql        = "SELECT count(*) FROM " . "tax_types
        WHERE (sales_gl_code=" . DB::_escape($gl_code) . " OR purchasing_gl_code=" . DB::_escape($purch_code) . ")";
      if ($selected_id != -1) {
        $sql .= " AND id!=" . DB::_escape($selected_id);
      }
      $res = DB::_query($sql, "could not query gl account uniqueness");
      $row = DB::_fetch($res);

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
    public static function select($name, $selected_id = null, $none_option = false, $submit_on_change = false)
    {
      $sql = "SELECT id, CONCAT(name, ' (',rate,'%)') as name FROM tax_types";

      return Forms::selectBox($name, $selected_id, $sql, 'id', 'name', array(
                                                                            'spec_option'   => $none_option,
                                                                            'spec_id'       => ALL_NUMERIC,
                                                                            'select_submit' => $submit_on_change,
                                                                            'async'         => false,
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
    public static function cells($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false)
    {
      if ($label != null) {
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
    public static function row($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false)
    {
      echo "<tr><td class='label'>$label</td>";
      Tax_Types::cells(null, $name, $selected_id, $none_option, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param $selected_id
     *
     * @return bool
     */
    public static function can_delete($selected_id)
    {
      $sql    = "SELECT COUNT(*) FROM tax_group_items	WHERE tax_type_id=" . DB::_escape($selected_id);
      $result = DB::_query($sql, "could not query tax groups");
      $myrow  = DB::_fetchRow($result);
      if ($myrow[0] > 0) {
        Event::error(_("Cannot delete this tax type because tax groups been created referring to it."));

        return false;
      }

      return true;
    }
    /**
     * @static
     *
     * @param $selected_id
     *
     * @return bool
     */
    public static function can_process($selected_id)
    {
      if (strlen($_POST['name']) == 0) {
        Event::error(_("The tax type name cannot be empty."));
        JS::_setFocus('name');

        return false;
      } elseif (!Validation::post_num('rate', 0)) {
        Event::error(_("The default tax rate must be numeric and not less than zero."));
        JS::_setFocus('rate');

        return false;
      }
      if (!Tax_Types::is_tax_gl_unique(Input::_post('sales_gl_code'), Input::_post('purchasing_gl_code'), $selected_id)) {
        Event::error(_("Selected GL Accounts cannot be used by another tax type."));
        JS::_setFocus('sales_gl_code');

        return false;
      }

      return true;
    }
  }

