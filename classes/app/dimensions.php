<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   adv.accounts.app
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Dimensions {

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
    static public function add($reference, $name, $type_, $date_, $due_date, $memo_) {
      DB::begin();
      $date = Dates::date2sql($date_);
      $duedate = Dates::date2sql($due_date);
      $sql
        = "INSERT INTO dimensions (reference, name, type_, date_, due_date)
		VALUES (" . DB::escape($reference) . ", " . DB::escape($name) . ", " . DB::escape($type_) . ", '$date', '$duedate')";
      DB::query($sql, "could not add dimension");
      $id = DB::insert_id();
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
    static public function update($id, $name, $type_, $date_, $due_date, $memo_) {
      DB::begin();
      $date = Dates::date2sql($date_);
      $duedate = Dates::date2sql($due_date);
      $sql = "UPDATE dimensions SET name=" . DB::escape($name) . ",
		type_ = " . DB::escape($type_) . ",
		date_='$date',
		due_date='$duedate'
		WHERE id = " . DB::escape($id);
      DB::query($sql, "could not update dimension");
      DB_Comments::update(ST_DIMENSION, $id, NULL, $memo_);
      DB::commit();
      return $id;
    }
    /**
     * @static
     *
     * @param $id
     */
    static public function delete($id) {
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
     * @return DB_Query_Result
     */
    static public function get($id, $allow_null = FALSE) {
      $sql = "SELECT * FROM dimensions	WHERE id=" . DB::escape($id);
      $result = DB::query($sql, "The dimension could not be retrieved");
      if (!$allow_null && DB::num_rows($result) == 0) {
        Errors::db_error("Could not find dimension $id", $sql);
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
    static public function get_string($id, $html = FALSE, $space = ' ') {
      if ($id <= 0) {
        if ($html) {
          $dim = "&nbsp;";
        }
        else {
          $dim = "";
        }
      }
      else {
        $row = Dimensions::get($id, TRUE);
        $dim = $row['reference'] . $space . $row['name'];
      }
      return $dim;
    }
    /**
     * @static
     * @return null|PDOStatement
     */
    static public function get_all() {
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
    static public function has_deposits($id) {
      return Dimensions::has_payments($id);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return bool
     */
    static public function has_payments($id) {
      $sql = "SELECT SUM(amount) FROM gl_trans WHERE dimension_id = " . DB::escape($id);
      $res = DB::query($sql, "Transactions could not be calculated");
      $row = DB::fetch_row($res);
      return ($row[0] != 0.0);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return bool
     */
    static public function is_closed($id) {
      $result = Dimensions::get($id);
      return ($result['closed'] == '1');
    }
    /**
     * @static
     *
     * @param $id
     */
    static public function close($id) {
      $sql = "UPDATE dimensions SET closed='1' WHERE id = " . DB::escape($id);
      DB::query($sql, "could not close dimension");
    }
    /**
     * @static
     *
     * @param $id
     */
    static public function reopen($id) {
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
    static public function display_balance($id, $from, $to) {
      $from = Dates::date2sql($from);
      $to = Dates::date2sql($to);
      $sql
        = "SELECT account, chart_master.account_name, sum(amount) AS amt FROM
			gl_trans,chart_master WHERE
			gl_trans.account = chart_master.account_code AND
			(dimension_id = $id OR dimension2_id = $id) AND
			tran_date >= '$from' AND tran_date <= '$to' GROUP BY account";
      $result = DB::query($sql, "Transactions could not be calculated");
      if (DB::num_rows($result) == 0) {
        Event::warning(_("There are no transactions for this dimension for the selected period."));
      }
      else {
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
          Cell::amount($total, TRUE);
          Cell::label("");
        }
        else {
          Cell::label("");
          Cell::amount(abs($total), TRUE);
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
    static public function select($name, $selected_id = NULL, $no_option = FALSE, $showname = ' ', $submit_on_change = FALSE, $showclosed = FALSE, $showtype = 1) {
      $sql = "SELECT id, CONCAT(reference,' ',name) as ref FROM dimensions";
      $options = array(
        'order' => 'reference', 'spec_option' => $no_option ? $showname :
          FALSE, 'spec_id' => 0, 'select_submit' => $submit_on_change, 'async' => FALSE
      );
      if (!$showclosed) {
        $options['where'][] = "closed=0";
      }
      if ($showtype) {
        $options['where'][] = "type_=$showtype";
      }
      return select_box($name, $selected_id, $sql, 'id', 'ref', $options);
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
    static public function cells($label, $name, $selected_id = NULL, $no_option = FALSE, $showname = NULL, $showclosed = FALSE, $showtype = 0, $submit_on_change = FALSE) {
      if ($label != NULL) {
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
    static public function select_row($label, $name, $selected_id = NULL, $no_option = FALSE, $showname = NULL, $showclosed = FALSE, $showtype = 0, $submit_on_change = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      Dimensions::cells(NULL, $name, $selected_id, $no_option, $showname, $showclosed, $showtype, $submit_on_change);
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
    static public function trans_view($type, $trans_no, $label = "", $icon = FALSE, $class = '', $id = '') {
      if ($type == ST_DIMENSION) {
        $viewer = "dimensions/view/view_dimension.php?trans_no=$trans_no";
      }
      else {
        return NULL;
      }
      if ($label == "") {
        $label = $trans_no;
      }
      return Display::viewer_link($label, $viewer, $class, $id, $icon);
    }
  }


