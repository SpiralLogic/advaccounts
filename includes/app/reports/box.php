<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/
  class Reports_Box extends Report {

    public $ar_classes;
    public $ctrl_handlers = array();
    /**
     *
     */
    public function __construct() {
    }
    public function reportClasses() {
      $this->ar_classes = array();
    }
    /**
     * @param $class_name
     */
    public function addReportClass($class_name) {
      $this->ar_classes[$class_name] = array();
    }
    /**
     * @param      $class_name
     * @param      $id
     * @param      $rep_name
     * @param null $params
     */
    public function addReport($class_name, $id, $rep_name, $params = NULL) {
      unset($this->ar_classes[$class_name][$id]); // unset std report if any
      $this->ar_classes[$class_name][$id] = new Report($id, $rep_name, $params);
    }
    /**
     * @param null $class
     *
     * @return string
     */
    public function getDisplay($class = NULL) {
      $Ajax = Ajax::i();
      $temp = array_values($this->ar_classes);
      $display_class = $class == NULL ? $temp[0] : $this->ar_classes[$class];
      $class_counter = 0;
      $rep_counter = 0;
      $st_reports = "";
      $st_params = "";
      $st_classes = "<span class='bold'>" . _("Report Classes:") . "</span><br>";
      foreach ($this->ar_classes as $key => $value) {
        $style = $class_counter == $_REQUEST['Class'] ? '' : "style='display:none'";
        $acc = Display::access_string($key);
        $st_classes .= "<a href='" . $_SERVER['PHP_SELF'] . "?Class=$class_counter' class='menu_option' id='" . JS::default_focus() . "' onclick='return showClass($class_counter);'$acc[1]>$acc[0]</a> <br>";
        $st_reports .= "<table id='TAB_" . $class_counter . "' $style cellpadding=0 cellspacing=0 style='width:100%'><tr><td><span class='bold'>" . _("Reports For Class: ") . "&nbsp;$key</span></td></tr>\n";
        foreach ($value as $report) {
          $acc = Display::access_string($report->name);
          $st_reports .= "<tr><td><a class='printlink' href='" . $_SERVER['PHP_SELF'] . "?Class=$class_counter&rep_id=$report->id' id='" . JS::default_focus() . "'$acc[1]>$acc[0]</a><tr><td>\n";
          if (isset($_REQUEST['rep_id']) && $_REQUEST['rep_id'] == $report->id) {
            $action = PATH_TO_ROOT . '/reporting/prn_redirect.php';
            $st_params = "<table><tr><td>\n<form method='POST' action='$action' target='_blank'>\n";
            $st_params .= submit('Rep' . $report->id, _("Display: ") . Display::access_string($report->name, TRUE), FALSE, '', Config::get('debug.pdf') ?
              FALSE :
              'default process') . hidden('REP_ID', $report->id, FALSE) . '<br><br>';
            $st_params .= $this->getOptions($report->get_controls());
            $st_params .= "\n</form></td></tr></table>\n";
            JS::set_focus('Rep' . $report->id);
            $Ajax->addUpdate(TRUE, 'rep_form', $st_params);
          }
        }
        $st_reports .= "</table>";
        $class_counter++;
      }
      $st_params = "<div id='rep_form'>$st_params</div>";
      $st
        = "<script language='javascript'>
					function showClass(pClass) {
						for(i=0; i<$class_counter; i++) {
							document.getElementById(\"TAB_\" + i).style.display=
							i==pClass ? \"block\" : \"none\";
						}
						document.getElementById('rep_form').innerHTML = '';
//						document.getElementById('rep_form').style.display = 'none';
						return false;
					}
					function checkDate(pObj) {
						var re = /^(3[01]|0[1-9]|[12]\d)\/(0[1-9]|1[012])\/\d{4}/;
						if (re.test(pObj.value)==false) {
							alert('" . _("Invalid date format") . "')
						}
					}
				</script>
				";
      $st .= "<table class='center' style='width:80%' style='border:1px solid #cccccc;'><tr class='top'>";
      $st .= "<td style='width:30%'>$st_classes</td>";
      $st .= "<td style='width:35%' style='border-left:1px solid #cccccc;border-right:1px solid #cccccc;padding-left:3px;'>$st_reports</td>";
      $st .= "<td style='width:35%'>$st_params</td>";
      $st .= "</tr></table><br>";
      return $st;
    }
    /**
     * @param $controls
     *
     * @return string
     * @throws Adv_Exception
     */
    public function getOptions($controls) {
      $st = '';
      if ($controls == NULL) {
        return "";
      }
      $cnt = 0;
      foreach ($controls as $title => $type) {
        $ctrl = '';
        foreach ($this->ctrl_handlers as $fun) { // first check for non-standard controls
          call_user_func($fun, 'PARAM_' . $cnt, $type);
          //$ctrl = $fun('PARAM_' . $cnt, $type);
          if ($ctrl) {
            break;
          }
        }
        if ($ctrl == '') {
          $ctrl = $this->get_ctrl('PARAM_' . $cnt, $type);
        }
        if ($ctrl != '') {
          $st .= $title . ':<br>';
          $st .= $ctrl;
          $st .= "<br><br>";
        }
        else {
          throw new Adv_Exception(_('Unknown report parameter type:') . $type);
        }
        $cnt++;
      }
      return $st;
    }

    //
    //	Register additional control handler
    // $handle - name of global function f($name, $type) returning html code for control
    /**
     * @param $handler
     */
    public function register_controls($handler) {
      $this->ctrl_handlers[] = $handler;
    }

    //
    //	Returns html code for input control $name of type $type
    //
    /**
     * @param $name
     * @param $type
     *
     * @return string
     */
    public function get_ctrl($name, $type) {
      $st = '';
      switch ($type) {
        case 'CURRENCY':
          $sql = "SELECT curr_abrev, concat(curr_abrev,' - ', currency) AS name FROM currencies";
          return select_box($name, '', $sql, 'curr_abrev', 'name', array(
            'spec_option' => _("No Currency Filter"),
            'spec_id' => ALL_TEXT, 'order' => FALSE
          ));
        case 'DATEMONTH':
          return Dates::months($name);
        case 'DATE':
        case 'DATEBEGIN':
        case 'DATEEND':
        case 'DATEBEGINM':
        case 'DATEENDM':
        case 'DATEBEGINTAX':
        case 'DATEENDTAX':
          if ($type == 'DATEBEGIN') {
            $date = Dates::begin_fiscalyear();
          }
          elseif ($type == 'DATEEND') {
            $date = Dates::end_fiscalyear();
          }
          else {
            $date = Dates::today();
          }
          if ($type == 'DATEBEGINM') {
            $date = Dates::begin_month($date);
          }
          elseif ($type == 'DATEENDM') {
            $date = Dates::end_month($date);
          }
          elseif ($type == 'DATEBEGINTAX' || $type == 'DATEENDTAX') {
            $row = DB_Company::get_prefs();
            $edate = Dates::add_months($date, -$row['tax_last']);
            $edate = Dates::end_month($edate);
            if ($type == 'DATEENDTAX') {
              $date = $edate;
            }
            else {
              $bdate = Dates::begin_month($edate);
              $bdate = Dates::add_months($bdate, -$row['tax_prd'] + 1);
              $date = $bdate;
            }
          }
          $st = "<input type='text' class='datepicker' name='$name' value='$date'>";
          return $st;
          break;
        case 'YES_NO':
          return yesno_list($name);
        case 'PAYMENT_LINK':
          $sel = array(_("No payment Link"), "PayPal");
          return array_selector($name, NULL, $sel);
        case 'DESTINATION':
          $sel = array(_("PDF/Printer"), "Excel");
          $def = 0;
          if (Config::get('print_default_excel') == 1) {
            $def = 1;
          }
          return array_selector($name, $def, $sel);
        case 'COMPARE':
          $sel = array(_("Accumulated"), _("Period Y-1"), _("Budget"));
          return array_selector($name, NULL, $sel);
        case 'GRAPHIC':
          $sel = array(_("No Graphics"), _("Vertical bars"), _("Horizontal bars"), _("Dots"), _("Lines"), _("Pie"), _("Donut"));
          return array_selector($name, NULL, $sel);
        case 'SYS_TYPES':
          return $this->gl_systypes_list($name, NULL, _("No Type Filter"));
        case 'SYS_TYPES_ALL':
          return SysTypes::select($name, NULL, _("No Type Filter"));
        case 'TEXT':
          return "<input type='text' name='$name'>";
        case 'TEXTBOX':
          return "<textarea rows=4 cols=30 name='$name'></textarea>";
        case 'ACCOUNTS': // not used
//					$sql = "SELECT id, name FROM ".''."chart_types";
//					return select_box($name, '', $sql, 'id', 'name',array('spec_option'=>_("No Account Group Filter"),'spec_id'=>ALL_NUMERIC));
          return GL_Type::select($name, NULL, _("No Account Group Filter"), TRUE);
        case 'ACCOUNTS_NO_FILTER': // not used
          return GL_Type::select($name);
        case 'GL_ACCOUNTS':
          return GL_UI::all($name);
        case 'BANK_ACCOUNTS':
          return Bank_Account::select($name);
        case 'DIMENSION':
          return Dimensions::select($name, NULL, FALSE, ' ', FALSE, TRUE, 0);
        case 'DIMENSIONS':
          return Dimensions::select($name, NULL, TRUE, _("No Dimension Filter"), FALSE, TRUE, 0);
        case 'DIMENSION1':
          return Dimensions::select($name, NULL, FALSE, ' ', FALSE, TRUE, 1);
        case 'DIMENSIONS1':
          return Dimensions::select($name, NULL, TRUE, _("No Dimension Filter"), FALSE, TRUE, 1);
        case 'DIMENSION2':
          return Dimensions::select($name, NULL, FALSE, ' ', FALSE, TRUE, 2);
        case 'DIMENSIONS2':
          return Dimensions::select($name, NULL, TRUE, _("No Dimension Filter"), FALSE, TRUE, 2);
        case 'CUSTOMERS_NO_FILTER':
        case 'CUSTOMERS':
          $sql = "SELECT debtor_no, name FROM debtors";
          if ($type == 'CUSTOMERS_NO_FILTER') {
            return select_box($name, '', $sql, 'debtor_no', 'name', array(
              'spec_option' => _("No Customer Filter"),
              'spec_id' => ALL_NUMERIC
            ));
          } // FIX allitems numeric!
          //						return Debtor::select($name, null, _("No Customer Filter"));
          else {
            return select_box($name, '', $sql, 'debtor_no', 'name', NULL);
          }
        //						return Debtor::select($name);
        case 'SUPPLIERS_NO_FILTER':
        case 'SUPPLIERS':
          $sql = "SELECT supplier_id, supp_name FROM suppliers";
          if ($type == 'SUPPLIERS_NO_FILTER') {
            return select_box($name, '', $sql, 'supplier_id', 'supp_name', array(
              'spec_option' => _("No Supplier Filter"),
              'spec_id' => ALL_NUMERIC
            ));
          } // FIX allitems numeric!
          //						return Creditor::select($name, null, _("No Supplier Filter"));
          else {
            return select_box($name, '', $sql, 'supplier_id', 'supp_name', NULL);
          }
        //						return Creditor::select($name);
        case 'INVOICE':
          $IV = _("IV");
          $CN = _("CN");
          $ref = (Config::get('print_useinvoicenumber') == 0 ? "trans_no" : "reference");
          $sql
            = "SELECT concat(debtor_trans.trans_no, '-',
						debtor_trans.type) AS TNO, concat(debtor_trans.$ref, if (type=" . ST_SALESINVOICE . ", ' $IV ', ' $CN '), debtors.name) as IName
						FROM debtors, debtor_trans WHERE (type=" . ST_SALESINVOICE . " OR type=" . ST_CUSTCREDIT . ") AND debtors.debtor_no=debtor_trans.debtor_no ORDER BY debtor_trans.trans_no DESC";
          return select_box($name, '', $sql, 'TNO', 'IName', array('order' => FALSE));
        case 'DELIVERY':
          $DN = _("DN");
          $sql
            = "SELECT
					concat(debtor_trans.trans_no, '-', debtor_trans.type) AS TNO, concat(debtor_trans.trans_no, ' $DN ',
					 debtors.name) as IName
						FROM debtors, debtor_trans
						WHERE type=" . ST_CUSTDELIVERY . " AND debtors.debtor_no=debtor_trans.debtor_no ORDER BY debtor_trans.trans_no DESC";
          return select_box($name, '', $sql, 'TNO', 'IName', array('order' => FALSE));
        case 'ORDERS':
          $ref = (Config::get('print_useinvoicenumber') == 0) ? "order_no" : "reference";
          $sql
            = "SELECT sales_orders.order_no, concat(sales_orders.$ref, '-',
						debtors.name) as IName
						FROM debtors, sales_orders WHERE debtors.debtor_no=sales_orders.debtor_no
						AND sales_orders.trans_type=" . ST_SALESORDER . " ORDER BY sales_orders.order_no DESC";
          return select_box($name, '', $sql, 'order_no', 'IName', array('order' => FALSE));
        case 'QUOTATIONS':
          $ref = (Config::get('print_useinvoicenumber') == 0 ? "order_no" : "reference");
          $sql
            = "SELECT sales_orders.order_no, concat(sales_orders.$ref, '-',
						debtors.name) as IName
						FROM debtors, sales_orders WHERE debtors.debtor_no=sales_orders.debtor_no
						AND sales_orders.trans_type=" . ST_SALESQUOTE . " ORDER BY sales_orders.order_no DESC";
          return select_box($name, '', $sql, 'order_no', 'IName', array('order' => FALSE));
        case 'PO':
          $ref = (Config::get('print_useinvoicenumber') == 0 ? "order_no" : "reference");
          $sql
            = "SELECT purch_orders.order_no, concat(purch_orders.$ref, '-',
						suppliers.supp_name) as IName
						FROM suppliers, purch_orders WHERE suppliers.supplier_id=purch_orders.supplier_id ORDER BY purch_orders.order_no DESC";
          return select_box($name, '', $sql, 'order_no', 'IName', array('order' => FALSE));
        case 'REMITTANCE':
          $BP = _("BP");
          $SP = _("SP");
          $CN = _("CN");
          $ref = (Config::get('print_useinvoicenumber') == 0 ? "trans_no" : "reference");
          $sql
            = "SELECT concat(creditor_trans.trans_no, '-',
						creditor_trans.type) AS TNO, concat(creditor_trans.$ref, if (type=" . ST_BANKPAYMENT . ", ' $BP ', if (type=" . ST_SUPPAYMENT . ", ' $SP ', ' $CN ')), suppliers.supp_name) as IName
						FROM suppliers, creditor_trans WHERE (type=" . ST_BANKPAYMENT . " OR type=" . ST_SUPPAYMENT . " OR type=" . ST_SUPPCREDIT . ") AND suppliers.supplier_id=creditor_trans.supplier_id ORDER BY creditor_trans.trans_no DESC";
          return select_box($name, '', $sql, 'TNO', 'IName', array('order' => FALSE));
        case 'RECEIPT':
          $BD = _("BD");
          $CP = _("CP");
          $CN = _("CN");
          $ref = (Config::get('print_useinvoicenumber') == 0 ? "trans_no" : "reference");
          $sql
            = "SELECT concat(debtor_trans.trans_no, '-',
						debtor_trans.type) AS TNO, concat(debtor_trans.$ref, if (type=" . ST_BANKDEPOSIT . ", ' $BD ', if (type=" . ST_CUSTPAYMENT . ", ' $CP ', ' $CN ')), debtors.name) as IName
						FROM debtors, debtor_trans WHERE (type=" . ST_BANKDEPOSIT . " OR type=" . ST_CUSTPAYMENT . " OR type=" . ST_CUSTCREDIT . ") AND debtors.debtor_no=debtor_trans.debtor_no ORDER BY debtor_trans.trans_no DESC";
          return select_box($name, '', $sql, 'TNO', 'IName', array('order' => FALSE));
        case 'REFUND':
          $BD = _("BD");
          $CP = _("CP");
          $CN = _("CN");
          $ref = (Config::get('print_useinvoicenumber') == 0 ? "trans_no" : "reference");
          $sql
            = "SELECT concat(debtor_trans.trans_no, '-',
						debtor_trans.type) AS TNO, concat(debtor_trans.$ref, if (type=" . ST_BANKDEPOSIT . ", ' $BD ', if (type=" . ST_CUSTREFUND . ",
						' $CP ', ' $CN ')), debtors.name) as IName
						FROM debtors, debtor_trans WHERE (type=" . ST_CUSTREFUND . ") AND debtors.debtor_no=debtor_trans.debtor_no ORDER BY debtor_trans.trans_no DESC";
          return select_box($name, '', $sql, 'TNO', 'IName', array('order' => FALSE));
        case 'ITEMS':
          return Item_UI::manufactured($name);
        case 'WORKORDER':
          $sql
            = "SELECT workorders.id, concat(workorders.id, '-',
						stock_master.description) as IName
						FROM stock_master, workorders WHERE stock_master.stock_id=workorders.stock_id ORDER BY workorders.id DESC";
          return select_box($name, '', $sql, 'id', 'IName', array('order' => FALSE));
        case 'LOCATIONS':
          return Inv_Location::select($name, NULL, _("No Location Filter"));
        case 'CATEGORIES':
          return Item_Category::select($name, NULL, _("No Category Filter"));
        case 'SALESTYPES':
          return Sales_Type::select($name);
        case 'AREAS':
          return Sales_UI::areas($name);
        case 'SALESMEN':
          return Sales_UI::persons($name, NULL, _("No Sales Folk Filter"));
        case 'TRANS_YEARS':
          return GL_UI::fiscalyears($name);
        case 'USERS':
          $sql = "SELECT id, user_id FROM users";
          return select_box($name, '', $sql, 'id', 'user_id', array(
            'spec_option' => _("No Users Filter"),
            'spec_id' => ALL_NUMERIC
          ));
        case 'ACCOUNTTAGS':
        case 'DIMENSIONTAGS':
          if ($type == 'ACCOUNTTAGS') {
            $tag_type = TAG_ACCOUNT;
          }
          else {
            $tag_type = TAG_DIMENSION;
          }
          return Tags::select($name, 5, $tag_type, TRUE, _("No tags"));
      }
      return '';
    }
    /**
     * @param      $name
     * @param null $value
     * @param bool $spec_opt
     *
     * @return string
     */
    protected function gl_systypes_list($name, $value = NULL, $spec_opt = FALSE) {
      global $systypes_array;
      $types = $systypes_array;
      foreach (array(
                 ST_LOCTRANSFER, ST_PURCHORDER, ST_SUPPRECEIVE, ST_MANUISSUE, ST_MANURECEIVE, ST_SALESORDER, ST_SALESQUOTE,
                 ST_DIMENSION
               ) as $type) {
        unset($types[$type]);
      }
      return array_selector($name, $value, $types, array(
        'spec_option' => $spec_opt, 'spec_id' => ALL_NUMERIC, 'async' => FALSE,
      ));
    }
  }
