<?php


    /*     * ********************************************************************
      Copyright (C) FrontAccounting, LLC.
      Released under the terms of the GNU General Public License, GPL,
      as published by the Free Software Foundation, either version 3
      of the License, or (at your option) any later version.
      This program is distributed in the hope that it will be useful,
      but WITHOUT ANY WARRANTY; without even the implied warranty of
      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
      See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
     * ********************************************************************* */
    $page_security = 'SA_CUSTOMER';
    $path_to_root = "..";

    include_once($path_to_root . "/includes/session.inc");
$_SESSION['App']->selected_application = 'cards';
get_jqueryui();


    include_once($path_to_root . "/includes/date_functions.inc");
    include_once($path_to_root . "/includes/banking.inc");
    include_once($path_to_root . "/includes/ui.inc");

page(_($help_context = "Customers"), @$_REQUEST['popup']);
start_form();
if (db_has_customers()) {
    start_table("class = 'tablestyle_noborder'");
    start_row();
    customer_list_cells(_("Select a customer: "), 'customer_id', null, _('New customer'), true, check_value('show_inactive'));
    check_cells(_("Show inactive:"), 'show_inactive', null, true);
    end_row();
    end_table();
    if (get_post('_show_inactive_update')) {
        $Ajax->activate('customer_id');
        set_focus('customer_id');
    }
} else {
    hidden('customer_id');
}

$menu = new ui_menu();
$menu->addTab('Customer', '/sales/manage/customers.php?debtor_no=&popup=1');
$menu->addTab('Branches', '/sales/manage/customer_branches.php?debtor_no=&popup=1');
$menu->render();
$_POST['_focus']='customer_id';
end_form();
$js_lib[] = '$("#tabs").livequery("ready", function() {
		$(this).tabs({
			ajaxOptions: {
				error: function(xhr, status, index, anchor) {
					$(anchor.hash).html("Couldn\'t load this tab . We\'ll try to fix this as soon as possible. If this wouldn\'t be a demo .");
				}
			}
		});
	});';
end_page(true,true);