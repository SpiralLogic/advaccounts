<?php

	class Sidemenu {
		protected $start = <<<HTML
		<div id="sidemenu" class="ui-widget-shadow ui-corner-all">
		 <h3><a href="#">Search</a></h3>
HTML;


		protected $end = <<<HTML
	</div>
HTML;

		protected $customer = <<<HTML
	 <h3><a href="#">Customer Search</a></h3>
		<div>
		<input size="14" id="quickCustomer"/>
		</div>
HTML;
		protected $search = <<<HTML
<div id="search">
		<ul>
		 <li id="orders" data-href="/sales/inquiry/sales_orders_view.php?type=30">Orders/Quotes</li>
		 <li id="invoices" data-href="/sales/inquiry/customer_inquiry.php?">Invoice/Delivery</li>
		 <li id="purchaseOrders" data-href="/purchases/inquiry/po_search_completed.php?">Purchase Order</li>
		 <li id="supplierInvoices" data-href="/purchases/inquiry/supplier_inquiry.php?">Supplier Invoices</li></ul></div>
HTML;
		protected $bank = <<<HTML
				 <h3><a href="#">Banking</a></h3>
		 <div>
		 <a href="/gl/gl_bank.php?NewPayment=Yes">Payment</a>
		 <a href="/gl/gl_bank.php?NewDeposit=Yes">Deposit</a>
		 <a href="/gl/bank_account_reconcile.php?">Reconcile</a>
		 <a href="/gl/undeposited_funds.php?">Undeposited</a>
		 </div>

HTML;
		protected $sales = <<<HTML

<h3><a href="#">Create</a></h3>

		 <div>
		 <a href="/sales/sales_order_entry.php?NewQuotation=Yes">Quote</a>
		 <a href="/sales/sales_order_entry.php?NewOrder=Yes">Order</a>
		 <a href="/sales/sales_order_entry.php?NewInvoice=0">Direct Invoice</a>
		 <a href="/purchases/po_entry_items.php?NewOrder=Yes">Purchase Order</a>
		 </div>

HTML;

		static function render() {
			$sidemenu = new static;
			echo $sidemenu->display;
		}

		protected function __construct() {
			$this->display = $this->start . $this->search . $this->sales;

			if (User::get()->can_access(SS_GL)) $this->display .= $this->bank;
			$this->display .= $this->customer . $this->end;
		}
	}
