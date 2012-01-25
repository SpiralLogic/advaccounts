<?php
	/**********************************************************************
	Copyright (C) Advanced Group PTY LTD
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/
	require_once($_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "bootstrap.php");
	Page::start(_($help_context = "Company Setup"), SA_SETUPCOMPANY);
	if (isset($_POST['update']) && $_POST['update'] != "") {
		$input_error = 0;
		if (!Validation::is_num('login_tout', 10)) {
			Event::error(_("Login timeout must be positive number not less than 10."));
			JS::set_focus('login_tout');
			$input_error = 1;
		}
		if (strlen($_POST['coy_name']) == 0) {
			$input_error = 1;
			Event::error(_("The company name must be entered."));
			JS::set_focus('coy_name');
		}
		if (isset($_FILES['pic']) && $_FILES['pic']['name'] != '') {
			$result = $_FILES['pic']['error'];
			$filename = COMPANY_PATH . "/images";
			if (!file_exists($filename)) {
				mkdir($filename);
			}
			$filename .= "/" . $_FILES['pic']['name'];
			//But check for the worst
			if (!in_array((substr(trim($_FILES['pic']['name']), -3)), array('jpg', 'JPG', 'png', 'PNG'))
			) {
				Event::error(_('Only jpg and png files are supported - a file extension of .jpg or .png is expected'));
				$input_error = 1;
			}
			elseif ($_FILES['pic']['size'] > (Config::get('item_images_max_size') * 1024)) { //File Size Check
				Event::error(_('The file size is over the maximum allowed. The maximum size allowed in KB is') . ' ' . Config::get('item_images_max_size'));
				$input_error = 1;
			}
			elseif ($_FILES['pic']['type'] == "text/plain") { //File type Check
				Event::error(_('Only graphics files can be uploaded'));
				$input_error = 1;
			}
			elseif (file_exists($filename)) {
				$result = unlink($filename);
				if (!$result) {
					Event::error(_('The existing image could not be removed'));
					$input_error = 1;
				}
			}
			if ($input_error != 1) {
				$result = move_uploaded_file($_FILES['pic']['tmp_name'], $filename);
				$_POST['coy_logo'] = $_FILES['pic']['name'];
				if (!$result) {
					Event::error(_('Error uploading logo file'));
				}
			}
		}
		if (check_value('del_coy_logo')) {
			$filename = COMPANY_PATH . "/images/" . $_POST['coy_logo'];
			if (file_exists($filename)) {
				$result = unlink($filename);
				if (!$result) {
					Event::error(_('The existing image could not be removed'));
					$input_error = 1;
				}
				else {
					$_POST['coy_logo'] = "";
				}
			}
		}
		if ($_POST['add_pct'] == "") {
			$_POST['add_pct'] = -1;
		}
		if ($_POST['round_to'] <= 0) {
			$_POST['round_to'] = 1;
		}
		if ($input_error != 1) {
			DB_Company::update_setup($_POST);
			$_SESSION['current_user']->timeout = $_POST['login_tout'];
			Event::notice(_("Company setup has been updated."));
		}
		JS::set_focus('coy_name');
		Ajax::i()->activate('_page_body');
	} /* end of if submit */
	start_form(true);
	$myrow = DB_Company::get_prefs();
	$_POST['coy_name'] = $myrow["coy_name"];
	$_POST['gst_no'] = $myrow["gst_no"];
	$_POST['tax_prd'] = $myrow["tax_prd"];
	$_POST['tax_last'] = $myrow["tax_last"];
	$_POST['coy_no'] = $myrow["coy_no"];
	$_POST['postal_address'] = $myrow["postal_address"];
	$_POST['phone'] = $myrow["phone"];
	$_POST['fax'] = $myrow["fax"];
	$_POST['email'] = $myrow["email"];
	$_POST['coy_logo'] = $myrow["coy_logo"];
	$_POST['suburb'] = $myrow["suburb"];
	$_POST['use_dimension'] = $myrow["use_dimension"];
	$_POST['base_sales'] = $myrow["base_sales"];
	$_POST['no_item_list'] = $myrow["no_item_list"];
	$_POST['no_customer_list'] = $myrow["no_customer_list"];
	$_POST['no_supplier_list'] = $myrow["no_supplier_list"];
	$_POST['curr_default'] = $myrow["curr_default"];
	$_POST['f_year'] = $myrow["f_year"];
	$_POST['time_zone'] = $myrow["time_zone"];
	$_POST['version_id'] = $myrow["version_id"];
	$_POST['add_pct'] = $myrow['add_pct'];
	$_POST['login_tout'] = $myrow['login_tout'];
	if ($_POST['add_pct'] == -1) {
		$_POST['add_pct'] = "";
	}
	$_POST['round_to'] = $myrow['round_to'];
	$_POST['del_coy_logo'] = 0;
	start_outer_table('tablestyle2');
	table_section(1);
	text_row_ex(_("Name (to appear on reports):"), 'coy_name', 42, 50);
	textarea_row(_("Address:"), 'postal_address', $_POST['postal_address'], 35, 6);
	text_row_ex(_("Suburb:"), 'suburb', 25, 55);
	text_row_ex(_("Phone Number:"), 'phone', 25, 55);
	text_row_ex(_("Fax Number:"), 'fax', 25);
	email_row_ex(_("Email Address:"), 'email', 25, 55);
	text_row_ex(_("Official Company Number:"), 'coy_no', 25);
	text_row_ex(_("GSTNo:"), 'gst_no', 25);
	GL_Currency::row(_("Home Currency:"), 'curr_default', $_POST['curr_default']);
	GL_UI::fiscalyears_row(_("Fiscal Year:"), 'f_year', $_POST['f_year']);
	table_section(2);
	text_row_ex(_("Tax Periods:"), 'tax_prd', 10, 10, '', null, null, _('Months.'));
	text_row_ex(_("Tax Last Period:"), 'tax_last', 10, 10, '', null, null, _('Months back.'));
	label_row(_("Company Logo:"), $_POST['coy_logo']);
	file_row(_("New Company Logo (.jpg)") . ":", 'pic', 'pic');
	check_row(_("Delete Company Logo:"), 'del_coy_logo', $_POST['del_coy_logo']);
	number_list_row(_("Use Dimensions:"), 'use_dimension', null, 0, 2);
	Sales_Type::row(_("Base for auto price calculations:"), 'base_sales', $_POST['base_sales'], false, _('No base price list'));
	text_row_ex(_("Add Price from Std Cost:"), 'add_pct', 10, 10, '', null, null, "%");
	$curr = GL_Currency::get($_POST['curr_default']);
	text_row_ex(_("Round to nearest:"), 'round_to', 10, 10, '', null, null, $curr['hundreds_name']);
	check_row(_("Search Item List"), 'no_item_list', null);
	check_row(_("Search Customer List"), 'no_customer_list', null);
	check_row(_("Search Supplier List"), 'no_supplier_list', null);
	label_row("", "&nbsp;");
	check_row(_("Time Zone on Reports"), 'time_zone', $_POST['time_zone']);
	text_row_ex(_("Login Timeout:"), 'login_tout', 10, 10, '', null, null, _('seconds'));
	label_row(_("Version Id"), $_POST['version_id']);
	end_outer_table(1);
	hidden('coy_logo', $_POST['coy_logo']);
	submit_center('update', _("Update"), true, '', 'default');
	end_form(2);
	Page::end();

?>
