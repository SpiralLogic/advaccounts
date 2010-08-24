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
//--------------------------------------------------
// User configurable variables
//---------------------------------------------------

    /* Show debug messages returned from an error on the page.
      Debugging info level also determined by settings in PHP.ini
      if $debug=1 show debugging info, dont show if $debug=0 */

    if (!isset($path_to_root) || isset($_GET['path_to_root']) || isset($_POST['path_to_root']))
            die("Restricted access");
// Log file for error/warning messages. Should be set to any location
// writable by www server. When set to empty string logging is switched off.
// Special value 'syslog' can be used for system logger usage (see php manual).
//$error_logfile = '';

    $error_logfile = dirname(__FILE__) . '/tmp/errors.log';
    $debug = 1;
    $show_sql = 0;
    $go_debug = 0;
    $pdf_debug = 0;

// set $sql_trail to 1 only if you want to perform bugtracking sql trail
// Warning: this produces huge amount of data in sql_trail table.
// Don't forget switch the option off and flush the table manually after
// trail, or your future backup files are overloaded with unneeded data.
//

    $sql_trail = 1; // save all sql queries in sql_trail
    $select_trail = 1; // track also SELECT queries
    if ($go_debug == 1) {
        error_reporting(E_ALL);
        ini_set("display_errors", "On");
    } else {
        error_reporting(E_USER_WARNING | E_USER_ERROR | E_USER_NOTICE);
        // ini_alter("error_reporting","E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE");
        ini_set("display_errors", "On");
    }

    if ($error_logfile != '') {
        ini_set("error_log", $error_logfile);
        ini_set("ignore_repeated_errors", "On");
        ini_set("log_errors", "On");
    }
// Main Title
    $app_title = "Advanced Accounting";
// application version
    $version = "2.2.11";

// Build for development purposes
    $build_version = date("d.m.Y", filemtime("$path_to_root/index2.php"));

// Powered by
    $power_by = "Advanced Accounting";
    $power_url = "http://www.advancedroadsigns.com.au";

    /* use popup windows for views */
    $use_popup_windows = 1;

    /* use date picker for all date fields */
    $use_date_picker = 1;

    /* use Audit Trails in GL */
    $use_audit_trail = 1;

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

    /*
      Before upgrade from pre-2.2 FA you have to move here your customized
      security roles definitions. If you have used standard roles, you
      can simply uncomment following two arrays. After upgrade both arrays need
      to be deleted or commented out. You may wish to change user roles to
      new better defined in Users Setup. Old not used roles can be set inactive
      or deleted.

      Standard FA2.1 Security Group definitions

      $security_headings = array(
      _("Inquiries"),
      _("Accountant"),
      _("System Administrator"),
      );

      $security_groups = array(
      array(1,2),
      array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,16),
      array(1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,20),
      );
     */

//MySQL Backup and Restore Settings

    if (isset($_SESSION["wa_current_user"])) {
        define("BACKUP_PATH", $comp_path . '/' . user_company() . "/backup/");
    }
// static js files path
    $js_path = $path_to_root . '/js/';
// standard external js scripts included in all files
    $js_static = array('JsHttpRequest.js', 'behaviour.js', 'utils.js', 'inserts.js');
// additional js source included in header
    $js_lib = $js_userlib = array();

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
?>
