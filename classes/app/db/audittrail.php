<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class DB_AuditTrail {

    /**
     * @static
     *
     * @param        $trans_type
     * @param        $trans_no
     * @param        $trans_date
     * @param string $descr
     */
    public static function add($trans_type, $trans_no, $trans_date, $descr = '') {
      $insertid = DB::insert('audit_trail')
        ->values(array(
        'type'        => $trans_type,
        'trans_no'    => $trans_no,
        'user'        => User::i()->user,
        'fiscal_year' => DB_Company::get_pref('f_year'),
        'gl_date'     => Dates::date2sql($trans_date),
        'description' => $descr,
        'gl_seq'      => 0
      ))->exec();
      // all audit records beside latest one should have gl_seq set to NULL
      // to avoid need for subqueries (not existing in MySQL 3) all over the code
      DB::update('audit_trail')->value('gl_seq', NULL)->where('type=', $trans_type)->and_where('trans_no=', $trans_no)
        ->and_where('id!=', $insertid)->exec();
    }

    /*
         * Confirm and close for edition all transactions up to date $todate,
                   and reindex	journal. */
    /**
     * @static
     *
     * @param $todate
     */
    public static function close_transactions($todate) {
      $errors = 0;
      $sql    = "SELECT DISTINCT a.id, a.gl_date, a.fiscal_year"
        . " FROM gl_trans gl"
        . " LEFT JOIN audit_trail a ON
					(gl.type=a.type AND gl.type_no=a.trans_no)"
        . " WHERE gl_date<='" . Dates::date2sql($todate) . "'"
        . " AND NOT ISNULL(gl_seq)"
        . " ORDER BY a.fiscal_year, a.gl_date, a.id";
      $result = DB::query($sql, "Cannot select transactions for closing");
      if (DB::num_rows($result)) {
        $last_year = $counter = 0;
        while ($row = DB::fetch($result)) {
          if ($row['fiscal_year'] == NULL) {
            $errors = 1;
          }
          elseif ($last_year != $row['fiscal_year']) {
            $last_year = $row['fiscal_year'];
            $counter   = 1; // reset counter on fiscal year change
          }
          else {
            $counter++;
          }
          DB::update('audit_trail')->value('gl_seq', $counter)->where('id=', $row['id'])->exec();
        }
      }
      if ($errors) {
        Event::warning(_("Some transactions journal GL postings were not indexed due to lack of audit trail record."));
      }
    }

    /* Closed transactions have gl_seq number assigned. */

    /**
     * @static
     *
     * @param $trans_type
     * @param $trans_no
     *
     * @return array
     */
    public static function get_all($trans_type, $trans_no) {
      $result = DB::select()->from('audit_trail')->where('type=', $trans_type)->and_where('trans_no-', $trans_no)->fetch()
        ->all();
      return $result;
    }

    /**
     * @static
     *
     * @param $trans_type
     * @param $trans_no
     *
     * @return mixed
     */
    public static function get_last($trans_type, $trans_no) {
      $result = DB::select()->from('audit_trail')->where('type=', $trans_type)->and_where('trans_no-', $trans_no)
        ->and_where("NOT ISNULL(gl_seq)")->fetch()->one();
      return $result;
    }

    /**
     * @static
     *
     * @param $type
     * @param $trans_no
     *
     * @return int
     */
    public static function is_closed_trans($type, $trans_no) {
      $sql = "SELECT	gl_seq FROM audit_trail"
        . " WHERE type=" . DB::escape($type)
        . " AND trans_no=" . DB::escape($trans_no)
        . " AND gl_seq>0";
      return DB::num_rows($sql);
    }

    /*
           Reopen all transactions for edition up from date $fromdate
         */
    /**
     * @static
     *
     * @param $fromdate
     */
    public static function open_transactions($fromdate) {
      $sql    = "SELECT a.id, a.gl_date, a.fiscal_year"
        . " FROM gl_trans gl"
        . " LEFT JOIN audit_trail a ON
			(gl.type=a.type AND gl.type_no=a.trans_no)"
        . " WHERE gl_date>='" . Dates::date2sql($fromdate) . "'"
        . " AND !ISNULL(gl_seq)"
        . " ORDER BY a.fiscal_year, a.gl_date, a.id";
      $result = DB::query($sql, "Cannot select transactions for openning");
      if (DB::num_rows($result)) {
        while ($row = DB::fetch($result)) {
          if ($row['fiscal_year'] == NULL) {
            continue;
          }
          DB::update('audit_trail')->value('gl_seq', 0)->where('id=', $row['id'])->exec();
        }
      }
    }
  }
