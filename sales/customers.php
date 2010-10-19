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


    page(_($help_context = "Customers"), @$_REQUEST['popup']);

    include_once($path_to_root . "/includes/date_functions.inc");
    include_once($path_to_root . "/includes/banking.inc");
    include_once($path_to_root . "/includes/ui.inc");
get_jqueryui();
$menu = new ui_menu();
$menu->addTab('test1', '/sales/manage/customers.php?debtor_no=&popup=1');
$menu->addTab('test2', '/sales/manage/customer_branches.php?debtor_no=&popup=1');
$menu->render();
    end_page();