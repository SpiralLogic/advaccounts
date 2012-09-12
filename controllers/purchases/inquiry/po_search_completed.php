<?php
  use ADV\Core\Input\Input;
  use ADV\App\Reporting;
  use ADV\Core\DB\DB;
  use ADV\App\UI\UI;
  use ADV\App\Item\Item;
  use ADV\App\Creditor\Creditor;
  use ADV\Core\Row;
  use ADV\Core\Table;
  use ADV\Core\Ajax;
  use ADV\Core\JS;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class POCompletedInquiry extends \ADV\App\Controller\Base
  {
    protected $order_number;
    protected $creditor_id;
    protected function before() {
      JS::_openWindow(950, 500);
      $this->order_number = $this->Input->getPost('order_number', Input::STRING);
      $this->creditor_id  = $this->Input->postGet('creditor_id', Input::NUMERIC, -1);
      if ($this->Input->post('SearchOrders')) {
        $this->Ajax->activate('orders_tbl');
      }
      if ($this->order_number) {
        $this->Ajax->addFocus(true, 'order_number');
      } else {
        $this->Ajax->addFocus(true, 'creditor');
      }
      $this->Ajax->activate('orders_tbl');
      if ($this->Input->post('_control') != 'supplier' && !$this->Input->post('supplier')) {
        $_POST['creditor_id'] = $this->Session->setGlobal('creditor', '');
      }
    }
    protected function index() {
      Page::start(_($help_context = "Search Purchase Orders"), SA_SUPPTRANSVIEW, $this->Input->request('frame'));
      Forms::start();
      if (!$this->Input->request('frame')) {
        Table::start('tablestyle_noborder');
        Row::start();
        Creditor::newselect(null, ['row'=> false, 'cell_class'=> 'med']);
        Forms::refCells(_("#:"), 'order_number');
        Forms::dateCells(_("From:"), 'OrdersAfterDate', '', null, -30);
        Forms::dateCells(_("To:"), 'OrdersToDate');
        //Inv_Location::cells(_("Location:"), 'StockLocation', null, true);
        Item::cells(_("Item:"), 'SelectStockFromList', null, true);
        Forms::submitCells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
        Row::end();
        Table::end();
      }
      $this->makeTable();
      Creditor::addInfoDialog('.pagerclick');
      UI::emailDialogue(CT_SUPPLIER);
      Forms::end();
      Page::end();
    }
    protected function makeTable() {
      $searchArray = [];
      $location    = $stock_location = '';
      if (AJAX_REFERRER && !empty($_POST['q'])) {
        $searchArray = explode(' ', $_POST['q']);
        unset($_POST['creditor_id']);
      }
      $sql
        = "SELECT
  	porder.order_no,
  	porder.reference,
  	supplier.name,
  	supplier.creditor_id as id,
  	location.location_name,
  	porder.requisition_no,
  	porder.ord_date,
  	supplier.curr_code,
  	Sum(line.unit_price*line.quantity_ordered)+porder.freight AS OrderValue,
  	Sum(line.quantity_ordered - line.quantity_received) AS Received,
  	Sum(line.quantity_received - line.qty_invoiced) AS Invoiced,
  	porder.into_stock_location, supplier.creditor_id
  	FROM purch_orders as porder, purch_order_details as line, suppliers as supplier, locations as location
  	WHERE porder.order_no = line.order_no
  	AND porder.creditor_id = supplier.creditor_id
  	AND location.loc_code = porder.into_stock_location ";
      if (AJAX_REFERRER && $searchArray && !empty($_POST['q'])) {
        foreach ($searchArray as $quicksearch) {
          if (empty($quicksearch)) {
            continue;
          }
          $quicksearch = $this->DB->_quote("%" . $quicksearch . "%");
          $sql
            .= " AND (supplier.name LIKE $quicksearch OR porder.order_no LIKE $quicksearch
  		 OR porder.reference LIKE $quicksearch
  		 OR porder.requisition_no LIKE $quicksearch
  		 OR location.location_name LIKE $quicksearch)";
        }
      } else {
        if ($this->order_number) {
          $sql .= " AND (porder.order_no LIKE " . $this->DB->_quote('%' . $this->order_number . '%');
          $sql .= " OR porder.reference LIKE " . $this->DB->_quote('%' . $this->order_number . '%') . ') ';
        }
        if ($this->creditor_id > -1) {
          $sql .= " AND porder.creditor_id = " . $this->DB->_quote($this->creditor_id);
        }
        $stock_location = $this->Input->post('StockLocation', Input::STRING);
        $location       = $this->Input->get(LOC_NOT_FAXED_YET);
        if ($stock_location || $location) {
          $sql .= " AND porder.into_stock_location = ";
          $sql .= ($location == 1) ? "'" . LOC_NOT_FAXED_YET . "'" : $this->DB->_quote($stock_location);
        } else {
          $data_after  = Dates::_dateToSql($_POST['OrdersAfterDate']);
          $date_before = Dates::_dateToSql($_POST['OrdersToDate']);
          $sql .= " AND porder.ord_date >= '$data_after'";
          $sql .= " AND porder.ord_date <= '$date_before'";
        }
        $selected_stock_item = $this->Input->post('SelectStockFromList');
        if ($selected_stock_item) {
          $sql .= " AND line.item_code=" . $this->DB->_quote($selected_stock_item);
        }
      } //end not order number selected
      $sql .= " GROUP BY porder.order_no";
      $cols = array(
        // Transaction link
        _("#")           => array('ord' => '', 'fun' => [$this, 'formatView']), //
        _("Reference"), //
        _("Supplier")    => array('ord' => '', 'type' => 'id'), //
        _("Supplier ID") => 'skip', //
        _("Location")    => '', //
        _("Invoice #")   => '', //
        _("Order Date")  => array('name' => 'ord_date', 'type' => 'date', 'ord' => 'desc'), //
        _("Currency")    => array('align' => 'center'), //
        _("Order Total") => 'amount', //
        'ord'            => 'skip',
        'rec'            => 'skip',
        'inv'            => 'skip',
        'stock_lock'     => 'skip',
        'creditor_id'    => 'skip',
        // Edit link

        ['insert' => true, 'fun' => [$this, 'formatDropDown']]
      ); //
      if ($stock_location) {
        $cols[_("Location")] = 'skip';
      }
      if ($location == 1) {
        $cols[_("Invoice #")] = 'skip';
      }
      $table        = DB_Pager::new_db_pager('orders_tbl', $sql, $cols);
      $table->width = ($this->Input->request('frame')) ? '100' : "90";
      $table->display($table);
    }
    /**
     * @param $row
     *
     * @return null|string
     */
    public function formatView($row) {
      return GL_UI::viewTrans(ST_PURCHORDER, $row["order_no"]);
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatEditBtn($row) {
      return "/purchases/po_entry_items.php?" . Orders::MODIFY_ORDER . "=" . $row["order_no"];
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatProcessBtn($row) {
      if ($row['Received'] > 0) {
        return DB_Pager::link(_("Receive"), "/purchases/po_receive_items.php?PONumber=" . $row["order_no"], ICON_RECEIVE);
      } elseif ($row['Invoiced'] > 0) {
        return DB_Pager::link(_("Invoice"), "/purchases/supplier_invoice.php?New=1&creditor_id=" . $row['creditor_id'] . "&PONumber=" . $row["order_no"], ICON_RECEIVE);
      }

      return '';
    }
    /**
     * @param $row
     *
     * @return string
     */
    public function formatPrintBtn($row) {
      return Reporting::print_doc_link($row['order_no'], _("Print"), true, 18, ICON_PRINT, 'button printlink');
    }
    /**
     * @param $row
     *
     * @return ADV\Core\HTML|string
     */
    public function formatEmailBtn($row) {
      return Reporting::emailDialogue($row['id'], ST_PURCHORDER, $row['order_no']);
    }
    public function formatDropDown($row) {
      $dropdown = new View('ui/dropdown');
      $edit_url = $this->formatEditBtn($row);
      $items[]  = ['label'=> 'Edit', 'href'=> $edit_url];
      $title    = 'Edit';
      $href     = Reporting::print_doc_link($row['order_no'], _("Print"), true, ST_PURCHORDER, ICON_PRINT, 'button printlink', '', 0, 0, true);
      $items[]  = ['class'=> 'printlink', 'label'=> 'Print', 'href'=> $href];
      $items[]  = ['class'=> 'email-button', 'label'=> 'Email', 'href'=> '#', 'data'=> ['emailid' => $row['creditor_id'] . '-' . ST_PURCHORDER . '-' . $row['order_no']]];
      if ($row['Received'] > 0) {
        $href = "/purchases/po_receive_items.php?PONumber=" . $row["order_no"];
      } elseif ($row['Invoiced'] > 0) {
        $href = "/purchases/supplier_invoice.php?New=1&creditor_id=" . $row['creditor_id'] . "&PONumber=" . $row["order_no"];
      }
      $items[] = ['label'=> 'Receive', 'href'=> $href];
      $menus[] = ['title'=> $title, 'items'=> $items, 'auto'=> 'auto', 'split'=> true];
      $dropdown->set('menus', $menus);

      return $dropdown->render(true);
    }
  }

  new POCompletedInquiry();
