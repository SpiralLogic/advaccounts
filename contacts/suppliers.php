<?php

	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
	Session::i()->App->set_selected('Contacts');
	if (AJAX_REFERRER) {
		if (isset($_GET['term'])) {
			$data = Creditor::search($_GET['term']);
			JS::renderJSON($data);
		}
	}
	if (isset($_POST['name'])) {
		$data['company'] = $supplier = new Creditor();
		$data['company']->save($_POST);
	}
	elseif (Input::request('id', Input::NUMERIC) > 0) {
		$data['company'] = $supplier = new Creditor(Input::request('id', Input::NUMERIC));
		$data['contact_log'] = Contact_Log::read($supplier->id, CT_SUPPLIER);
		$_SESSION['global_supplier_id'] = $supplier->id;
	}
	else {
		$data['company'] = $supplier = new Debtor();
	}
	if (AJAX_REFERRER) {
		$data['status'] = $supplier->getStatus();
		JS::renderJSON($data);
	}
	JS::footerFile("js/company.js");
	Page::start(_($help_context = "Suppliers"), SA_SUPPLIER, Input::request('frame'));
	Validation::check(Validation::SALES_AREA, _("There are no sales areas defined in the system. At least one sales area is required before proceeding."));
	Validation::check(Validation::SHIPPERS, _("There are no shipping companies defined in the system. At least one shipping company is required before proceeding."));
	Validation::check(Validation::TAX_GROUP, _("There are no tax groups defined in the system. At least one tax group is required before proceeding."));
	JS::onload("Company.setValues(" . json_encode($data) . ");");
	$currentContact = $supplier->contacts[$supplier->defaultContact];
	$currentBranch = $supplier->branches[$supplier->defaultBranch];
	if (isset($_POST['delete'])) {
		$supplier->delete();
		$status = $supplier->getStatus();
		Event::notice($status['message']);
	}
	if (!Input::get('frame') && !Input::get('id')) {
		/** @noinspection PhpUndefinedMethodInspection */
		HTML::div('companysearch');
		HTML::table(array('class' => 'marginauto bold'));
		HTML::tr(true)->td(true);
		UI::search('supplier', array('label' => 'Search Supplier:', 'size' => 80, 'callback' => 'Company.fetch', 'focus' => true));
		HTML::td()->tr->table->div;
	}
	start_form();
	$menu = new MenuUi();
	$menu->startTab('Details', 'Supplier Details', '#', 'text-align:center');
	HTML::div('companyIDs');
	HTML::table(array("class" => "marginauto bold"))->tr(true)->td(true);
	HTML::label(array(
									 'for' => 'name', 'content' => 'Supplier name:'
							), false);
	HTML::input('name', array(
													 'value' => $supplier->name, 'name' => 'name', 'size' => 50
											));
	HTML::td()->td(array(
											'content' => _("Supplier ID: "), "style" => "width:90px"
								 ), false)->td(true);
	HTML::input('id', array(
												 'value' => $supplier->id, 'name' => 'id', 'size' => 10, 'maxlength' => '7'
										));
	HTML::td()->tr->table->div;
	start_outer_table('tablestyle2');
	table_section(1);
	table_section_title(_("Shipping Details"), 2);
	/** @noinspection PhpUndefinedMethodInspection */
	HTML::tr(true)->td('branchSelect', array(
																					'colspan' => 2, 'class' => "center"
																		 ));
	UI::select('branchList', array_map(function($v) {
		return $v->br_name;
	}, $supplier->branches), array('name' => 'branchList'));
	UI::button('addBranch', 'Add new address', array(
																									'class' => 'invis', 'name' => 'addBranch'
																						 ));
	HTML::td()->tr;
	text_row(_("Contact:"), 'br_contact_name', $currentBranch->contact_name, 35, 40);
	//hidden('br_contact_name', $supplier->contact_name);
	text_row(_("Phone Number:"), 'br_phone', $currentBranch->phone, 35, 30);
	text_row(_("2nd Phone Number:"), 'br_phone2', $currentBranch->phone2, 35, 30);
	text_row(_("Fax Number:"), 'br_fax', $currentBranch->fax, 35, 30);
	email_row(_("Email:"), 'br_email', $currentBranch->email, 35, 55);
	textarea_row(_("Street:"), 'br_br_address', $currentBranch->br_address, 35, 2);
	Contact_Postcode::render(array(
																'br_city', $currentBranch->city
													 ), array(
																	 'br_state', $currentBranch->state
															), array(
																			'br_postcode', $currentBranch->postcode
																 ));
	table_section(2);
	table_section_title(_("Accounts Details"), 2);
	/** @noinspection PhpUndefinedMethodInspection */
	HTML::tr(true)->td(array(
													'class' => "center", 'colspan' => 2
										 ));
	UI::button('useShipAddress', _("Use shipping details"), array('name' => 'useShipAddress'));
	text_row(_("Accounts Contact:"), 'acc_contact_name', $supplier->accounts->contact_name, 35, 40);
	text_row(_("Phone Number:"), 'acc_phone', $supplier->accounts->phone, 35, 30);
	text_row(_("Secondary Phone Number:"), 'acc_phone2', $supplier->accounts->phone2, 35, 30);
	text_row(_("Fax Number:"), 'acc_fax', $supplier->accounts->fax, 35, 30);
	email_row(_("E-mail:"), 'acc_email', $supplier->accounts->email, 35, 55);
	textarea_row(_("Street:"), 'acc_br_address', $supplier->accounts->br_address, 35, 2);
	Contact_Postcode::render(array(
																'acc_city', $supplier->accounts->city
													 ), array(
																	 'acc_state', $supplier->accounts->state
															), array(
																			'acc_postcode', $supplier->accounts->postcode
																 ));
	end_outer_table(1);
	$menu->endTab()->startTab('Accounts', 'Accounts');
	hidden('accounts_id', $supplier->accounts->accounts_id);
	start_outer_table('tablestyle2');
	table_section(1);
	table_section_title(_("Accounts Details:"), 2);
	percent_row(_("Discount Percent:"), 'discount', $supplier->discount, (User::i()->can_access(SA_SUPPLIERCREDIT)) ? "" : " disabled");
	percent_row(_("Prompt Payment Discount Percent:"), 'pymt_discount', $supplier->pymt_discount, (User::i()->can_access(SA_SUPPLIERCREDIT)) ? "" :
	 " disabled");
	amount_row(_("Credit Limit:"), 'credit_limit', $supplier->credit_limit, null, null, 0, (User::i()->can_access(SA_SUPPLIERCREDIT)) ? "" :
	 " disabled");
	Sales_Type::row(_("Sales Type/Price List:"), 'sales_type', $supplier->sales_type);
	record_status_list_row(_("Supplier status:"), 'inactive');
	text_row(_("GSTNo:"), 'tax_id', $supplier->tax_id, 35, 40);
	if (!$supplier->id) {
		GL_Currency::row(_("Supplier's Currency:"), 'curr_code', $supplier->curr_code);
	}
	else {
		label_row(_("Supplier's Currency:"), $supplier->curr_code);
		hidden('curr_code', $supplier->curr_code);
	}
	GL_UI::payment_terms_row(_("Pament Terms:"), 'payment_terms', $supplier->payment_terms);
	Sales_CreditStatus::row(_("Credit Status:"), 'credit_status', $supplier->credit_status);
	table_section(2);
	table_section_title(_("Contact log:"), 1);
	start_row();
	HTML::td(array(
								'class' => 'ui-widget-content center'
					 ));
	UI::button('addLog', "Add log entry")->td->tr->tr(true)->td(null)->textarea('messageLog', array('cols' => 50, 'rows' => 20));
	Contact_Log::read($supplier->id, 'C');
	/** @noinspection PhpUndefinedMethodInspection */
	HTML::textarea()->td->tr;
	end_outer_table(1);
	$menu->endTab()->startTab('Supplier Contacts', 'Supplier Contacts');
	HTML::div(array('style' => 'text-align:center'))->div('Contacts', array('style' => 'min-height:200px;'));
	HTML::script('contact', array('type' => 'text/x-jquery-tmpl'))->table('contact-${id}', array(
																																															'class' => '', 'style' => 'display:inline-block'
																																												 ))->tr(true)->td(array(
																																																							 'content' => '${name}',
																																																							 'class' => 'tablehead',
																																																							 'colspan' => 2
																																																					))->td->tr;
	text_row("Name:", 'con_name-${id}', '${name}', 35, 40);
	text_row("Phone:", 'con_phone1-${id}', '${phone1}', 35, 40);
	text_row("Phone2:", 'con_phone2-${id}', '${phone2}', 35, 40);
	text_row("Email:", 'con_email-${id}', '${email}', 35, 40);
	text_row("Dept:", 'con_department-${id}', '${department}', 35, 40);
	HTML::td()->tr->table->script->div->div;
	$menu->endTab()->startTab('Extra Shipping Info', 'Extra Shipping Info');
	start_outer_table('tablestyle2');
	hidden('branch_id', $currentBranch->branch_id);
	table_section(1);
	table_section_title(_("Sales"));
	Sales_UI::persons_row(_("Sales Person:"), 'br_salesman', $currentBranch->salesman);
	Sales_UI::areas_row(_("Sales Area:"), 'br_area', $currentBranch->area);
	Sales_UI::groups_row(_("Sales Group:"), 'br_group_no', $currentBranch->group_no);
	Inv_Location::row(_("Default Inventory Location:"), 'br_default_location', $currentBranch->default_location);
	Sales_UI::shippers_row(_("Default Shipping Company:"), 'br_default_ship_via', $currentBranch->default_ship_via);
	Tax_Groups::row(_("Tax Group:"), 'br_tax_group_id', $currentBranch->tax_group_id);
	yesno_list_row(_("Disable this Branch:"), 'br_disable_trans', $currentBranch->disable_trans);
	table_section(2);
	table_section_title(_("GL Accounts"));
	GL_UI::all_row(_("Sales Account:"), 'br_sales_account', $currentBranch->sales_account, false, false, true);
	GL_UI::all_row(_("Sales Discount Account:"), 'br_sales_discount_account', $currentBranch->sales_discount_account);
	GL_UI::all_row(_("Accounts Receivable Account:"), 'br_receivables_account', $currentBranch->receivables_account);
	GL_UI::all_row(_("Prompt Payment Discount Account:"), 'br_payment_discount_account', $currentBranch->payment_discount_account);
	table_section_title(_("Notes"));
	textarea_row(_("General Notes:"), 'br_notes', $currentBranch->notes, 35, 4);
	end_outer_table(1);

	hidden('frame', Input::request('frame'));
		end_form();
	$menu->endTab()->startTab('Invoices', 'Invoices');
	echo "<div id='invoiceFrame' data-src='" . PATH_TO_ROOT . "/purchases/inquiry/supplier_allocation_inquiry.php?supplier_id=" . $supplier->id . "' ></div> ";
	$menu->endTab()->render();

	HTML::div('contactLog', array(
															 'title' => 'New contact log entry', 'class' => 'ui-widget-overlay', 'style' => 'display:none;'
													));
	hidden('type', CT_SUPPLIER);
	start_table();
	label_row('Date:', date('Y-m-d H:i:s'));
	text_row('Contact:', 'contact_name', $supplier->accounts->contact_name, 35, 40);
	textarea_row('Entry:', 'message', '', 100, 10);
	end_table();
	HTML::_div()->div(array('class' => 'center width50'));
	UI::button('btnConfirm', ($supplier->id) ? 'Update Supplier' : 'New Supplier', array(
																																											 'name' => 'submit', 'type' => 'submit', 'style' => 'margin:10px;'
																																									));
	UI::button('btnCancel', 'Cancel', array(
																				 'name' => 'cancel', 'type' => 'submit', 'class' => 'ui-helper-hidden', 'style' => 'margin:10px;'
																		));
	/** @noinspection PhpUndefinedMethodInspection */
	HTML::_div();

	Page::end(false, true);
