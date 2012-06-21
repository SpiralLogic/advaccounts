<div id="sidemenu" class="ui-widget-shadow ui-corner-all">
  <h3><a href="#">Search</a></h3>
  <div id="search">
    <ul>
      <li id="orders" data-href="/sales/inquiry/sales_orders_view.php?type=30">Orders/Quotes</li>
      <li id="invoices" data-href="/sales/inquiry/customer_inquiry.php?">Invoice/Delivery</li>
      <li id="purchaseOrders" data-href="/purchases/inquiry/po_search_completed.php?">Purchase Order</li>
      <li id="supplierInvoices" data-href="/purchases/inquiry/supplier_inquiry.php?">Supplier Invoices</li>
    </ul>
  </div>
  <h3><a href="#">Create</a></h3>
  <div>
    <ul>
      <li><a href="/sales/sales_order_entry.php?type=32">Quote</a></li>
      <li><a href="/sales/sales_order_entry.php?New=30">Order</a></li>
      <li><a href="/sales/sales_order_entry.php?New=0">Direct Invoice</a></li>
      <li><a href="/purchases/po_entry_items.php?New=0">Purchase Order</a></li>
    </ul>
  </div>
<? if ($bank): ?>
  <h3><a href="#">Banking</a></h3>
  <div>
    <ul>
      <li><a href="/gl/gl_bank.php?NewPayment=Yes">Payment</a></li>
      <li><a href="/gl/gl_bank.php?NewDeposit=Yes">Deposit</a></li>
      <li><a href="/gl/bank_account_reconcile.php?">Reconcile</a></li>
      <li><a href="/gl/undeposited_funds.php?">Undeposited</a></li>
    </ul>
  </div>
    <? endif; ?>
  <h3><a href="#">Customer Search</a></h3>
  <div>
    <input size="14" id="quickCustomer"/>
  </div>
  <h3><a href="#">Supplier Search</a></h3>
  <div>
    <input size="14" id="quickSupplier"/>
  </div>
  <!-- end sidemenu div-->
</div>
