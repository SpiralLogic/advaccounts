<?php

  /* * ********************************************************************
          Copyright (C) Advanced Group PTY LTD
          Released under the terms of the GNU General Public License, GPL,
          as published by the Free Software Foundation, either version 3
          of the License, or (at your option) any later version.
          This program is distributed in the hope that it will be useful,
          but WITHOUT ANY WARRANTY; without even the implied warranty of
          MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
          See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
          * ********************************************************************* */
  // first check is this is not start page call
  if (Input::get('OutstandingOnly')||Input::post('order_view_mode')=='OutstandingOnly') {
    $security = SA_SALESDELIVERY;
  }
  elseif (Input::get('InvoiceTemplates')||Input::post('order_view_mode')=='InvoiceTemplates') {
    $security = SA_SALESINVOICE;
  }else {
    $security = SA_SALESAREA;
  }
  // then check session value
  JS::openWindow(900, 600);
  if (AJAX_REFERRER && !empty($_POST['ajaxsearch'])) {
    $searchArray = explode(' ', $_POST['ajaxsearch']);
  }
  if (isset($searchArray) && $searchArray[0] == 'o') {
    $trans_type = ST_SALESORDER;
  } elseif (isset($searchArray) && $searchArray[0] == 'q') {
    $trans_type = ST_SALESQUOTE;
  } elseif (isset($searchArray)) {
    $trans_type = ST_SALESORDER;
  } elseif (Input::post('type')) {
    $trans_type = $_POST['type'];
  } elseif (isset($_GET['type']) && ($_GET['type'] == ST_SALESQUOTE)) {
    $trans_type = ST_SALESQUOTE;
  } else {
    $trans_type = ST_SALESORDER;
  }
  if ($trans_type == ST_SALESORDER) {
    if (Input::get('OutstandingOnly')) {
      $_POST['order_view_mode'] = 'OutstandingOnly';
      Session::i()->page_title  = _($help_context = "Search Outstanding Sales Orders");
    } elseif (isset($_GET['InvoiceTemplates']) && ($_GET['InvoiceTemplates'] == true)) {
      $_POST['order_view_mode'] = 'InvoiceTemplates';
      Session::i()->page_title  = _($help_context = "Search Template for Invoicing");
    } elseif (isset($_GET['DeliveryTemplates']) && ($_GET['DeliveryTemplates'] == true)) {
      $_POST['order_view_mode'] = 'DeliveryTemplates';
      Session::i()->page_title  = _($help_context = "Select Template for Delivery");
    } elseif (!isset($_POST['order_view_mode'])) {
      $_POST['order_view_mode'] = false;
      Session::i()->page_title  = _($help_context = "Search All Sales Orders");
    }
  } else {
    $_POST['order_view_mode'] = "Quotations";
    Session::i()->page_title  = _($help_context = "Search All Sales Quotations");
  }
  Page::start(Session::i()->page_title,$security);
  $selected_customer = Input::getPost('customer_id', Input::NUMERIC, -1);
  if (isset($_POST['SelectStockFromList']) && ($_POST['SelectStockFromList'] != "") && ($_POST['SelectStockFromList'] != ALL_TEXT)
  ) {
    $selected_stock_item = $_POST['SelectStockFromList'];
  } else {
    unset($selected_stock_item);
  }
  $id = Forms::findPostPrefix('_chgtpl');
  if ($id != -1) {
    $sql = "UPDATE sales_orders SET type = !type WHERE order_no=$id";
    DB::query($sql, "Can't change sales order type");
    Ajax::activate('orders_tbl');
  }
  if (isset($_POST['Update']) && isset($_POST['last'])) {
    foreach ($_POST['last'] as $id => $value) {
      if ($value != Forms::hasPost('chgtpl' . $id)) {
        $sql = "UPDATE sales_orders SET type = !type WHERE order_no=$id";
        DB::query($sql, "Can't change sales order type");
        Ajax::activate('orders_tbl');
      }
    }
  }
  //	Order range form
  //
  if (Input::post('_OrderNumber_changed')) { // enable/disable selection controls
    $disable = Input::post('OrderNumber') !== '';
    if ($_POST['order_view_mode'] != 'DeliveryTemplates' && $_POST['order_view_mode'] != 'InvoiceTemplates') {
      Ajax::addDisable(true, 'OrdersAfterDate', $disable);
      Ajax::addDisable(true, 'OrdersToDate', $disable);
    }
    Ajax::addDisable(true, 'StockLocation', $disable);
    Ajax::addDisable(true, '_SelectStockFromList_edit', $disable);
    Ajax::addDisable(true, 'SelectStockFromList', $disable);
    if ($disable) {
      Ajax::addFocus(true, 'OrderNumber');
    } else {
      Ajax::addFocus(true, 'OrdersAfterDate');
    }
    Ajax::activate('orders_tbl');
  }
  Forms::start();
  Table::start('tablestyle_noborder');
  Row::start();
  Debtor::cells(_(""), 'customer_id', $selected_customer, true);
  Forms::refCells(_("#:"), 'OrderNumber', '', null, '', true);
  if ($_POST['order_view_mode'] != 'DeliveryTemplates' && $_POST['order_view_mode'] != 'InvoiceTemplates') {
    Forms::dateCells(_("From:"), 'OrdersAfterDate', '', null, -30);
    Forms::dateCells(_("To:"), 'OrdersToDate', '', null, 1);
  }
  Inv_Location::cells(_(""), 'StockLocation', null, true);
  Item::cells(_("Item:"), 'SelectStockFromList', null, true);
  if ($trans_type == ST_SALESQUOTE) {
    Forms::checkCells(_("Show All:"), 'show_all');
  }
  Forms::submitCells('SearchOrders', _("Search"), '', _('Select documents'), 'default');
  Row::end();
  Table::end(1);
  Forms::hidden('order_view_mode', $_POST['order_view_mode']);
  Forms::hidden('type', $trans_type);
  //	Orders inquiry table
  //
  $sql = "SELECT
		sorder.trans_type,
		sorder.order_no,
		sorder.reference," . ($_POST['order_view_mode'] == 'InvoiceTemplates' || $_POST['order_view_mode'] == 'DeliveryTemplates' ? "sorder.comments, " : "sorder.customer_ref, ") . "
		sorder.ord_date,
		sorder.delivery_date,
		debtor.name,
		debtor.debtor_id,
		branch.br_name,
		sorder.deliver_to,
		Sum(line.unit_price*line.quantity*(1-line.discount_percent))+freight_cost AS OrderValue,
		sorder.type,
		debtor.curr_code,
		Sum(line.qty_sent) AS TotDelivered,
		Sum(line.quantity) AS TotQuantity
	FROM sales_orders as sorder, sales_order_details as line, debtors as debtor, branches as branch
		WHERE sorder.order_no = line.order_no
		AND sorder.trans_type = line.trans_type";
  if (isset($searchArray) && $searchArray[0] == 'o') {
    $sql .= " AND sorder.trans_type = 30 ";
  } elseif (isset($searchArray) && $searchArray[0] == 'q') {
    $sql .= " AND sorder.trans_type = 32 ";
  } elseif (isset($searchArray)) {
    $sql .= " AND ( sorder.trans_type = 30 OR sorder.trans_type = 32) ";
  } else {
    $sql .= " AND sorder.trans_type = " . $trans_type;
  }
  $sql .= " AND sorder.debtor_id = debtor.debtor_id
		AND sorder.branch_id = branch.branch_id
		AND debtor.debtor_id = branch.debtor_id";
  if ($selected_customer != -1) {
    $sql .= " AND sorder.debtor_id = " . DB::quote($selected_customer);
  }
  if (isset($_POST['OrderNumber']) && $_POST['OrderNumber'] != "") {
    // search orders with number like
    $number_like = "%" . $_POST['OrderNumber'];
    $sql .= " AND sorder.order_no LIKE " . DB::quote($number_like) . " GROUP BY sorder.order_no";
    $number_like = "%" . $_POST['OrderNumber'] . "%";
    $sql .= " OR sorder.reference LIKE " . DB::quote($number_like) . " GROUP BY sorder.order_no";
  } elseif (AJAX_REFERRER && !empty($_POST['ajaxsearch'])) {
    foreach ($searchArray as $ajaxsearch) {
      if (empty($ajaxsearch)) {
        continue;
      }
      $ajaxsearch = DB::quote("%" . trim($ajaxsearch) . "%");
      $sql .= " AND ( debtor.debtor_id = $ajaxsearch OR debtor.name LIKE $ajaxsearch OR sorder.order_no LIKE $ajaxsearch
			OR sorder.reference LIKE $ajaxsearch OR sorder.contact_name LIKE $ajaxsearch
			OR sorder.customer_ref LIKE $ajaxsearch
			 OR sorder.customer_ref LIKE $ajaxsearch OR branch.br_name LIKE $ajaxsearch)";
    }
    $sql .= " GROUP BY sorder.ord_date,
				 sorder.order_no,
				sorder.debtor_id,
				sorder.branch_id,
				sorder.customer_ref,
				sorder.deliver_to";
  } else { // ... or select inquiry constraints
    if ($_POST['order_view_mode'] != 'DeliveryTemplates' && $_POST['order_view_mode'] != 'InvoiceTemplates' && !isset($_POST['ajaxsearch'])
    ) {
      $date_after  = Dates::dateToSql($_POST['OrdersAfterDate']);
      $date_before = Dates::dateToSql($_POST['OrdersToDate']);
      $sql .= " AND sorder.ord_date >= '$date_after' AND sorder.ord_date <= '$date_before'";
    }
    if ($trans_type == 32 && !Forms::hasPost('show_all')) {
      $sql .= " AND sorder.delivery_date >= '" . Dates::dateToSql(Dates::today()) . "'";
    }
    if ($selected_customer != -1) {
      $sql .= " AND sorder.debtor_id=" . DB::quote($selected_customer);
    }
    if (isset($selected_stock_item)) {
      $sql .= " AND line.stk_code=" . DB::quote($selected_stock_item);
    }
    if (isset($_POST['StockLocation']) && $_POST['StockLocation'] != ALL_TEXT) {
      $sql .= " AND sorder.from_stk_loc = " . DB::quote($_POST['StockLocation']);
    }
    if ($_POST['order_view_mode'] == 'OutstandingOnly') {
      $sql .= " AND line.qty_sent < line.quantity";
    } elseif ($_POST['order_view_mode'] == 'InvoiceTemplates' || $_POST['order_view_mode'] == 'DeliveryTemplates'
    ) {
      $sql .= " AND sorder.type=1";
    }
    $sql .= " GROUP BY sorder.ord_date,
 sorder.order_no,
				sorder.debtor_id,
				sorder.branch_id,
				sorder.customer_ref,
				sorder.deliver_to";
  }
  $ord = null;
  if ($trans_type == ST_SALESORDER) {
    $ord  = "Order #";
    $cols = array(
      array('type' => 'skip'),
      _("Order #")                                                                   => array(
        'fun'    => function ($row, $order_no) {
          return Debtor::trans_view($row['trans_type'], $order_no);
        }, 'ord' => ''
      ),
      _("Ref")                                                                       => array('ord' => ''),
      _("PO#")                                                                       => array('ord' => ''),
      _("Date")                                                                      => array('type' => 'date', 'ord' => 'asc'),
      _("Required")                                                                  => array('type' => 'date', 'ord' => ''),
      _("Customer")                                                                  => array('ord' => 'asc'),
      array('type' => 'skip'),
      _("Branch")                                                                    => array('ord' => ''),
      _("Address"),
      _("Total")                                                                     => array('type' => 'amount', 'ord' => ''),
    );
  } else {
    $ord  = "Quote #";
    $cols = array(
      array('type' => 'skip'),
      _("Quote #")                                                                   => array(
        'fun'    => function ($row, $order_no) {
          return Debtor::trans_view($row['trans_type'], $order_no);
        }, 'ord' => ''
      ),
      _("Ref")                                                                       => array('ord' => ''),
      _("PO#")                                                                       => array('type' => 'skip'),
      _("Date")                                                                      => array('type' => 'date', 'ord' => 'desc'),
      _("Valid until")                                                               => array('type' => 'date', 'ord' => ''),
      _("Customer")                                                                  => array('ord' => 'asc'),
      array('type' => 'skip'),
      _("Branch")                                                                    => array('ord' => ''),
      _("Delivery To"),
      _("Total")                                                                     => array('type' => 'amount', 'ord' => ''),
    );
  }
  if ($trans_type == ST_CUSTDELIVERY) {
  }
  if ($_POST['order_view_mode'] == 'OutstandingOnly') {
    Arr::append($cols, array(
                            array('type' => 'skip'), array(
        'fun' => function ($row) {
          if ($row['trans_type'] == ST_SALESORDER) {
            return DB_Pager::link(_("Dispatch"), "/sales/customer_delivery.php?OrderNumber=" . $row['order_no'], ICON_DOC);
          } else {
            return DB_Pager::link(_("Sales Order"), "/sales/sales_order_entry.php?OrderNumber=" . $row['order_no'], ICON_DOC);
          }
        }
      )
                       ));
  } elseif ($_POST['order_view_mode'] == 'InvoiceTemplates') {
    Arr::substitute($cols, 3, 1, _("Description"));
    Arr::append($cols, array(
                            array(
                              'insert' => true, 'fun' => function ($row) {
                              if ($row['trans_type'] == ST_SALESORDER) {
                                return DB_Pager::link(_("Invoice"), "/sales/sales_order_entry.php?NewInvoice=" . $row["order_no"], ICON_DOC);
                              } else {
                                return '';
                              }
                            }
                            )
                       ));
  } else {
    if ($_POST['order_view_mode'] == 'DeliveryTemplates') {
      Arr::substitute($cols, 3, 1, _("Description"));
      Arr::append($cols, array(
                              array(
                                'insert' => true, 'fun' => function ($row) {
                                return DB_Pager::link(_("Delivery"), "/sales/sales_order_entry.php?NewDelivery=" . $row['order_no'], ICON_DOC);
                              }
                              )
                         ));
    } elseif ($trans_type == ST_SALESQUOTE || $trans_type == ST_SALESORDER) {
      Arr::append($cols, array(
                              array(
                                'insert' => true, 'fun' => function ($row) {
                                global $trans_type;
                                if ($row['trans_type'] == ST_SALESQUOTE) {
                                  return DB_Pager::link(_("Create Order"), "/sales/sales_order_entry?QuoteToOrder=" . $row['order_no'], ICON_DOC);
                                }
                                $name  = "chgtpl" . $row['order_no'];
                                $value = $row['type'] ? 1 : 0;
                                // save also in hidden field for testing during 'Update'
                                return Forms::checkbox(null, $name, $value, true, _('Set this order as a template for direct deliveries/invoices')) . Forms::hidden('last[' . $row
                                ['order_no'] . ']', $value, false);
                              }
                              ), array(
          'insert' => true, 'fun' => function ($row) {
            return DB_Pager::link(_("Edit"), "/sales/sales_order_entry?update=" . $row['order_no'] . "&type=" . $row['trans_type'], ICON_EDIT);
          }
        ), array(
          'insert' => true, 'fun' => function ($row) {
            return Reporting::emailDialogue($row['debtor_id'], $row['trans_type'], $row['order_no']);
          }
        ), array(
          'insert' => true, 'fun' => function ($row) {
            return Reporting::print_doc_link($row['order_no'], _("Proforma"), true, ($row['trans_type'] == ST_SALESORDER ? ST_PROFORMA : ST_PROFORMAQ), ICON_PRINT, 'button printlink');
          }
        ), array(
          'insert' => true, 'fun' => function ($row) {
            return Reporting::print_doc_link($row['order_no'], _("Print"), true, $row['trans_type'], ICON_PRINT, 'button printlink');
          }
        )
                         ));
    }
  }
  $table = & db_pager::new_db_pager('orders_tbl', $sql, $cols, null, null, 0, 4);
  $table->set_marker(function ($row) {
    global $trans_type;
    if ($trans_type == ST_SALESQUOTE) {
      return (Dates::isGreaterThan(Dates::today(), Dates::sqlToDate($row['delivery_date'])));
    } else {
      return ($row['type'] == 0 && Dates::isGreaterThan(Dates::today(), Dates::sqlToDate($row['delivery_date'])) && ($row['TotDelivered'] < $row['TotQuantity']));
    }
  }, _("Marked items are overdue."));
  $table->width = "80%";
  DB_Pager::display($table);
  Forms::submitCenter('Update', _("Update"), true, '', null);
  Forms::end();
  Page::end();



