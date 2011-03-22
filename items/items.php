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
$page_security = 'SA_ITEM';
$path_to_root = "../";
include_once("includes/items.inc");
include_once("../includes/ui2/menuui.inc");
if (isAjaxReferrer()) {
	if (isset($_GET['term'])) {
		$sql = "SELECT stock_id as id, description as label, description as value FROM " . TB_PREF . "stock_master " . "where stock_id LIKE '%" .
			   $_GET['term'] . "%' OR description LIKE '%" .
			   $_GET['term'] . "%' LIMIT 20";
		$result = db_query($sql, 'Couldn\'t Get Items');
		while ($row = db_fetch_assoc($result)) {
			$data[] = $row;
		}
	} elseif (isset ($_POST['id'])) {
		$data = new Item($_POST['id']);
		$data = $data->data;

	}
	echo json_encode($data);
	exit;
}
add_js_ufile("includes/js/items.js");
page(_($help_context = "Items"), @$_REQUEST['popup']);

$menu = new MenuUI();
$menu->startTab('Hi Mike', 'Your are the worst guy');
HTML::div('itemsearch', array('style' => 'text-align:center; '));
UI::search('item', array('label' => 'Item:', 'size' => 80, 'callback' => 'getItem'))->div;
HTML::div('itemdetails', array('style' => 'text-align:center; '))->div;
$menu->endTab();
$menu->startTab('dgdfsg', 'nothing');
HTML::p(true);
echo 'Hi Mike';

HTML::_p();
$menu->endTab();
$menu->render();
end_page(true, true);