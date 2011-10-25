<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 19/09/11
	 * Time: 2:12 AM
	 * To change this template use File | Settings | File Templates.
	 */
	return array(
		'debug'                            => false,
		'debug.sql'                        => false,
		'debug.pdf'                        => false,
		'debug.query.log'                  => false,
		'debug.select.log'                 => false,
		// Log file for error/warning messages. Should be set to any location
		// writable by www server. When set to empty string logging is switched off.
		// Special value 'syslog' can be used for system logger usage (see php manual).
		//$error_logfile = '';

		'logs.error.file'                  => APP_PATH . '/tmp/errors.log',

/* use popup windows for views */
		'ui.windows.popups'                => true,
		/* use date picker for all date fields */
		'ui.forms.datepicker'              => true,
		/* use Audit Trails in GL */
		'logs.audits'                      => true,
		/* use old style convert (income and expense in BS, PL) */
		'accounts.gl.oldconvertstyle'      => false,
		/* show users online discretely in the footer */
		'ui.users.showonline'              => true,
		/* default print destination. false = PDF/Printer, true = Excel */
		'print.default.excel'              => false,

		// Wiki context help configuration
		// If your help wiki use translated page titles uncomment next line
		// $old_style_help = 1; // this setting is depreciated and subject to removal in next FA versions
		// 	locally installed wiki module
		// Config::get('help.baseurl') = $path_to_root.'/modules/wiki/index.php?n='._('Help').'.';
		// 	context help feed from frontaccounting.net
		// Config::get('help.baseurl')  = 'http://frontaccounting.com/fawiki/index.php?n=Help.';
		// 	not used
		'help.baseurl'                     => null,
		/* per user data/cache directory */

		/* allow alpha characters in accounts. 0 = numeric, 1 = alpha numeric, 2 = uppercase alpha numeric */
		'accounts.allowcharacters'         => null,
		/* Date systems. 0 = traditional, 1 = Jalali used by Iran, nabour countries, Afghanistan and some other Central Asian nations,
				 2 = Islamic used by other arabic nations. 3 = traditional, but where non-workday is Friday and start of week is Saturday */
		'accounts.datesystem'              => 0,
		/* email stock location if order below reorder-level */
		'accounts.stock.emailnotify'       => false,
		/* print_invoice_no. 0 = print reference number, 1 = print invoice number */
		'print.useinvoicenumber'           => 1,
		'ui.date.format'                   => 1,
		'auth.salt'                        => '342342134sdfgsgdf',

		'ui.date.format'                   => 1,
		'ui.date.seperator'                => '/',

		/* Default border and spacing for tables
				 *  Should be moved to CSS */
		'tables.style'                     => "class='tablestyle'",
		'tables.style2'                    => "class='tablestyle2'",

/* Accounts Payable
		 System check to see if quantity charged on purchase invoices exceeds the quantity received.
		 If this parameter is checked the proportion by which the purchase invoice is an overcharge
		 referred to before reporting an error */
		'valid.charged_to_delivered.qty'   => true,
		/* System check to see if price charged on purchase invoices exceeds the purchase order price.
				 If this parameter is checked the proportion by which the purchase invoice is an overcharge
				 referred to before reporting an error */
		'valid.charged_to_delivered.price' => true,
		'accounts.allocation_allowance'    => 0.005,
		'demo_mode'                        => false,
		'item.images.width'                => 80,
		'item.images.height'               => 50,
		'item.images.max_size'             => 500,
		'company.default'                  => 0,
		'company_subdirs'                  => array('images', 'pdf_files', 'backup', 'js_cache', 'reporting', 'attachments'),

		'graphs.skin'                      => 1,
		'db.default'                       => array('name'  => 'fa',
																								'user'  => 'fa',
																								'pass'  => '1willenberg',
																								'host'  => 'localhost',
																								'port'  => 3306,
																								'debug' => false
		),
		'formats.date'                     => array("MMDDYYYY", "DDMMYYYY", "YYYYMMDD"),
		'separators.thousands'             => array(",", ".", " "),
		'separators.decimal'               => array(".", ","),
		'separators.date'                  => array('/', ".", "-", " "),
		'formats.paper_size'               => array("Letter", "A4"),

		'config.onload'                    => array('db', 'js', 'installed_languages'),
		'default_lang'                     => 'en_AU',

	);
/* Whether to display the demo login and password or not */
/* for uploaded item pictures */
/* skin for Business Graphics, 1, 2 or 3 */

// set Config::get('debug.query.log') to 1 only if you want to perform bugtracking sql trail
// Warning: this produces huge amount of data in sql_trail table.
// Don't forget switch the option off and flush the table manually after
// trail, or your future backup files are overloaded with unneeded data.