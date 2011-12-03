<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 8/10/11
	 * Time: 2:33 AM
	 * To change this template use File | Settings | File Templates.
	 */
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
	define('DEFAULT_LOCATION', 'MEL');
	define('DEFAULT_TAX_GROUP', 1);
	define('DEFAULT_AREA', 1);
	define('DEFAULT_SHIP_VIA', 1);
	define('APP_TITLE', "Advanced Accounting");
	define('VERSION', "2.2.12");
	define('BUILD_VERSION', -3);
	define('POWERED_BY', "Advanced Accounting");
	define('POWERED_URL', "http://www.advancedgroup.com.au");
	define('STORE_PRODUCT_URL', 'http://www.advancedroadsigns.com.au/-p/');
	define('STORE_URL_EXTENSION', '.htm');
	define('COMPANY_PATH', PATH_TO_ROOT . '/company');
	define('THEME_PATH', PATH_TO_ROOT . '/themes/');
	define("BACKUP_PATH", COMPANY_PATH . "/backup/");
	// ACCESS LEVELS
	define('SS_SADMIN', 1 << 8); // site admin
	define('SS_SETUP', 2 << 8); // company level setup
	define('SS_SPEC', 3 << 8); // special administration
	define('SS_SALES_C', 11 << 8); // configuration
	define('SS_SALES', 12 << 8); // transactions
	define('SS_SALES_A', 13 << 8); // analytic functions/reports/inquires
	define('SS_PURCH_C', 21 << 8);
	define('SS_PURCH', 22 << 8);
	define('SS_PURCH_A', 23 << 8);
	define('SS_ITEMS_C', 31 << 8);
	define('SS_ITEMS', 32 << 8);
	define('SS_ITEMS_A', 33 << 8);
	define('SS_MANUF_C', 41 << 8);
	define('SS_MANUF', 42 << 8);
	define('SS_MANUF_A', 43 << 8);
	define('SS_DIM_C', 51 << 8);
	define('SS_DIM', 52 << 8);
	define('SS_DIM_A', 53 << 8);
	define('SS_GL_C', 61 << 8);
	define('SS_GL', 62 << 8);
	define('SS_GL_A', 63 << 8);
	define('SS_ADV', 71 << 8);

	//	ADVAccounts system transaction types
	//
	define('ST_JOURNAL', 0);
	define('ST_BANKPAYMENT', 1);
	define('ST_BANKDEPOSIT', 2);
	define('ST_BANKTRANSFER', 4);
	define('ST_SALESINVOICE', 10);
	define('ST_CUSTCREDIT', 11);
	define('ST_CUSTPAYMENT', 12);
	define('ST_CUSTDELIVERY', 13);
	define('ST_CUSTREFUND', 14);
	define('ST_DEPOSIT_GROUP', 15);
	define('ST_LOCTRANSFER', 16);
	define('ST_INVADJUST', 17);
	define('ST_PURCHORDER', 18);
	define('ST_SUPPINVOICE', 20);
	define('ST_SUPPCREDIT', 21);
	define('ST_SUPPAYMENT', 22);
	define('ST_SUPPRECEIVE', 25);
	define('ST_WORKORDER', 26);
	define('ST_MANUISSUE', 28);
	define('ST_MANURECEIVE', 29);
	define('ST_PROFORMA', 36);
	define('ST_PROFORMAQ', 37);
	define('ST_SALESORDER', 30);
	define('ST_SALESQUOTE', 32);
	define('ST_COSTUPDATE', 35);
	define('ST_DIMENSION', 40);
	// Don't include these defines in the $systypes_array.
	// They are used for documents only.
	define ('ST_STATEMENT', 91);
	define ('ST_CHEQUE', 92);

	//		Bank transaction types
	//
	define('BT_TRANSFER', 0);
	define('BT_CHEQUE', 1);
	define('BT_CREDIT', 2);
	define('BT_CASH', 3);
	//

	//	Payment types
	//
	define('PT_MISC', 0);
	define('PT_WORKORDER', 1);
	define('PT_CUSTOMER', 2);
	define('PT_SUPPLIER', 3);
	define('PT_QUICKENTRY', 4);
	define('PT_DIMENSION', 5);

	//	Manufacturing types
	//
	define('WO_ASSEMBLY', 0);
	define('WO_UNASSEMBLY', 1);
	define('WO_ADVANCED', 2);
	define('WO_LABOUR', 0);
	define('WO_OVERHEAD', 1);

	//	GL account classes
	//
	define('CL_NONE', 0); // for backward compatibility
	define('CL_ASSETS', 1);
	define('CL_LIABILITIES', 2);
	define('CL_EQUITY', 3);
	define('CL_INCOME', 4);
	define('CL_COGS', 5);
	define('CL_EXPENSE', 6);

	//	Quick entry types
	//
	define('QE_PAYMENT', 1);
	define('QE_DEPOSIT', 2);
	define('QE_JOURNAL', 3);
	define('QE_SUPPINV', 4);

	//	Special option values for various list selectors.
	//
	define('ANY_TEXT', '');
	define('ANY_NUMERIC', -1);
	define('ALL_TEXT', '');
	define('ALL_NUMERIC', -1);

	define('CT_CUSTOMER', 'c');
	define('CT_SUPPLIER', 's');

	// Types of stock items
	define('STOCK_MANUFACTURE', 'M');
	define('STOCK_PURCHASED', 'B');
	define('STOCK_SERVICE', 'D');
	define('STOCK_INFO', 'I');

	define('TAG_ACCOUNT', 1);
	define('TAG_DIMENSION', 2);

	define('TYPE_BANK_ACCOUNTS' ,serialize(array(
			BT_TRANSFER => _("Savings Account"),
			_("Chequing Account"),
			_("Credit Account"),
			_("Cash Account"))
		));
	global  $systypes_array,  $bank_transfer_types, $payment_person_types, $wo_types_array, $wo_cost_types,
				 $class_types, $quick_actions, $quick_entry_types, $stock_types, $tag_types, $security_areas, $security_sections;
	$access_levels = Cache::get('access_levels');
	if (!$access_levels || isset($_GET['reload_config'])) {
		$access_levels = include(DOCROOT . "config/access_levels.php");
		Cache::set('access_levels', $access_levels);
	} else {
		foreach ($access_levels as $k => $v) {
			$$k = $v;
		}
	}
	$types = Cache::get('types');
	if (!$types || isset($_GET['reload_config'])) {
		$types = include(DOCROOT . "config/types.php");
		Cache::set('types', $types);
	} else {
		foreach ($types as $k => $v) {
			$$k = $v;
		}
	}
