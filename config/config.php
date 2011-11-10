<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 19/09/11
	 * Time: 2:12 AM
	 * To change this template use File | Settings | File Templates.
	 */
	return array(
		'debug'														=> true,
		'debug_sql'												=> false,
		'debug_pdf'												=> false,
		'debug_query_log'									=> false,
		'debug_select_log'								 => false,
		// Log file for error/warning messages. Should be set to any location
		// writable by www server. When set to empty string logging is switched off.
		// Special value 'syslog' can be used for system logger usage (see php manual).
		//$error_logfile = '';
		'logs_error_file'									=> APP_PATH . 'tmp/errors.log',
/* use popup windows for views */
		'ui_windows_popups'								=> true,
		/* use date picker for all date fields */
		'ui_forms_datepicker'							=> true,
		/* use Audit Trails in GL */
		'logs_audits'											=> true,
		/* use old style convert (income and expense in BS, PL) */
		'accounts_gl_oldconvertstyle'			=> false,
		/* show users online discretely in the footer */
		'ui_users_showonline'							=> true,
		/* default print destination. false = PDF/Printer, true = Excel */
		'print_default_excel'							=> false,
		// Wiki context help configuration
		// If your help wiki use translated page titles uncomment next line
		// $old_style_help = 1; // this setting is depreciated and subject to removal in next FA versions
		// 	locally installed wiki module
		// Config::get('help_baseurl') = $path_to_root.'/modules/wiki/index.php?n='._('Help').'.';
		// 	context help feed from frontaccounting.net
		// Config::get('help_baseurl')  = 'http://frontaccounting.com/fawiki/index.php?n=Help.';
		// 	not used
		'help_baseurl'										 => null,
		/* per user data/cache directory */
		/* allow alpha characters in accounts. 0 = numeric, 1 = alpha numeric, 2 = uppercase alpha numeric */
		'accounts_allowcharacters'				 => null,
		/* Date systems. 0 = traditional, 1 = Jalali used by Iran, nabour countries, Afghanistan and some other Central Asian nations,
							 2 = Islamic used by other arabic nations. 3 = traditional, but where non-workday is Friday and start of week is Saturday */
		'accounts_datesystem'							=> 0,
		/* email stock location if order below reorder-level */
		'accounts_stock_emailnotify'			 => false,
		'accounts_allocation_allowance'		=> 0.005,
		/* use old style convert (income and expense in BS, PL) */
		'accounts_gl_oldconvertstyle'			=> false,
		/* print_invoice_no. 0 = print reference number, 1 = print invoice number */
		'print_useinvoicenumber'					 => 1,
		'auth_salt'												=> '342342134sdfgsgdf',
		'ui_date_format'									 => 1,
		'ui_date_seperator'								=> '/',
		/* Default border and spacing for tables
							 *  Should be moved to CSS */
		'tables_style'										 => "class='tablestyle'",
		'tables_style2'										=> "class='tablestyle2'",
/* Accounts Payable
		 System check to see if quantity charged on purchase invoices exceeds the quantity received.
		 If this parameter is checked the proportion by which the purchase invoice is an overcharge
		 referred to before reporting an error */
		'valid_charged_to_delivered_qty'	 => true,
		/* System check to see if price charged on purchase invoices exceeds the purchase order price.
							 If this parameter is checked the proportion by which the purchase invoice is an overcharge
							 referred to before reporting an error */
		'valid_charged_to_delivered_price' => true,
		'demo_mode'												=> false,
		'item_images_width'								=> 80,
		'item_images_height'							 => 50,
		'item_images_max_size'						 => 500,
		'company_default'									=> 1,
		'company_subdirs'									=> array('images', 'pdf_files', 'backup', 'js_cache', 'reporting', 'attachments'),
		'graphs_skin'											=> 1,
		'db_default'											 => array(
			'name'	=> 'fa',
			'user'	=> 'fa',
			'pass'	=> '1willenberg',
			'host'	=> 'localhost',
			'port'	=> 3306,
			'debug' => false
		),
		'formats_date'										 => array("MMDDYYYY", "DDMMYYYY", "YYYYMMDD"),
		'separators_thousands'						 => array(",", ".", " "),
		'separators_decimal'							 => array(".", ","),
		'separators_date'									=> array('/', ".", "-", " "),
		'formats_paper_size'							 => array("Letter", "A4"),
		'default_lang'										 => 'en_AU',
	);
/* Whether to display the demo login and password or not */
/* for uploaded item pictures */
/* skin for Business Graphics, 1, 2 or 3 */
// set Config::get('debug.query.log') to 1 only if you want to perform bugtracking sql trail
// Warning: this produces huge amount of data in sql_trail table.
// Don't forget switch the option off and flush the table manually after
// trail, or your future backup files are overloaded with unneeded data.