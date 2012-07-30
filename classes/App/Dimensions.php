<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Dimensions
  {
    /**
     * @static
     *
     * @param $reference
     * @param $name
     * @param $type_
     * @param $date_
     * @param $due_date
     * @param $memo_
     *
     * @return mixed
     */
    public static function add($reference, $name, $type_, $date_, $due_date, $memo_) {
      DB::begin();
      $date    = Dates::dateToSql($date_);
      $duedate = Dates::dateToSql($due_date);
      $sql     = "INSERT INTO dimensions (reference, name, type_, date_, due_date)
		VALUES (" . DB::escape($reference) . ", " . DB::escape($name) . ", " . DB::escape($type_) . ", '$date', '$duedate')";
      DB::query($sql, "could not add dimension");
      $id = DB::insertId();
      DB_Comments::add(ST_DIMENSION, $id, $date_, $memo_);
      Ref::save(ST_DIMENSION, $id, $reference);
      DB::commit();
      return $id;
    }
    /**
     * @static
     *
     * @param $id
     * @param $name
     * @param $type_
     * @param $date_
     * @param $due_date
     * @param $memo_
     *
     * @return mixed
     */
    public static function update($id, $name, $type_, $date_, $due_date, $memo_) {
      DB::begin();
      $date    = Dates::dateToSql($date_);
      $duedate = Dates::dateToSql($due_date);
      $sql     = "UPDATE dimensions SET name=" . DB::escape($name) . ",
		type_ = " . DB::escape($type_) . ",
		date_='$date',
		due_date='$duedate'
		WHERE id = " . DB::escape($id);
      DB::query($sql, "could not update dimension");
      DB_Comments::update(ST_DIMENSION, $id, null, $memo_);
      DB::commit();
      return $id;
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function delete($id) {
      DB::begin();
      // delete the actual dimension
      $sql = "DELETE FROM dimensions WHERE id=" . DB::escape($id);
      DB::query($sql, "The dimension could not be deleted");
      DB_Comments::delete(ST_DIMENSION, $id);
      DB::commit();
    }
    /**
     * @static
     *
     * @param      $id
     * @param bool $allow_null
     *
     * @return \ADV\Core\DB\Query\Result
     */
    public static function get($id, $allow_null = false) {
      $sql    = "SELECT * FROM dimensions	WHERE id=" . DB::escape($id);
      $result = DB::query($sql, "The dimension could not be retrieved");
      if (!$allow_null && DB::numRows($result) == 0) {
        Errors::databaseError("Could not find dimension $id", $sql);
      }
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param        $id
     * @param bool   $html
     * @param string $space
     *
     * @return string
     */
    public static function get_string($id, $html = false, $space = ' ') {
      if ($id <= 0) {
        if ($html) {
          $dim = "&nbsp;";
        } else {
          $dim = "";
        }
      } else {
        $row = Dimensions::get($id, true);
        $dim = $row['reference'] . $space . $row['name'];
      }
      return $dim;
    }
    /**
     * @static
     * @return null|PDOStatement
     */
    public static function getAll() {
      $sql = "SELECT * FROM dimensions ORDER BY date_";
      return DB::query($sql, "The dimensions could not be retrieved");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return bool
     */
    public static function has_deposits($id) {
      return Dimensions::has_payments($id);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return bool
     */
    public static function has_payments($id) {
      $sql = "SELECT SUM(amount) FROM gl_trans WHERE dimension_id = " . DB::escape($id);
      $res = DB::query($sql, "Transactions could not be calculated");
      $row = DB::fetchRow($res);
      return ($row[0] != 0.0);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return bool
     */
    public static function is_closed($id) {
      $result = Dimensions::get($id);
      return ($result['closed'] == '1');
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function close($id) {
      $sql = "UPDATE dimensions SET closed='1' WHERE id = " . DB::escape($id);
      DB::query($sql, "could not close dimension");
    }
    /**
     * @static
     *
     * @param $id
     */
    public static function reopen($id) {
      $sql = "UPDATE dimensions SET closed='0' WHERE id = $id";
      DB::query($sql, "could not reopen dimension");
    }
    /**
     * @static
     *
     * @param $id
     * @param $from
     * @param $to
     */
    public static function display_balance($id, $from, $to) {
      $from   = Dates::dateToSql($from);
      $to     = Dates::dateToSql($to);
      $sql    = "SELECT account, chart_master.account_name, sum(amount) AS amt FROM
			gl_trans,chart_master WHERE
			gl_trans.account = chart_master.account_code AND
			(dimension_id = $id OR dimension2_id = $id) AND
			tran_date >= '$from' AND tran_date <= '$to' GROUP BY account";
      $result = DB::query($sql, "Transactions could not be calculated");
      if (DB::numRows($result) == 0) {
        Event::warning(_("There are no transactions for this dimension for the selected period."));
      } else {
        Display::heading(_("Balance for this Dimension"));
        Display::br();
        Table::start('tablestyle grid');
        $th = array(_("Account"), _("Debit"), _("Credit"));
        Table::header($th);
        $total = $k = 0;
        while ($myrow = DB::fetch($result)) {
          Cell::label($myrow["account"] . " " . $myrow['account_name']);
          Cell::debitOrCredit($myrow["amt"]);
          $total += $myrow["amt"];
          Row::end();
        }
        Row::start();
        Cell::label("<span class='bold'>" . _("Balance") . "</span>");
        if ($total >= 0) {
          Cell::amount($total, true);
          Cell::label("");
        } else {
          Cell::label("");
          Cell::amount(abs($total), true);
        }
        Row::end();
        Table::end();
      }
    }
    // DIMENSIONS
    /**
     * @static
     *
     * @param        $name
     * @param null   $selected_id
     * @param bool   $no_option
     * @param string $showname
     * @param bool   $submit_on_change
     * @param bool   $showclosed
     * @param int    $showtype
     *
     * @return string
     */
    public static function select($name, $selected_id = null, $no_option = false, $showname = ' ', $submit_on_change = false, $showclosed = false, $showtype = 1) {
      $sql     = "SELECT id, CONCAT(reference,' ',name) as ref FROM dimensions";
      $options = array(
        'order'            => 'reference', 'spec_option'      => $no_option ? $showname : false, 'spec_id'          => 0, 'select_submit'    => $submit_on_change, 'async'            => false
      );
      if (!$showclosed) {
        $options['where'][] = "closed=0";
      }
      if ($showtype) {
        $options['where'][] = "type_=$showtype";
      }
      return Forms::selectBox($name, $selected_id, $sql, 'id', 'ref', $options);
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $no_option
     * @param null $showname
     * @param bool $showclosed
     * @param int  $showtype
     * @param bool $submit_on_change
     */
    public static function cells($label, $name, $selected_id = null, $no_option = false, $showname = null, $showclosed = false, $showtype = 0, $submit_on_change = false) {
      if ($label != null) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Dimensions::select($name, $selected_id, $no_option, $showname, $submit_on_change, $showclosed, $showtype);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $no_option
     * @param null $showname
     * @param bool $showclosed
     * @param int  $showtype
     * @param bool $submit_on_change
     */
    public static function select_row($label, $name, $selected_id = null, $no_option = false, $showname = null, $showclosed = false, $showtype = 0, $submit_on_change = false) {
      echo "<tr><td class='label'>$label</td>";
      Dimensions::cells(null, $name, $selected_id, $no_option, $showname, $showclosed, $showtype, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param        $type
     * @param        $trans_no
     * @param string $label
     * @param bool   $icon
     * @param string $class
     * @param string $id
     *
     * @return null|string
     */
    public static function viewTrans($type, $trans_no, $label = "", $icon = false, $class = '', $id = '') {
      if ($type == ST_DIMENSION) {
        $viewer = "dimensions/view/view_dimension.php?trans_no=$trans_no";
      } else {
        return null;
      }
      if ($label == "") {
        $label = $trans_no;
      }
      return Display::viewer_link($label, $viewer, $class, $id, $icon);
    }
  }


