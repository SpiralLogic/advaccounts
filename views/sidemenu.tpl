<div id="sidemenu" class="ui-widget-shadow ui-corner-all">
  <h3><a href="#">Search</a></h3>

  <div id="search">
    <ul>
      <li id="orders" data-href="/sales/inquiry/sales_orders_view?type={{ST_SALESORDER}}">Orders/Quotes</li>
      <li id="invoices" data-href="/sales/inquiry/customer_inquiry?">Invoice/Delivery</li>
      <li id="purchaseOrders" data-href="/purchases/inquiry/po_search_completed?">Purchase Order</li>
      <li id="supplierInvoices" data-href="/purchases/inquiry/supplier_inquiry?">Supplier Invoices</li>
    </ul>
  </div>
  <h3><a href="#">Create</a></h3>

  <div>
    <ul>
      <li><a href="/sales/sales_order_entry?add=0&amp;type={{ST_SALESQUOTE}}">Quote</a></li>
      <li><a href="/sales/sales_order_entry?add=0&amp;type={{ST_SALESORDER}}">Order</a></li>
      <li><a href="/sales/sales_order_entry?add=0&amp;type={{ST_SALESINVOICE}}">Direct Invoice</a></li>
      <li><a href="/purchases/po_entry_items?New=0">Purchase Order</a></li>
    </ul>
  </div>
  {{#bank?}}
  <h3><a href="#">Banking</a></h3>

  <div>
    <ul>
      <li><a href="/gl/gl_bank?NewPayment=Yes">Payment</a></li>
      <li><a href="/gl/gl_bank?NewDeposit=Yes">Deposit</a></li>
      <li><a href="/gl/bank_account_reconcile?">Reconcile</a></li>
      <li><a href="/gl/undeposited_funds?">Undeposited</a></li>
    </ul>
  </div>
  {{/bank?}}
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
