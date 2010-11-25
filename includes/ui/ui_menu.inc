<?php
/**********************************************************************
Copyright (C) FrontAccounting, LLC.
Released under the terms of the GNU General Public License, GPL,
as published by the Free Software Foundation, either version 3
of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
 ***********************************************************************/
include_once($path_to_root . "/admin/db/voiding_db.inc");
include_once($path_to_root . "/includes/types.inc");

//--------------------------------------------------------------------------------------

class MenuUi {


	protected $tabs = array();
	protected $menu = '';
	protected $content = '';

	function __construct() {
		ob_start();
		$this->menu = "<div id='tabs'>";
	}

	function addTab($title, $tooltip = '', $link = '#') {
		$this->tabs[] = new MenuUi_item($title, $tooltip, $link);
	}

	function startTab($title, $tooltip) {
		$this->addTab($title, $tooltip, '#');
		echo '<div id="tabs-' . count($this->tabs) . '">';
	}

	function endTab() {
		echo '</div>';
	}

	function render() {
		global $js_lib;
		$this->menu .= "<ul>";
		foreach ($this->tabs as $key => $tab) {
			$num = $key + 1;
			$this->menu .= "<li><a title='{$tab->title}' href='#tabs-{$num}'><span>{$tab->title}</span></a></li>";
		}
		$this->menu .= "</ul>";
		$this->content = ob_get_clean();
		echo $this->menu . $this->content . '</div>';
		$js_lib[] = '$("#tabs").tabs();';
	}

}



class MenuUi_item {


	public $content = '';
	public $link = '';
	public $title = '';
	public $tooltip = '';

	function __construct($title, $link, $tooltip = '') {
		$this->title = $title;
		$this->link = $link;
		$this->$tooltip = $tooltip;

	}

}



class Sidemenu {


	static function render() {
		global $path_to_root, $js_lib;
		get_jqueryui();

			echo '<div id="sidemenu" class="ui-widget-shadow ui-corner-all">
<h3> <a href="#">Search</a></h3><div id="search">
<a id="quotes" href="' . $path_to_root . '/sales/inquiry/sales_orders_view.php?type=32">Quote</a>
<a id="orders" href="' . $path_to_root . '/sales/inquiry/sales_orders_view.php?type=30">Order</a>
<a id="invoices" href="' . $path_to_root . '/sales/inquiry/customer_inquiry.php?">Invoice/Delivery</a>
<a id="purchaseOrders" href="' . $path_to_root . '/purchasing/inquiry/po_search_completed.php?">Purchase Order</a></div>

<h3> <a href="#">Create</a></h3><div>
<a href="' . $path_to_root . '/sales/sales_order_entry.php?NewQuotation=Yes">Quote</a>
<a href="' . $path_to_root . '/sales/sales_order_entry.php?NewOrder=Yes">Order</a>
<a href="' . $path_to_root . '/sales/sales_order_entry.php?NewInvoice=0">Direct Invoice</a>
<a href="' . $path_to_root . '/purchasing/po_entry_items.php?NewOrder=Yes">Purchase Order</a>
</div>
</div>


';

	}
}
