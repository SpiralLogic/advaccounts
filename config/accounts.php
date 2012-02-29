<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Maidenii
	 * Date: 29/02/12
	 * Time: 10:18 PM
	 * To change this template use File | Settings | File Templates.
	 */
	return array(
		'allowcharacters' => null,
		/* Date systems. 0 = traditional, 1 = Jalali used by Iran, nabour countries, Afghanistan and some other Central Asian nations,
										 2 = Islamic used by other arabic nations. 3 = traditional, but where non-workday is Friday and start of week is Saturday */
		'datesystem' => 0, /* email stock location if order below reorder-level */
		'stock_emailnotify' => false, //
		'allocation_allowance' => 0.005, /* use old style convert (income and expense in BS, PL) */
		'gl_oldconvertstyle' => false, /* print_invoice_no. 0 = print reference number, 1 = print invoice number */
		'logs_audits' => true, /* use old style convert (income and expense in BS, PL) */
	);
