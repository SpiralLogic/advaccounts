<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Tax_ItemType {

    /**
     * @static
     *
     * @param $name
     * @param $exempt
     * @param $exempt_from
     */
    static public function add($name, $exempt, $exempt_from) {
      DB::begin();

      $sql = "INSERT INTO item_tax_types (name, exempt)
		VALUES (" . DB::escape($name) . "," . DB::escape($exempt) . ")";

      DB::query($sql, "could not add item tax type");

      $id = DB::insert_id();

      // add the exemptions
      static::add_exemptions($id, $exempt_from);

      DB::commit();
    }
    /**
     * @static
     *
     * @param $id
     * @param $name
     * @param $exempt
     * @param $exempt_from
     */
    static public function update($id, $name, $exempt, $exempt_from) {
      DB::begin();

      $sql = "UPDATE item_tax_types SET name=" . DB::escape($name) .
        ",	exempt=" . DB::escape($exempt) . " WHERE id=" . DB::escape($id);

      DB::query($sql, "could not update item tax type");

      // readd the exemptions
      static::delete_exemptions($id);
      static::add_exemptions($id, $exempt_from);

      DB::commit();
    }
    /**
     * @static
     * @return null|PDOStatement
     */
    static public function get_all() {
      $sql = "SELECT * FROM item_tax_types";

      return DB::query($sql, "could not get all item tax type");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($id) {
      $sql = "SELECT * FROM item_tax_types WHERE id=" . DB::escape($id);

      $result = DB::query($sql, "could not get item tax type");

      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $stock_id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get_for_item($stock_id) {
      $sql = "SELECT item_tax_types.* FROM item_tax_types,stock_master WHERE
		stock_master.stock_id=" . DB::escape($stock_id) . "
		AND item_tax_types.id=stock_master.tax_type_id";

      $result = DB::query($sql, "could not get item tax type");

      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return bool
     */
    static public function delete($id) {
      if (!can_delete($id)) {
        return FALSE;
      }

      DB::begin();
      $sql = "DELETE FROM item_tax_types WHERE id=" . DB::escape($id);
      DB::query($sql, "could not delete item tax type");
      // also delete all exemptions
      static::delete_exemptions($id);
      DB::commit();
      Event::notice(_('Selected item tax type has been deleted'));
    }
    /**
     * @static
     *
     * @param $id
     * @param $exemptions
     */
    static public function add_exemptions($id, $exemptions) {
      for ($i = 0; $i < count($exemptions); $i++) {
        $sql = "INSERT INTO item_tax_type_exemptions (item_tax_type_id, tax_type_id)
			VALUES (" . DB::escape($id) . ", " . DB::escape($exemptions[$i]) . ")";
        DB::query($sql, "could not add item tax type exemptions");
      }
    }
    /**
     * @static
     *
     * @param $id
     */
    static public function delete_exemptions($id) {
      $sql = "DELETE FROM item_tax_type_exemptions WHERE item_tax_type_id=" . DB::escape($id);

      DB::query($sql, "could not delete item tax type exemptions");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return null|PDOStatement
     */
    static public function get_exemptions($id) {
      $sql = "SELECT * FROM item_tax_type_exemptions WHERE item_tax_type_id=" . DB::escape($id);

      return DB::query($sql, "could not get item tax type exemptions");
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     *
     * @return string
     */
    static public function select($name, $selected_id = NULL) {
      $sql = "SELECT id, name FROM item_tax_types";
      return select_box($name, $selected_id, $sql, 'id', 'name', array('order' => 'id'));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    static public function cells($label, $name, $selected_id = NULL) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Tax_ItemType::select($name, $selected_id);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    static public function row($label, $name, $selected_id = NULL) {
      echo "<tr><td class='label'>$label</td>";
      Tax_ItemType::cells(NULL, $name, $selected_id);
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
      $sql    = "SELECT COUNT(*) FROM stock_master WHERE tax_type_id=" . DB::escape($selected_id);
      $result = DB::query($sql, "could not query stock master");
      $myrow  = DB::fetch_row($result);
      if ($myrow[0] > 0) {
        Event::error(_("Cannot delete this item tax type because items have been created referring to it."));
        return FALSE;
      }
      return TRUE;
    }
  }


