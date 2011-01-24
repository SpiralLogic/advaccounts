<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 12/4/10
 * Time: 6:28 PM
 * To change this template use File | Settings | File Templates.
 */
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
$page_security = 'SA_SUPPLIER';
$path_to_root = "../";
include_once("includes/contacts.inc");
if (isAjaxReferrer()) {
	if (isset($_GET['term'])) {
		$data = Supplier::search($_GET['term']);
	} elseif (isset($_POST['id'])) {
		if (isset($_POST['name'])) {
			$data['supplier'] = $supplier = new Supplier($_POST);
			$supplier->save();
			$data['status'] = $supplier->getStatus();
		} elseif (!isset($_POST['name'])) {
			$data['supplier'] = $supplier = new Supplier($_POST['id']);
		}
	} else {
		$data['supplier'] = new Supplier(0);
	}
	echo json_encode($data);
	exit();
}
add_js_ffile("includes/js/suppliers.js");
page(_($help_context = "Suppliers"), @$_REQUEST['popup']);
$menu = new MenuUI();
$menu->startTab('Details', 'Your are the worst guy');
HTML::div('suppliersearch', array('style' => 'text-align:center; '));
UI::search('item', array('label' => 'Supplier:', 'size' => 80, 'callback' => 'getSupplier'))->div;
HTML::div('supplierdetails', array('style' => 'text-align:center; '))->div;
$menu->endTab();
$menu->startTab('dgdfsg', 'nothing');
HTML::p(true);
echo 'Hi Mike';
HTML::_p();
$menu->endTab();
$menu->render();
end_page(true, true);