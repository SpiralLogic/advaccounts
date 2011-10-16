<?php
	/**********************************************************************
	Copyright (C) FrontAccounting, LLC.
	Released under the terms of the GNU General Public License, GPL,
	as published by the Free Software Foundation, either version 3
	of the License, or (at your option) any later version.
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	See the License here <http://www.gnu.org/licenses/gpl-3.0.html>.
	 ***********************************************************************/

	include_once(APP_PATH . "gl/includes/db/gl_db_trans.php");
	include_once(APP_PATH . "gl/includes/db/gl_db_bank_trans.php");
	include_once(APP_PATH . "gl/includes/db/gl_db_banking.php");
	include_once(APP_PATH . "gl/includes/db/gl_db_bank_accounts.php");
	include_once(APP_PATH . "gl/includes/db/gl_db_currencies.php");
	include_once(APP_PATH . "gl/includes/db/gl_db_rates.php");
	include_once(APP_PATH . "gl/includes/db/gl_db_accounts.php");
	include_once(APP_PATH . "gl/includes/db/gl_db_account_types.php");
	function payment_person_currency($type, $person_id) {
		switch ($type)
		{
			case PT_MISC :
			case PT_QUICKENTRY :
			case PT_WORKORDER :
				return Banking::get_company_currency();

			case PT_CUSTOMER :
				return Banking::get_customer_currency($person_id);

			case PT_SUPPLIER :
				return Banking::get_supplier_currency($person_id);

			default :
				return Banking::get_company_currency();
		}
	}

	function payment_person_name($type, $person_id, $full = true) {
		global $payment_person_types;

		switch ($type)
		{
			case PT_MISC :
				return $person_id;
			case PT_QUICKENTRY :
				$qe = get_quick_entry($person_id);
				return ($full ? $payment_person_types[$type] . " " : "") . $qe["description"];
			case PT_WORKORDER :
				global $wo_cost_types;
				return $wo_cost_types[$person_id];
			case PT_CUSTOMER :
				return ($full ? $payment_person_types[$type] . " " : "") . get_customer_name($person_id);
			case PT_SUPPLIER :
				return ($full ? $payment_person_types[$type] . " " : "") . get_supplier_name($person_id);
			default :
				//DisplayDBerror("Invalid type sent to person_name");
				//return;
				return '';
		}
	}

	function payment_person_has_items($type) {
		switch ($type)
		{
			case PT_MISC :
				return true;
			case PT_QUICKENTRY :
				return db_has_quick_entries();
			case PT_WORKORDER : // 070305 changed to open workorders JH
				return db_has_open_workorders();
			case PT_CUSTOMER :
				return db_has_customers();
			case PT_SUPPLIER :
				return db_has_suppliers();
			default :
				Errors::show_db_error("Invalid type sent to has_items", "");
				return false;
		}
	}

	function get_class_type_convert($ctype) {
		global $use_oldstyle_convert;
		if (Config::get('accounts.gl.oldconvertstyle') == 1)
			return (($ctype >= CL_INCOME || $ctype == CL_NONE) ? -1 : 1);
		else
			return ((($ctype >= CL_LIABILITIES && $ctype <= CL_INCOME) || $ctype == CL_NONE) ? -1 : 1);
	}

	//--------------------------------------------------------------------------------
?>