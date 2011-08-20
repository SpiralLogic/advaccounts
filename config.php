<?php

   /** ********************************************************************
   Copyright (C) FrontAccounting, LLC.
   Released under the terms of the GNU General Public License, GPL,
   as published by the Free Software Foundation, either version 3
   of the License, or (at your option) any later version.
   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
   See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
    * ********************************************************************* */
   //--------------------------------------------------
   // User configurable variables
   //---------------------------------------------------
   /* Show debug messages returned from an error on the page.
    Debugging info level also determined by settings in PHP.ini
    if $debug=1 show debugging info, dont show if $debug=0 */
   if (!isset($path_to_root) || isset($_GET['path_to_root']) || isset($_POST['path_to_root'])) {
      die("Restricted access");
   }


   // Log file for error/warning messages. Should be set to any location
   // writable by www server. When set to empty string logging is switched off.
   // Special value 'syslog' can be used for system logger usage (see php manual).
   //$error_logfile = '';
   $error_logfile = '';
   $debug = 1;
   $show_sql = 0;
   $go_debug = 1;
   $pdf_debug = 0;
   $app_title = "Advanced Accounting";
   $version = "2.2.11";

   // application version
   $version = $version;
   // Build for development purposes
   $build_version = date("d.m.Y", filemtime(__FILE__));
   // Powered by
   $power_by = "Advanced Accounting";
   $power_url = "http://www.advancedroadsigns.com.au";
   /* use popup windows for views */
   $use_popup_windows = 1;
   /* use date picker for all date fields */
   $use_date_picker = 0;
   /* use Audit Trails in GL */
   $use_audit_trail = 0;
   /* use old style convert (income and expense in BS, PL) */
   $use_oldstyle_convert = 0;
   /* show users online discretely in the footer */
   $show_users_online = 1;
   /* default print destination. 0 = PDF/Printer, 1 = Excel */
   $def_print_destination = 0;
   // Wiki context help configuration
   // If your help wiki use translated page titles uncomment next line
   // $old_style_help = 1; // this setting is depreciated and subject to removal in next FA versions
   // 	locally installed wiki module
   // $help_base_url = $path_to_root.'/modules/wiki/index.php?n='._('Help').'.';
   // 	context help feed from frontaccounting.net
   // $help_base_url = 'http://frontaccounting.com/fawiki/index.php?n=Help.';
   // 	not used
   $help_base_url = null;
   /* per user data/cache directory */
   $comp_path = $path_to_root . '/company';
   /* allow alpha characters in accounts. 0 = numeric, 1 = alpha numeric, 2 = uppercase alpha numeric */
   $accounts_alpha = 0;
   /* Date systems. 0 = traditional, 1 = Jalali used by Iran, nabour countries, Afghanistan and some other Central Asian nations,
2 = Islamic used by other arabic nations. 3 = traditional, but where non-workday is Friday and start of week is Saturday */
   $date_system = 0;
   /* email stock location if order below reorder-level */
   $loc_notification = 0;
   /* print_invoice_no. 0 = print reference number, 1 = print invoice number */
   $print_invoice_no = 0;
   $dateformats = array("MMDDYYYY", "DDMMYYYY", "YYYYMMDD");
   $dateseps = array("/", ".", "-", " ");
   $thoseps = array(",", ".", " ");
   $decseps = array(".", ",");
   // defalt dateformats and dateseps indexes used before user login
   $dflt_date_fmt = 0;
   $dflt_date_sep = 0;
   $pagesizes = array("Letter", "A4"); // default PDF pagesize
   /* Default border and spacing for tables
*  Should be moved to CSS */
   $table_style = "class='tablestyle'";
   $table_style2 = "class='tablestyle2'";
   /* Accounts Payable
System check to see if quantity charged on purchase invoices exceeds the quantity received.
If this parameter is checked the proportion by which the purchase invoice is an overcharge
referred to before reporting an error */
   $check_qty_charged_vs_del_qty = true;
   /* System check to see if price charged on purchase invoices exceeds the purchase order price.
If this parameter is checked the proportion by which the purchase invoice is an overcharge
referred to before reporting an error */
   $check_price_charged_vs_order_price = True;
   $config_allocation_settled_allowance = 0.005;
   // Internal configurable variables
   //-----------------------------------------------------------------------------------
   /* Whether to display the demo login and password or not */
   $allow_demo_mode = false;
   /* for uploaded item pictures */
   $pic_width = 80;
   $pic_height = 50;
   $max_image_size = 500;
   /* skin for Business Graphics, 1, 2 or 3 */
   $graph_skin = 1;


   // set $sql_trail to 1 only if you want to perform bugtracking sql trail
   // Warning: this produces huge amount of data in sql_trail table.
   // Don't forget switch the option off and flush the table manually after
   // trail, or your future backup files are overloaded with unneeded data.

   $sql_trail = 0; // save all sql queries in sql_trail
   $select_trail = 0; // track also SELECT queries


   $error_logfile = dirname(__FILE__) . '/tmp/errors.log';
   if ($go_debug && isset($_SESSION["wa_current_user"]) && $_SESSION["wa_current_user"]->user == 1) {
      error_reporting(E_ALL);
      ini_set("display_errors", "On");
   }
   else {
      $go_debug = 0;
      error_reporting(E_USER_WARNING | E_USER_ERROR | E_USER_NOTICE);
      ini_set("display_errors", "On");
   }
   if ($error_logfile != '') {
      ini_set("error_log", $error_logfile);
      ini_set("ignore_repeated_errors", "On");
      ini_set("log_errors", "On");
   }
 
   JS::headerFile(array('/js/JsHttpRequest.js', '/js/behaviour.js', '/js/utils.js', '/js/inserts.js', '/js/js2/jquery-1.6.2.min.js', '/js/js2/jquery-ui-1.8.14.min.js'));
   JS::footerFile(array('/js/js2/advanced.js', '/js/js2/shortmenu.js', '/js/js2/jquery.calculator.pack.js', '/js/js2/calc.js'));


   if (!defined('ICON_EDIT')) {
      define("ICON_EDIT", "edit.gif");
      define("ICON_DELETE", "delete.gif");
      define("ICON_ADD", "ok.gif");
      define("ICON_UPDATE", "ok.gif");
      define("ICON_OK", "ok.gif");
      define("ICON_CANCEL", "cancel.png");
      define("ICON_GL", "gl.png");
      define("ICON_PRINT", "print.png");
      define("ICON_PDF", "pdf.gif");
      define("ICON_DOC", "invoice.gif");
      define("ICON_CREDIT", "credit.gif");
      define("ICON_RECEIVE", "receive.gif");
      define("ICON_DOWN", "download.gif");
      define("ICON_MONEY", "money.png");
      define("ICON_REMOVE", "remove.png");
      define("ICON_REPORT", "report.png");
      define("ICON_VIEW", "view.gif");
      define("ICON_SUBMIT", "ok.gif");
      define("ICON_ESCAPE", "escape.png");
   }
   define("BACKUP_PATH", $comp_path . "/backup/");
   define('DEFAULT_LOCATION', 'MEL');
   define('DEFAULT_TAX_GROUP', 1);
   define('DEFAULT_AREA', 1);
   define('DEFAULT_SHIP_VIA', 1);
?>