<?php

	$page_security = 'SA_CUSTOMER';
	include_once("includes/contacts.inc");


	if (isset($_POST['name'])) {
		$data['customer'] = $customer = new Customer($_POST);
		$data['customer']->save();
	} elseif (Input::post_get('id',Input::NUMERIC) > 0) {
		$data['customer'] = $customer = new Customer(Input::post_get('id'));
		$data['contact_log'] = ContactLog::read($customer->id, ContactLog::CUSTOMER);
		$data['transactions'] = '<pre>' . print_r($customer->getTransactions(), true) . '</pre>';
		$_SESSION['wa_global_customer_id'] = $customer->id;
	} else {
		$data['customer'] = $customer = new Customer();
	}
	$data['status'] = $customer->getStatus();

	if (AJAX_REFERRER) {
		if (isset($_GET['term'])) {
			$data = Customer::search($_GET['term']);
		}
		echo json_encode($data, JSON_NUMERIC_CHECK);
		exit();
	}
	JS::footerFile("/js/js2/jquery-tmpl.min.js");
	JS::footerFile("includes/js/customers.js");

	page(_($help_context = "Customers"), Input::request('popup') );
	check_db_has_sales_types(_("There are no sales types defined. Please define at least one sales type before adding a customer."));
	check_db_has_sales_people(_("There are no sales people defined in the system. At least one sales person is required before proceeding."));
	check_db_has_sales_areas(_("There are no sales areas defined in the system. At least one sales area is required before proceeding."));
	check_db_has_shippers(_("There are no shipping companies defined in the system. At least one shipping company is required before proceeding."));
	check_db_has_tax_groups(_("There are no tax groups defined in the system. At least one tax group is required before proceeding."));

	JS::onload("Customer.setValues(" . json_encode($data) . ");");
	$currentContact = $customer->contacts[$customer->defaultContact];
	$currentBranch = $customer->branches[$customer->defaultBranch];
	if (isset($_POST['delete'])) {
		$customer->delete();
		$status = $customer->getStatus();
		display_notification($status['message']);
	}
	if ( !Input::get('popup') && !Input::get('id')) {
		/** @noinspection PhpUndefinedMethodInspection */
		HTML::div('custsearch');
		HTML::table(array('class' => 'marginauto bold'));
		HTML::tr(true)->td(array("style" => "width:750px"));
		UI::search('customer', array('label' => 'Search Customer:',
																'size' => 80,
																'callback' => 'Customer.fetch'
													 ), array('focus' => true));
		HTML::td()->tr->table->div;
	}
	start_form();
	$menu = new MenuUi();
	$menu->startTab('Details', 'Customer Details', '#', 'text-align:center');
	HTML::div('customerIDs');
	HTML::table(array("class" => "marginauto bold"))->tr(true)->td(true);
	HTML::label(array('for' => 'name',
									 'content' => 'Customer name:'
							), false);
	HTML::input('name', array('value' => $customer->name,
													 'name' => 'name',
													 'size' => 50
											));
	HTML::td()->td(array('content' => _("Customer ID: "),
											"style" => "width:90px"
								 ), false)->td(true);
	HTML::input('id', array('value' => $customer->id,
												 'name' => 'id',
												 'size' => 10,
												 'maxlength' => '7'
										));
	HTML::td()->tr->table->div;
	start_outer_table(Config::get('tables.style2'), 5);
	table_section(1);
	table_section_title(_("Shipping Details"), 2);
	/** @noinspection PhpUndefinedMethodInspection */
	HTML::tr(true)->td('branchSelect', array('colspan' => 2,
																					'class' => "center"
																		 ));
	UI::select('branchList', array_map(function($v) {
														 return $v->br_name;
													 }, $customer->branches), array('name' => 'branchList'));
	UI::button('addBranch', 'Add new address', array('class' => 'invis',
																									'name' => 'addBranch'
																						 ));
	HTML::td()->tr;
	text_row(_("Contact:"), 'br_contact_name', $currentBranch->contact_name, 35, 40);
	//hidden('br_contact_name', $customer->contact_name);
	text_row(_("Phone Number:"), 'br_phone', $currentBranch->phone, 32, 30);
	text_row(_("2nd Phone Number:"), 'br_phone2', $currentBranch->phone2, 32, 30);
	text_row(_("Fax Number:"), 'br_fax', $currentBranch->fax, 32, 30);
	email_row(_("Email:"), 'br_email', $currentBranch->email, 35, 55);
	textarea_row(_("Street:"), 'br_br_address', $currentBranch->br_address, 35, 2);
	postcode::render(array('br_postcode', $currentBranch->postcode), array('br_city', $currentBranch->city), array('br_state', $currentBranch->state));
	table_section(2);
	table_section_title(_("Accounts Details"), 2);
	/** @noinspection PhpUndefinedMethodInspection */
	HTML::tr(true)->td(array('class' => "center",
													'colspan' => 2
										 ));
	UI::button('useShipAddress', _("Use shipping details"), array('name' => 'useShipAddress'));
	text_row(_("Accounts Contact:"), 'acc_contact_name', $customer->accounts->contact_name, 40, 40);
	HTML::td()->tr;
	text_row(_("Phone Number:"), 'acc_phone', $customer->accounts->phone, 40, 30);
	text_row(_("Secondary Phone Number:"), 'acc_phone2', $customer->accounts->phone2, 40, 30);
	text_row(_("Fax Number:"), 'acc_fax', $customer->accounts->fax, 40, 30);
	email_row(_("E-mail:"), 'acc_email', $customer->accounts->email, 35, 40);
	textarea_row(_("Street:"), 'acc_br_address', $customer->accounts->br_address, 35, 2);
	postcode::render(array('acc_postcode', $customer->accounts->postcode), array('acc_city', $customer->accounts->city), array('acc_state', $customer->accounts->state));
	sales_types_list_row(_("Sales Type/Price List:"), 'sales_type', $customer->sales_type);
	record_status_list_row(_("Customer status:"), 'inactive');
	end_outer_table(1);
	$menu->endTab()->startTab('Accounts', 'Accounts');
	start_outer_table(Config::get('tables.style2'), 5);
	table_section(1);
	hidden('accounts_id', $customer->accounts->accounts_id);
	table_section_title(_("Accounts Details:"), 2);
	text_row(_("Accounts Contact:"), 'acc_contact_name', $customer->accounts->contact_name, 40, 40);
	email_row(_("E-mail:"), 'acc_email', $customer->email, 40, 40);
	text_row(_("Phone Number:"), 'acc_phone', $customer->accounts->phone, 40, 30);
	text_row(_("2nd Phone Number:"), 'acc_phone2', $customer->accounts->phone2, 40, 30);
	text_row(_("Fax Number:"), 'acc_fax', $customer->accounts->fax, 40, 30);
	textarea_row(_("Street:"), 'acc_br_address', $customer->accounts->br_address, 35, 2);
	postcode::render(array('acc_postcode', $customer->accounts->postcode), array('acc_city', $customer->accounts->city), array('acc_state', $customer->accounts->state));
	textarea_row(_("Postal Address:"), 'acc_br_post_address', $customer->accounts->br_address, 35, 2);
	percent_row(_("Discount Percent:"), 'discount', $customer->discount, ($_SESSION['wa_current_user']->can_access('SA_CUSTOMER_CREDIT')) ? "" : " disabled=\"\"");
	percent_row(_("Prompt Payment Discount Percent:"), 'pymt_discount', $customer->pymt_discount, ($_SESSION['wa_current_user']->can_access('SA_CUSTOMER_CREDIT')) ? "" : " disabled=\"\"");
	amount_row(_("Credit Limit:"), 'credit_limit', $customer->credit_limit, ($_SESSION['wa_current_user']->can_access('SA_CUSTOMER_CREDIT')) ? "" : " disabled=\"\"");

	text_row(_("GSTNo:"), 'tax_id', $customer->tax_id, 35, 40);
	if (!$customer->id) {
		currencies_list_row(_("Customer's Currency:"), 'curr_code', $customer->curr_code);
	} else {
		label_row(_("Customer's Currency:"), $customer->curr_code);
		hidden('curr_code', $customer->curr_code);
	}
	$dim = get_company_pref('use_dimension');
	if ($dim >= 1) {
		dimensions_list_row(_("Dimension") . " 1:", 'dimension_id', $customer->dimension_id, true, " ", false, 1);
	}
	if ($dim > 1) {
		dimensions_list_row(_("Dimension") . " 2:", 'dimension2_id', $customer->dimension2_id, true, " ", false, 2);
	}
	if ($dim < 1) {
		hidden('dimension_id', 0);
	}
	if ($dim < 2) {
		hidden('dimension2_id', 0);
	}
	table_section(2);
	table_section_title(_("Contact log:"), 2);
	start_row();
	HTML::td(array('class' => 'ui-widget-content center',
								'colspan' => 2
					 ));
	UI::button('addLog', "Add log entry")->td->tr->tr(true)->td(array('colspan' => 2))->textarea('messageLog', array('cols' => 50,
																																																									'rows' => 25
																																																						 ));
	ContactLog::read($customer->id, 'C');
	/** @noinspection PhpUndefinedMethodInspection */
	HTML::textarea()->td->td;
	payment_terms_list_row(_("Pament Terms:"), 'payment_terms', $customer->payment_terms);
	credit_status_list_row(_("Credit Status:"), 'credit_status', $customer->credit_status);
	end_outer_table(1);

	$menu->endTab()->startTab('Customer Contacts', 'Customer Contacts');
	HTML::div(array('style' => 'text-align:center'))->div('Contacts', array('style' => 'min-height:200px;'));
	HTML::script('contact', array('type' => 'text/x-jquery-tmpl'))->table('contact-${id}', array('class' => '',
																																															'style' => 'display:inline-block'
																																												 ))->tr(true)->td(
		array('content' => '${name}',
				 'class' => 'tableheader',
				 'colspan' => 2
		))->td->tr;
	text_row("Name:", 'con_name-${id}', '${name}', 35, 40);
	text_row("Phone:", 'con_phone1-${id}', '${phone1}', 35, 40);
	text_row("Phone2:", 'con_phone2-${id}', '${phone2}', 35, 40);
	text_row("Email:", 'con_email-${id}', '${email}', 35, 40);
	text_row("Dept:", 'con_department-${id}', '${department}', 35, 40);

	HTML::td()->tr->table->script->div->div;

	$menu->endTab()->startTab('Extra Shipping Info', 'Extra Shipping Info');
	start_outer_table(Config::get('tables.style2'), 5);
	table_section(1);
	hidden('branch_code', $currentBranch->branch_code);
	table_section_title(_("Name and Contact"));
	text_row(_("Address Name:"), 'br_br_name', $currentBranch->br_name, 35, 40);
	text_row(_("Contact:"), 'br_contact_name', $currentBranch->contact_name, 35, 40);
	textarea_row(_("General Notes:"), 'br_notes', $currentBranch->notes, 35, 4);
	table_section_title(_("Sales"));
	sales_persons_list_row(_("Sales Person:"), 'br_salesman', $currentBranch->salesman);
	sales_areas_list_row(_("Sales Area:"), 'br_area', $currentBranch->area);
	sales_groups_list_row(_("Sales Group:"), 'br_group_no', $currentBranch->group_no);
	locations_list_row(_("Default Inventory Location:"), 'br_default_location', $currentBranch->default_location);
	shippers_list_row(_("Default Shipping Company:"), 'br_default_ship_via', $currentBranch->default_ship_via);
	tax_groups_list_row(_("Tax Group:"), 'br_tax_group_id', $currentBranch->tax_group_id);
	yesno_list_row(_("Disable this Branch:"), 'br_disable_trans', $currentBranch->disable_trans);
	table_section(2);
	table_section_title(_("GL Accounts"));
	gl_all_accounts_list_row(_("Sales Account:"), 'br_sales_account', $currentBranch->sales_account, false, false, true);
	gl_all_accounts_list_row(_("Sales Discount Account:"), 'br_sales_discount_account', $currentBranch->sales_discount_account);
	gl_all_accounts_list_row(_("Accounts Receivable Account:"), 'br_receivables_account', $currentBranch->receivables_account);
	gl_all_accounts_list_row(_("Prompt Payment Discount Account:"), 'br_payment_discount_account', $currentBranch->payment_discount_account);
	table_section_title(_("Addresses"));
	textarea_row(_("Address:"), 'br_br_address', $currentBranch->br_address, 35, 2);
	textarea_row(_("Branch Mailing Address:"), 'br_br_post_address', $currentBranch->br_post_address, 35, 4);
	end_outer_table(1);
	$menu->endTab()->startTab('Invoices', 'Invoices');
	HTML::div('transactions');
	$menu->endTab()->render();

	hidden('popup', Input::request('popup') );
	end_form();

	HTML::div('contactLog', array('title' => 'New contact log entry',
															 'class' => 'ui-widget-overlay',
															 'style' => 'display:none;'
													));
	HTML::p('New log entry:', array('class' => 'validateTips'));
	start_table();
	label_row('Date:', date('Y-m-d H:i:s'));
	hidden('type', ContactLog::CUSTOMER);
	text_row('Contact:', 'contact_name', $customer->accounts->contact_name, 40, 40);
	textarea_row('Entry:', 'message', '', 100, 10);
	end_table();
	HTML::p()->div->div(array('class' => 'center width50'));
	UI::button('btnCustomer', ($customer->id) ? 'Update Customer' : 'New Customer', array('name' => 'submit',
																																											 'type' => 'submit',
																																											 'style' => 'margin:10px;'
																																									));
	UI::button('btnCancel', 'Cancel', array('name' => 'cancel',
																				 'type' => 'submit',
																				 'class' => 'ui-helper-hidden',
																				 'style' => 'margin:10px;'
																		));
	/** @noinspection PhpUndefinedMethodInspection */
	HTML::_div();
	if ( !Input::get('popup') && !Input::get('id')) {
		HTML::_div()->div('shortcuts', array('class' => 'width50 center'));
		$shortcuts = new MenuUI(array('noajax' => true));
		$shortcuts->startTab('Create Order', 'Create Order for this customer!', '/sales/sales_order_entry.php?NewOrder=Yes&customer_id=');
		$shortcuts->endTab();
		$shortcuts->startTab('Create Quote', 'Create Quote for this customer!', '/sales/sales_order_entry.php?NewQuotation=Yes&customer_id=');
		$shortcuts->endTab();
		$shortcuts->render();
		/** @noinspection PhpUndefinedMethodInspection */
	}
	HTML::_div();

	end_page(true, true);
