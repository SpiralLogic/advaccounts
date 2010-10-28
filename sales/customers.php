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
get_jquery_live();

    include_once($path_to_root . "/includes/date_functions.inc");
    include_once($path_to_root . "/includes/banking.inc");
    include_once($path_to_root . "/includes/ui.inc");

page(_($help_context = "Customers"), @$_REQUEST['popup']);
start_form();
if (db_has_customers() ) {
    start_table("class = 'tablestyle_noborder'");
    start_row();
    customer_list_cells(_("Select a customer: "), 'customer_id', null, _('New customer'), true, check_value('show_inactive'),false);
    check_cells(_("Show inactive:"), 'show_inactive', null, true);
    end_row();
    end_table();
    if (get_post('_show_inactive_update')) {
        $Ajax->activate('_page_body');
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
/*$js_lib[] = '$(".combo2").live("change",  function() {alert(
$("#_customer_id_sel select option:selected").text());
});';*/


end_page(true,true);