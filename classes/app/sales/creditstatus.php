<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Sales_CreditStatus
  {
    /**
     * @static
     *
     * @param $description
     * @param $disallow_invoicing
     */
    public static function add($description, $disallow_invoicing)
    {
      $sql
        = "INSERT INTO credit_status (reason_description, dissallow_invoices)
        VALUES (" . DB::escape($description) . "," . DB::escape($disallow_invoicing) . ")";
      DB::query($sql, "could not add credit status");
    }
    /**
     * @static
     *
     * @param $status_id
     * @param $description
     * @param $disallow_invoicing
     */
    public static function update($status_id, $description, $disallow_invoicing)
    {
      $sql = "UPDATE credit_status SET reason_description=" . DB::escape($description) . ",
        dissallow_invoices=" . DB::escape($disallow_invoicing) . " WHERE id=" . DB::escape($status_id);
      DB::query($sql, "could not update credit status");
    }
    /**
     * @static
     *
     * @param bool $all
     *
     * @return null|PDOStatement
     */
    public static function get_all($all = false)
    {
      $sql = "SELECT * FROM credit_status";
      if (!$all) {
        $sql .= " WHERE !inactive";
      }

      return DB::query($sql, "could not get all credit status");
    }
    /**
     * @static
     *
     * @param $status_id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    public static function get($status_id)
    {
      $sql    = "SELECT * FROM credit_status WHERE id=" . DB::escape($status_id);
      $result = DB::query($sql, "could not get credit status");

      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $status_id
     */
    public static function delete($status_id)
    {
      $sql = "DELETE FROM credit_status WHERE id=" . DB::escape($status_id);
      DB::query($sql, "could not delete credit status");
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param null $disabled
     *
     * @return string
     */
    public static function select($name, $selected_id = null, $disabled = null)
    {
      if ($disabled === null) {
        $disabled = (!User::i()->can_access(SA_CUSTOMER_CREDIT));
      }
      $sql = "SELECT id, reason_description, inactive FROM credit_status";

      return Form::selectBox($name, $selected_id, $sql, 'id', 'reason_description', array('disabled' => $disabled));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param null $disabled
     */
    public static function cells($label, $name, $selected_id = null, $disabled = null)
    {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Sales_CreditStatus::select($name, $selected_id, $disabled);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param null $disabled
     */
    public static function row($label, $name, $selected_id = null, $disabled = null)
    {
      echo "<tr><td class='label'>$label</td>";
      Sales_CreditStatus::cells(null, $name, $selected_id, $disabled);
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
      $sql
              = "SELECT COUNT(*) FROM debtors
            WHERE credit_status=" . DB::escape($selected_id);
      $result = DB::query($sql, "could not query customers");
      $myrow  = DB::fetch_row($result);
      if ($myrow[0] > 0) {
        Event::error(_("Cannot delete this credit status because customer accounts have been created referring to it."));

        return false;
      }

      return true;
    }
    /**
     * @static
     * @return bool
     */
    public static function can_process()
    {
      if (strlen($_POST['reason_description']) == 0) {
        Event::error(_("The credit status description cannot be empty."));
        JS::set_focus('reason_description');

        return false;
      }

      return true;
    }
  }
