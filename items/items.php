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
add_js_ufile("includes/js/items.js");
page(_($help_context = "Items"), @$_REQUEST['popup']);
UI::div('itemsearch', array('style' => 'text-align:center; '));
UI::search('item', array('label' => 'Item:', 'size' => 80))->div;
UI::div('itemdetails', array('style' => 'text-align:center; '))->div;
end_page(true, true);