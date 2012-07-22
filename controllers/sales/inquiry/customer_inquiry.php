<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class SalesInquirey extends \ADV\App\Controller\Base
  {
    public $isAjaxSearch;
    public $filterType;
    public $customer_id;
    const SEARCH_DELIVERY = 'd';
    const SEARCH_INVOICE  = 'i';
    const SEARCH_PAYMENT  = 'p';
    protected function before() {
      JS::openWindow(900, 500);
      if (isset($_GET['id'])) {
        $_GET['customer_id'] = $_GET['id'];
      }
      $this->customer_id  = Input::$post['customer_id'] = Input::postGetGlobal('customer_id', INPUT::NUMERIC, null);
      $this->filterType   = Input::$post['filterType'] = Input::post('filterType', Input::NUMERIC);
      $this->isAjaxSearch = (AJAX_REFERRER && isset($_POST['ajaxsearch']));
    }
    protected function index() {
      Page::start(_($help_context = "Customer Transactions"), SA_SALESTRANSVIEW, Input::$get->has('customer_id'));
      Forms::start();
      Table::start('tablestyle_noborder');
      Row::start();
      Debtor::newselect(null, ['label'=> false, 'row'=> false]);
      Forms::refCells(_("#"), 'reference', '', null, '', true);
      Forms::dateCells(_("From:"), 'TransAfterDate', '', null, -30);
      Forms::dateCells(_("To:"), 'TransToDate', '', null, 1);
      Debtor_Payment::allocations_select(null, 'filterType', $this->filterType, true);
      Forms::submitCells('RefreshInquiry', _("Search"), '', _('Refresh Inquiry'), 'default');
      Row::end();
      Table::end();
      Display::div_start('totals_tbl');
      $this->displaySummary();
      Display::div_end();
      if (Input::post('RefreshInquiry')) {
        Ajax::activate('totals_tbl');
      }
      $sql = ($this->isAjaxSearch) ? $this->prepareAjaxSearch() : $this->prepareSearch();
      if (Input::post('reference')) {
        $number_like = "%" . $_POST['reference'] . "%";
        $sql .= " AND trans.reference LIKE " . DB::quote($number_like);
      }
      if ($this->customer_id) {
        $sql .= " AND trans.debtor_id = " . DB::quote($this->customer_id);
      }
      if ($this->filterType) {
        switch ($this->filterType) {
          case '1':
            $sql .= " AND (trans.type = " . ST_SALESINVOICE . " OR trans.type = " . ST_BANKPAYMENT . ") ";
            break;
          case '2':
            $sql .= " AND (trans.type = " . ST_SALESINVOICE . ") AND trans.due_date < '" . Dates::today(true) . "'
    				AND (trans.ov_amount + trans.ov_gst + trans.ov_freight_tax +
    				trans.ov_freight + trans.ov_discount - trans.alloc > 0)";
            break;
          case '3':
            $sql .= " AND (trans.type = " . ST_CUSTPAYMENT . " OR trans.type = " . ST_CUSTREFUND . " OR trans.type = " . ST_BANKDEPOSIT . " OR trans.type = " . ST_BANKDEPOSIT . ") ";
            break;
          case '4':
            $sql .= " AND trans.type = " . ST_CUSTCREDIT . " ";
            break;
          case '5':
            $sql .= " AND trans.type = " . ST_CUSTDELIVERY . " ";
            break;
          case '6':
            $sql .= " AND trans.type = " . ST_SALESINVOICE . " ";
            break;
        }
      }
      if (!$this->isAjaxSearch) {
        $sql .= " GROUP BY trans.trans_no, trans.type";
      }
      DB::query("set @bal:=0");
      $cols = array(
        _("Type")                                                    => array(
          'fun'    => function ($dummy, $type) {
            global $systypes_array;
            return $systypes_array[$type];
          }, 'ord' => ''
        ),
        _("#")                                                       => array(
          'fun'    => [$this, 'viewTrans'], 'ord' => ''
        ),
        _("Order")                                                   => array(
          'fun' => function ($row) {
            return $row['order_'] > 0 ? Debtor::viewTrans(ST_SALESORDER, $row['order_']) : "";
          }
        ),
        _("Reference")                                               => array('ord' => ''),
        _("Date")                                                    => array(
          'name' => 'tran_date', 'type' => 'date', 'ord'  => 'desc'
        ),
        _("Due Date")                                                => array(
          'type' => 'date', 'fun' => function ($row) {
            return $row["type"] == ST_SALESINVOICE ? $row["due_date"] : '';
          }
        ),
        _("Customer")                                                => array('ord' => 'asc'),
        array('type' => 'skip'),
        _("Branch")                                                  => array('ord' => ''),
        _("Currency")                                                => array('align' => 'center', 'type' => 'skip'),
        _("Debit")                                                   => array(
          'align' => 'right', 'fun' => function ($row) {
            $value = $row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_BANKDEPOSIT ?
              -$row["TotalAmount"] : $row["TotalAmount"];
            return $value >= 0 ? Num::priceFormat($value) : '';
          }
        ),
        _("Credit")                                                  => array(
          'align' => 'right', 'insert' => true, 'fun' => function ($row) {
            $value = !($row['type'] == ST_CUSTCREDIT || $row['type'] == ST_CUSTREFUND || $row['type'] == ST_CUSTPAYMENT || $row['type'] == ST_BANKDEPOSIT) ?
              -$row["TotalAmount"] : $row["TotalAmount"];
            return $value > 0 ? Num::priceFormat($value) : '';
          }
        ),
        array('type' => 'skip'),
        _("RB")                                                      => array('align' => 'right', 'type' => 'amount'),
        array(
          'insert' => true, 'fun' => function ($row) {
          return GL_UI::view($row["type"], $row["trans_no"]);
        }
        ),
        array(
          'insert' => true, 'align' => 'center', 'fun' => function ($row) {
          return $row['type'] == ST_SALESINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
            DB_Pager::link(_("Credit"), "/sales/customer_credit_invoice.php?InvoiceNumber=" . $row['trans_no'], ICON_CREDIT) : '';
        }
        ),
        array(
          'insert' => true, 'align' => 'center', 'fun' => function ($row) {
          return $row['type'] == ST_SALESINVOICE && $row["TotalAmount"] - $row["Allocated"] > 0 ?
            DB_Pager::link(_("Payment"), "/sales/customer_payments.php?customer_id=" . $row['debtor_id'], ICON_MONEY) : '';
        }
        ),
        array(
          'insert' => true, 'align' => 'center', 'fun' => function ($row) {
          $str = '';
          switch ($row['type']) {
            case ST_SALESINVOICE:
              if (Voiding::get(ST_SALESINVOICE, $row["trans_no"]) === false || AJAX_REFERRER) {
                if ($row['Allocated'] == 0) {
                  $str = "/sales/customer_invoice.php?ModifyInvoice=" . $row['trans_no'];
                } else {
                  $str = "/sales/customer_invoice.php?ViewInvoice=" . $row['trans_no'];
                }
              }
              break;
            case ST_CUSTCREDIT:
              if (Voiding::get(ST_CUSTCREDIT, $row["trans_no"]) === false && $row['Allocated'] == 0) {
                if ($row['order_'] == 0) {
                  $str = "/sales/credit_note_entry.php?ModifyCredit=" . $row['trans_no'];
                } else {
                  $str = "/sales/customer_credit_invoice.php?ModifyCredit=" . $row['trans_no'];
                }
              }
              break;
            case ST_CUSTDELIVERY:
              if ($row['still_to_deliver'] == 0) {
                continue;
              }
              if (Voiding::get(ST_CUSTDELIVERY, $row["trans_no"]) === false) {
                $str = "/sales/customer_delivery.php?ModifyDelivery=" . $row['trans_no'];
              }
              break;
          }
          if (!$str && (!DB_AuditTrail::is_closed_trans($row['type'], $row["trans_no"]) || $row['type'] == ST_SALESINVOICE)) {
            return DB_Pager::link(_('Edit'), $str, ICON_EDIT);
          }
          return '';
        }
        ),
        array(
          'insert' => true, 'align' => 'center', 'fun' => function ($row) {
          if ($row['type'] != ST_SALESINVOICE) {
            return '';
          }
          HTML::setReturn(true);
          UI::button(false, 'Email', array(
                                          'class'        => 'button email-button',
                                          'data-emailid' => $row['debtor_id'] . '-' . $row['type'] . '-' . $row['trans_no']
                                     ));
          return HTML::setReturn(false);
        }
        ),
        array(
          'insert' => true, 'align' => 'center', 'fun' => function ($row) {
          if ($row['type'] != ST_CUSTPAYMENT && $row['type'] != ST_CUSTREFUND && $row['type'] != ST_BANKDEPOSIT) // customer payment or bank deposit printout not defined yet.
          {
            return Reporting::print_doc_link($row['trans_no'] . "-" . $row['type'], _("Print"), true, $row['type'], ICON_PRINT, 'button printlink');
          } else {
            return Reporting::print_doc_link($row['trans_no'] . "-" . $row['type'], _("Receipt"), true, $row['type'], ICON_PRINT, 'button printlink');
          }
        }
        )
      );
      if ($this->customer_id) {
        $cols[_("Customer")] = 'skip';
        $cols[_("Currency")] = 'skip';
      }
      if (!$this->filterType || !$this->isAjaxSearch) {
        $cols[_("RB")] = 'skip';
      }
      $table = db_pager::new_db_pager('trans_tbl', $sql, $cols);
      $table->setMarker(function ($row) {
        return (isset($row['OverDue']) && $row['OverDue'] == 1) && (Num::round(abs($row["TotalAmount"]) - $row["Allocated"], 2) != 0);
      }, _("Marked items are overdue."));
      $table->width = "80%";
      $table->display($table);
      UI::emailDialogue(CT_CUSTOMER);
      Forms::end();
      Page::end();
    }
    /**
     * @param $trans
     *
     * @return null|string
     */
    protected function viewTrans($trans) {
      return GL_UI::viewTrans($trans["type"], $trans["trans_no"]);
    }
    /**
     * @return string
     */
    protected function prepareSearch() {
      $date_to    = Dates::dateToSql($_POST['TransToDate']);
      $date_after = Dates::dateToSql($_POST['TransAfterDate']);
      $sql
                  = "SELECT
         		trans.type,
        		trans.trans_no,
        		trans.order_,
        		trans.reference,
        		trans.tran_date,
        		trans.due_date,
        		debtor.name,
        		debtor.debtor_id,
        		branch.br_name,
        		debtor.curr_code,
        		(trans.ov_amount + trans.ov_gst + trans.ov_freight
        			+ trans.ov_freight_tax + trans.ov_discount)	AS TotalAmount, ";
      if ($this->filterType) {
        $sql .= "@bal := @bal+(trans.ov_amount + trans.ov_gst + trans.ov_freight + trans.ov_freight_tax + trans.ov_discount), ";
      }
      $sql
        .= "trans.alloc AS Allocated,
        		((trans.type = " . ST_SALESINVOICE . ")
        			AND trans.due_date < '" . Dates::today(true) . "') AS OverDue, SUM(details.quantity - qty_done) as
        			still_to_deliver
        		FROM debtors as debtor, branches as branch,debtor_trans as trans
        		LEFT JOIN debtor_trans_details as details ON (trans.trans_no = details.debtor_trans_no AND trans.type = details.debtor_trans_type) WHERE debtor.debtor_id =
        		trans.debtor_id AND trans.branch_id = branch.branch_id";
      $sql
        .= " AND trans.tran_date >= '$date_after' AND trans.tran_date <= '$date_to'";
      $this->Ajax->_activate('_page_body');
      return $sql;
    }
    /**
     * @return string
     */
    protected function prepareAjaxSearch() {
      $searchArray = trim($_POST['ajaxsearch']);
      $searchArray = explode(' ', $searchArray);
      if ($searchArray[0] == self::SEARCH_DELIVERY) {
        $filter = " AND type = " . ST_CUSTDELIVERY . " ";
      } elseif ($searchArray[0] == self::SEARCH_INVOICE) {
        $filter = " AND (type = " . ST_SALESINVOICE . " OR type = " . ST_BANKPAYMENT . ") ";
      } elseif ($searchArray[0] == self::SEARCH_PAYMENT) {
        $filter = " AND (type = " . ST_CUSTPAYMENT . " OR type = " . ST_CUSTREFUND . " OR type = " . ST_BANKDEPOSIT . ") ";
      }
      $sql = "SELECT * FROM debtor_trans_view WHERE ";
      foreach ($searchArray as $key => $ajaxsearch) {
        if (empty($ajaxsearch)) {
          continue;
        }
        $sql .= ($key == 0) ? " (" : " AND (";
        if ($ajaxsearch[0] == "$") {
          if (substr($ajaxsearch, -1) == 0 && substr($ajaxsearch, -3, 1) == '.') {
            $ajaxsearch = (substr($ajaxsearch, 0, -1));
          }
          $sql .= "TotalAmount LIKE " . DB::quote('%' . substr($ajaxsearch, 1) . '%') . ") ";
          continue;
        }
        if (stripos($ajaxsearch, $this->User->_date_sep()) > 0) {
          $sql .= " tran_date LIKE '%" . Dates::dateToSql($ajaxsearch, false) . "%' OR";
          continue;
        }
        if (is_numeric($ajaxsearch)) {
          $sql .= " debtor_id = $ajaxsearch OR ";
        }
        $search_value = DB::quote("%" . $ajaxsearch . "%");
        $sql .= " name LIKE $search_value ";
        if (is_numeric($ajaxsearch)) {
          $sql .= " OR trans_no LIKE $search_value OR order_ LIKE $search_value ";
        }
        $sql .= " OR reference LIKE $search_value OR br_name LIKE $search_value) ";
      }
      if (isset($filter) && $filter) {
        $sql .= $filter;
      }
      return $sql;
    }
    protected function displaySummary() {
      if ($this->customer_id && !$this->isAjaxSearch) {
        $customer_record = Debtor::get_details($this->customer_id, $_POST['TransToDate']);
        Debtor::display_summary($customer_record);
        echo "<br>";
      }
    }
  }

  new SalesInquirey();
