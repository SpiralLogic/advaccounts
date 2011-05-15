<?php

	class Sidemenu {
		static function render() {
			global $path_to_root;
			echo <<<HTML
<div id="sidemenu" class="ui-widget-shadow ui-corner-all">
    <h3><a href="#">Search</a></h3>

    <div id="search">

        <a id="orders" href="/sales/inquiry/sales_orders_view.php?type=30">Orders/Quotes</a>
        <a id="invoices" href="/sales/inquiry/customer_inquiry.php?">Invoice/Delivery</a>
        <a id="purchaseOrders" href="/purchasing/inquiry/po_search_completed.php?">Purchase Order</a>
        <a id="supplierInvoices" href="/purchasing/inquiry/supplier_inquiry.php?">Supplier Invoices</a></div>

    <h3><a href="#">Create</a></h3>

    <div>
        <a href="/sales/sales_order_entry.php?NewQuotation=Yes">Quote</a>
        <a href="/sales/sales_order_entry.php?NewOrder=Yes">Order</a>
        <a href="/sales/sales_order_entry.php?NewInvoice=0">Direct Invoice</a>
        <a href="/purchasing/po_entry_items.php?NewOrder=Yes">Purchase Order</a>
    </div>
        <h3><a href="#">Customer Search</a></h3>
<div>
<input size="14" id="quickCustomer"/>
</div>
</div>
HTML;

		}
	}
