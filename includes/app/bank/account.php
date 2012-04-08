<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Bank_Account {
    /**
     * @static
     *
     * @param $account_code
     * @param $account_type
     * @param $bank_account_name
     * @param $bank_name
     * @param $bank_account_number
     * @param $bank_address
     * @param $bank_curr_code
     * @param $dflt_curr_act
     */
    static public function add($account_code, $account_type, $bank_account_name, $bank_name, $bank_account_number, $bank_address, $bank_curr_code, $dflt_curr_act) {
      if ($dflt_curr_act) // only one default account for any currency
      {
        Bank_Currency::clear_default($bank_curr_code);
      }
      $sql = "INSERT INTO bank_accounts (account_code, account_type,
		bank_account_name, bank_name, bank_account_number, bank_address, 
		bank_curr_code, dflt_curr_act)
		VALUES (" . DB::escape($account_code) . ", " . DB::escape($account_type) . ", " . DB::escape($bank_account_name) . ", " . DB::escape($bank_name) . ", " . DB::escape($bank_account_number) . "," . DB::escape($bank_address) . ", " . DB::escape($bank_curr_code) . ", " . DB::escape($dflt_curr_act) . ")";
      DB::query($sql, "could not add a bank account for $account_code");
    }
    /**
     * @static
     *
     * @param $id
     * @param $account_code
     * @param $account_type
     * @param $bank_account_name
     * @param $bank_name
     * @param $bank_account_number
     * @param $bank_address
     * @param $bank_curr_code
     * @param $dflt_curr_act
     */
    static public function update($id, $account_code, $account_type, $bank_account_name, $bank_name, $bank_account_number, $bank_address, $bank_curr_code, $dflt_curr_act) {
      if ($dflt_curr_act) // only one default account for any currency
      {
        Bank_Currency::clear_default($bank_curr_code);
      }
      $sql = "UPDATE bank_accounts	SET account_type = " . DB::escape($account_type) . ",
		account_code=" . DB::escape($account_code) . ",
		bank_account_name=" . DB::escape($bank_account_name) . ", bank_name=" . DB::escape($bank_name) . ",
		bank_account_number=" . DB::escape($bank_account_number) . ", bank_curr_code=" . DB::escape($bank_curr_code) . ",
		bank_address=" . DB::escape($bank_address) . ",
		dflt_curr_act=" . DB::escape($dflt_curr_act) . " WHERE id = " . DB::escape($id);
      DB::query($sql, "could not update bank account for $account_code");
    }
    /**
     * @static
     *
     * @param $id
     */
    static public function delete($id) {
      $sql = "DELETE FROM bank_accounts WHERE id=" . DB::escape($id);
      DB::query($sql, "could not delete bank account for $id");
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get($id) {
      $sql = "SELECT * FROM bank_accounts WHERE id=" . DB::escape($id);
      $result = DB::query($sql, "could not retreive bank account for $id");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $id
     *
     * @return mixed
     */
    static public function get_gl($id) {
      $sql = "SELECT account_code FROM bank_accounts WHERE id=" . DB::escape($id);
      $result = DB::query($sql, "could not retreive bank account for $id");
      $bank_account = DB::fetch($result);
      return $bank_account['account_code'];
    }
    /**
     * @static
     *
     * @param $curr
     *
     * @return ADV\Core\DB\Query_Result|Array
     */
    static public function get_default($curr) {
      /* default bank account is selected as first found account from:
                         . default account in $curr if any
                         . first defined account in $curr if any
                         . default account in home currency
                         . first defined account in home currency
                       */
      $home_curr = DB_Company::get_pref('curr_default');
      $sql = "SELECT b.*, b.bank_curr_code='$home_curr' as fall_back FROM " . "bank_accounts b" . " WHERE b.bank_curr_code=" . DB::escape($curr) . " OR b.bank_curr_code='$home_curr'
		ORDER BY fall_back, dflt_curr_act desc";
      $result = DB::query($sql, "could not retreive default bank account");
      return DB::fetch($result);
    }
    /**
     * @static
     *
     * @param $cust_id
     *
     * @return mixed
     */
    static public function get_customer_default($cust_id) {
      $sql = "SELECT curr_code FROM debtors WHERE debtor_no=" . DB::escape($cust_id);
      $result = DB::query($sql, "could not retreive default customer currency code");
      $row = DB::fetch_row($result);
      $ba = static::get_default($row[0]);
      return $ba['id'];
    }
    /**
     * @static
     *
     * @param $account_code
     *
     * @return bool
     */
    static public function is($account_code) {
      $sql = "SELECT id FROM bank_accounts WHERE account_code='$account_code'";
      $result = DB::query($sql, "checking account is bank account");
      if (DB::num_rows($result) > 0) {
        $acct = DB::fetch($result);
        return $acct['id'];
      }
      else {
        return FALSE;
      }
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     *
     * @return string
     */
    static public function  select($name, $selected_id = NULL, $submit_on_change = FALSE) {
      $sql = "SELECT bank_accounts.id, bank_account_name, bank_curr_code, inactive
												FROM bank_accounts";
      return select_box($name, $selected_id, $sql, 'id', 'bank_account_name', array(
        'format' => '_format_add_curr',
        'select_submit' => $submit_on_change,
        'async' => FALSE
      ));
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    static public function  cells($label, $name, $selected_id = NULL, $submit_on_change = FALSE) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Bank_Account::select($name, $selected_id, $submit_on_change);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     * @param bool $submit_on_change
     */
    static public function  row($label, $name, $selected_id = NULL, $submit_on_change = FALSE) {
      echo "<tr><td class='label'>$label</td>";
      Bank_Account::cells(NULL, $name, $selected_id, $submit_on_change);
      echo "</tr>\n";
    }
    /**
     * @static
     *
     * @param      $name
     * @param null $selected_id
     *
     * @return string
     */
    static public function  type($name, $selected_id = NULL) {
      global $bank_account_types;
      return array_selector($name, $selected_id, $bank_account_types);
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    static public function  type_cells($label, $name, $selected_id = NULL) {
      if ($label != NULL) {
        echo "<td>$label</td>\n";
      }
      echo "<td>";
      echo Bank_Account::type($name, $selected_id);
      echo "</td>\n";
    }
    /**
     * @static
     *
     * @param      $label
     * @param      $name
     * @param null $selected_id
     */
    static public function  type_row($label, $name, $selected_id = NULL) {
      echo "<tr><td class='label'>$label</td>";
      Bank_Account::type_cells(NULL, $name, $selected_id);
      echo "</tr>\n";
    }
  }
