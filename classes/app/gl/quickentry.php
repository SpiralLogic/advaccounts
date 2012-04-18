<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class GL_QuickEntry {
    /**
     * @static
     *
     * @param $description
     * @param $type
     * @param $base_amount
     * @param $base_desc
     */
    static public function add($description, $type, $base_amount, $base_desc) {
      $sql = "INSERT INTO quick_entries (description, type, base_amount, base_desc)
		VALUES (" . DB::escape($description) . ", " . DB::escape($type) . ", "
        . DB::escape($base_amount) . ", " . DB::escape($base_desc) . ")";

      DB::query($sql, "could not insert quick entry for $description");
    }
    /**
     * @static
     *
     * @param $selected_id
     * @param $description
     * @param $type
     * @param $base_amount
     * @param $base_desc
     */
    static public function update($selected_id, $description, $type, $base_amount, $base_desc) {
      $sql = "UPDATE quick_entries	SET description = " . DB::escape($description) . ",
			type=" . DB::escape($type) . ", base_amount=" . DB::escape($base_amount)
        . ", base_desc=" . DB::escape($base_desc) . "
			WHERE id = " . DB::escape($selected_id);

      DB::query($sql, "could not update quick entry for $selected_id");
    }
    /**
     * @static
     *
     * @param $selected_id
     */
    static public function delete($selected_id) {
      $sql = "DELETE FROM quick_entries WHERE id=" . DB::escape($selected_id);

      DB::query($sql, "could not delete quick entry $selected_id");
    }
    /**
     * @static
     *
     * @param $qid
     * @param $action
     * @param $dest_id
     * @param $amount
     * @param $dim
     * @param $dim2
     */
    static public function add_line($qid, $action, $dest_id, $amount, $dim, $dim2) {
      $sql = "INSERT INTO quick_entry_lines
			(qid, action, dest_id, amount, dimension_id, dimension2_id)
		VALUES
			($qid, " . DB::escape($action) . "," . DB::escape($dest_id) . ",
				" . DB::escape($amount) . ", " . DB::escape($dim) . ", " . DB::escape($dim2) . ")";

      DB::query($sql, "could not insert quick entry line for $qid");
    }
    /**
     * @static
     *
     * @param $selected_id
     * @param $qid
     * @param $action
     * @param $dest_id
     * @param $amount
     * @param $dim
     * @param $dim2
     */
    static public function update_line($selected_id, $qid, $action, $dest_id, $amount, $dim, $dim2) {
      $sql = "UPDATE quick_entry_lines SET qid = " . DB::escape($qid)
        . ", action=" . DB::escape($action) . ",
			dest_id=" . DB::escape($dest_id) . ", amount=" . DB::escape($amount)
        . ", dimension_id=" . DB::escape($dim) . ", dimension2_id=" . DB::escape($dim2) . "
			WHERE id = " . DB::escape($selected_id);

      DB::query($sql, "could not update quick entry line for $selected_id");
    }
    /**
     * @static
     *
     * @param $selected_id
     */
    static public function delete_line($selected_id) {
      $sql = "DELETE FROM quick_entry_lines WHERE id=" . DB::escape($selected_id);

      DB::query($sql, "could not delete quick entry line $selected_id");
    }
    /**
     * @static
     *
     * @param null $type
     *
     * @return bool
     */
    static public function has($type = NULL) {
      $sql = "SELECT id FROM quick_entries";
      if ($type != NULL) {
        $sql .= " WHERE type=" . DB::escape($type);
      }

      $result = DB::query($sql, "could not retreive quick entries");
      return DB::num_rows($result) > 0;
    }
    /**
     * @static
     *
     * @param null $type
     *
     * @return null|PDOStatement
     */
    static public function get_all($type = NULL) {
      $sql = "SELECT * FROM quick_entries";
      if ($type != NULL) {
        $sql .= " WHERE type=" . DB::escape($type);
      }
      $sql .= " ORDER BY description";

      return DB::query($sql, "could not retreive quick entries");
    }
    /**
     * @static
     *
     * @param $selected_id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($selected_id) {
      $sql = "SELECT * FROM quick_entries WHERE id=" . DB::escape($selected_id);

      $result = DB::query($sql, "could not retreive quick entry $selected_id");

      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $qid
     *
     * @return null|PDOStatement
     */
    static public function get_lines($qid) {
      $sql = "SELECT quick_entry_lines.*, chart_master.account_name,
				tax_types.name as tax_name
			FROM quick_entry_lines
			LEFT JOIN chart_master ON
				quick_entry_lines.dest_id = chart_master.account_code
			LEFT JOIN tax_types ON
				quick_entry_lines.dest_id = tax_types.id
			WHERE
				qid=" . DB::escape($qid) . " ORDER by id";

      return DB::query($sql, "could not retreive quick entries");
    }
    /**
     * @static
     *
     * @param $qid
     *
     * @return bool
     */
    static public function has_lines($qid) {
      $sql = "SELECT id FROM quick_entry_lines WHERE qid=" . DB::escape($qid);

      $result = DB::query($sql, "could not retreive quick entries");
      return DB::num_rows($result) > 0;
    }
    /**
     * @static
     *
     * @param $selected_id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function has_line($selected_id) {
      $sql = "SELECT * FROM quick_entry_lines WHERE id=" . DB::escape($selected_id);

      $result = DB::query($sql, "could not retreive quick entry for $selected_id");

      return DB::fetch($result);
    }

    //
    //	Expands selected quick entry $id into GL posings and adds to order.
    //		returns calculated amount posted to bank GL account.
    //
    /**
     * @static
     *
     * @param        $order
     * @param        $id
     * @param        $base
     * @param        $type
     * @param string $descr
     *
     * @return int
     */
    static public function show_menu(&$order, $id, $base, $type, $descr = '') {
      $bank_amount = 0;
      if (!isset($id) || $id == NULL || $id == "") {
        Event::error(_("No Quick Entries are defined."));
        JS::set_focus('total_amount');
      }
      else {
        if ($type == QE_DEPOSIT) {
          $base = -$base;
        }
        if ($type != QE_SUPPINV) // only one quick entry on journal/bank transaction
        {
          $order->clear_items();
        }
        $qe = GL_QuickEntry::get($id);
        if ($descr != '') {
          $qe['description'] .= ': ' . $descr;
        }
        $result = GL_QuickEntry::get_lines($id);
        $totrate = 0;
        $qe_lines = array();
        while ($row = DB::fetch($result)) {
          $qe_lines[] = $row;
          switch (strtolower($row['action'])) {
            case "t": // post taxes calculated on base amount
            case "t+": // ditto & increase base amount
            case "t-": // ditto & reduce base amount
              if (substr($row['action'], 0, 1) != 'T') {
                $totrate += Tax_Types::get_default_rate($row['dest_id']);
              }
          }
        }
        $first = TRUE;
        $taxbase = 0;
        if (!count($qe_lines)) {
          Event::error(_('There are no lines for this quick entry!'));
          Page::footer_exit();
        }

        foreach ($qe_lines as $qe_line) {
          switch (strtolower($qe_line['action'])) {
            case "=": // post current base amount to GL account
              $part = $base;
              break;
            case "a": // post amount to GL account and reduce base
              $part = $qe_line['amount'];
              break;
            case "a+": // post amount to GL account and increase base
              $part = $qe_line['amount'];
              $base += $part;
              break;
            case "a-": // post amount to GL account and reduce base
              $part = $qe_line['amount'];
              $base -= $part;
              break;
            case "%": // store acc*amount% to GL account
              $part = Num::round($base * $qe_line['amount'] / 100, User::price_dec());
              break;
            case "%+": // ditto & increase base amount
              $part = Num::round($base * $qe_line['amount'] / 100, User::price_dec());
              $base += $part;
              break;
            case "%-": // ditto & reduce base amount
              $part = Num::round($base * $qe_line['amount'] / 100, User::price_dec());
              $base -= $part;
              break;
            case "t": // post taxes calculated on base amount
            case "t+": // ditto & increase base amount
            case "t-": // ditto & reduce base amount
              if ($first) {
                $taxbase = $base / ($totrate + 100);
                $first = FALSE;
              }
              if (substr($qe_line['action'], 0, 1) != 'T') {
                $part = $taxbase;
              }
              else {
                $part = $base / 100;
              }
              $item_tax = Tax_Types::get($qe_line['dest_id']);
              //if ($type == QE_SUPPINV && substr($qe_line['action'],0,1) != 'T')
              if ($type == QE_SUPPINV) {
                $taxgroup = $order->tax_group_id;
                $rates = 0;
                $res = Tax_Groups::get_for_item($order->tax_group_id);
                while ($row = DB::fetch($res)) {
                  $rates += $row['rate'];
                }
                if ($rates == 0) {
                  continue 2;
                }
              }
              $tax = Num::round($part * $item_tax['rate'], User::price_dec());
              if ($tax == 0) {
                continue 2;
              }
              $gl_code = ($type == QE_DEPOSIT || ($type == QE_JOURNAL && $base < 0)) ? $item_tax['sales_gl_code'] : $item_tax['purchasing_gl_code'];
              if (!Tax_Types::is_tax_gl_unique($gl_code)) {
                Event::error(_("Cannot post to GL account used by more than one tax type."));
                break 2;
              }
              if ($type != QE_SUPPINV) {
                $order->add_gl_item($gl_code, $qe_line['dimension_id'], $qe_line['dimension2_id'], $tax, $qe['description']);
              }
              else {
                $acc_name = GL_Account::get_name($gl_code);
                $order->add_gl_codes_to_trans($gl_code, $acc_name, $qe_line['dimension_id'], $qe_line['dimension2_id'], $tax, $qe['description']);
              }
              if (strpos($qe_line['action'], '+')) {
                $base += $tax;
              }
              elseif (strpos($qe_line['action'], '-')) {
                $base -= $tax;
              }
              continue 2;
          }
          if ($type != QE_SUPPINV) {
            $order->add_gl_item($qe_line['dest_id'], $qe_line['dimension_id'], $qe_line['dimension2_id'], $part, $qe['description']);
          }
          else {
            $acc_name = GL_Account::get_name($qe_line['dest_id']);
            $order->add_gl_codes_to_trans($qe_line['dest_id'], $acc_name, $qe_line['dimension_id'], $qe_line['dimension2_id'], $part, $qe['description']);
          }
        }
      }
      return $bank_amount;
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param null $type
     * @param bool $submit_on_change
     *
     * @return string
     */
    static public function select($name, $selected_id = NULL, $type = NULL, $submit_on_change = FALSE) {
      $where = FALSE;
      $sql = "SELECT id, description FROM quick_entries";
      if ($type != NULL) {
        $sql .= " WHERE type=$type";
      }
      return select_box($name, $selected_id, $sql, 'id', 'description', array(
        'spec_id' => '', 'order' => 'description', 'select_submit' => $submit_on_change, 'async' => FALSE
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param      $type
     * @param bool $submit_on_change
     */
    static public function cells($label, $name, $selected_id = NULL, $type, $submit_on_change = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo GL_QuickEntry::select($name, $selected_id, $type, $submit_on_change);
      echo "</td>";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param      $type
     * @param bool $submit_on_change
     */
    static public function row($label, $name, $selected_id = NULL, $type, $submit_on_change = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      GL_QuickEntry::cells(NULL, $name, $selected_id, $type, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    static public function actions($label, $name, $selected_id = NULL, $submit_on_change = FALSE) {
      global $quick_actions;
      echo "<tr><td class='label'>$label</td><td>";
      echo array_selector($name, $selected_id, $quick_actions, array('select_submit' => $submit_on_change));
      echo "</td></tr>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    static public function types($label, $name, $selected_id = NULL, $submit_on_change = FALSE) {
      global $quick_entry_types;
      echo "<tr><td class='label'>$label</td><td>";
      echo array_selector($name, $selected_id, $quick_entry_types, array('select_submit' => $submit_on_change));
      echo "</td></tr>\n";
    }
  }
