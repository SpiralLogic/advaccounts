<?php

  /* * ********************************************************************
		 Copyright (C) FrontAccounting, LLC.
		 Released under the terms of the GNU General Public License, GPL,
		 as published by the Free Software Foundation, either version 3
		 of the License, or (at your option) any later version.
		 This program is distributed in the hope that it will be useful,
		 but WITHOUT ANY WARRANTY; without even the implied warranty of
		 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
		 See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
		* ********************************************************************* */
  $page_security = 'SA_SALESPAYMNT';
  $path_to_root = "..";
  include_once($path_to_root . "/includes/ui/allocation_cart.inc");
  include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/session.inc");
  include_once($path_to_root . "/includes/date_functions.inc");
  include_once($path_to_root . "/includes/faui.inc");
  include_once($path_to_root . "/includes/banking.inc");
  include_once($path_to_root . "/includes/data_checks.inc");
  include_once($path_to_root . "/sales/includes/sales_db.inc");
  include_once($path_to_root . "/sales/includes/cart_class.inc");
  include_once($path_to_root . "/reporting/includes/reporting.inc");

  $js = "";
  if ($use_popup_windows) {
	 $js .= get_js_open_window(900, 500);
  }
  JS::headerFile('/js/payalloc.js');
  page(_($help_context = "Customer Payment Entry"), @$_REQUEST['frame'], false, "", $js);

$_POST=$_GET;
		  global $Refs;
		  processing_start();
		  $doc_type = $type = ST_SALESINVOICE;
		  	 $doc_type = ST_SALESORDER;
			 $doc = new Cart(ST_SALESINVOICE, 0);
			 $doc->trans_type = $type;
			 $doc->trans_no = 0;
			 $doc->document_date = new_doc_date();
				  $doc->pos = user_pos();
				  $pos = get_sales_point($doc->pos);
				  $doc->cash = $pos['cash_sale'];

				  $doc->due_date = $doc->document_date;
			 $doc->reference = $Refs->get_next($doc->trans_type);
		  copy_from_cart();

  var_dump($invoice);
  //----------------------------------------------------------------------------------------------

  check_db_has_customers(_("There are no customers defined in the system."));

  check_db_has_bank_accounts(_("There are no bank accounts defined in the system."));

  //----------------------------------------------------------------------------------------

  
  (isset($_REQUEST['frame'])) ? end_page() : end_page(true, true, true);


?>
