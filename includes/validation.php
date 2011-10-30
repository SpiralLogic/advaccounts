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
	class Validation
	{
		const CUSTOMERS = "debtors_master";
		const CURRENCIES = "currencies";
		const SALES_TYPES = "sales_types";
		const ITEM_TAX_TYPES = "item_tax_types";
		const TAX_TYPES = "tax_types";
		const TAX_GROUP = "tax_groups";
		const MOVEMENT_TYPES = "movement_types";
		const BRANCHES = "cust_branch WHERE debtor_no=";
		const BRANCHES_ACTIVE = "cust_branch WHERE !inactive";
		const SALESPERSONS = "salesman";
		const SALES_AREA = "areas";
		const SHIPPERS = "shippers";
		const OPEN_WORKORDERS = "workorders WHERE closed=0";
		const WORKORDERS = "workorders";
		const OPEN_DIMENSIONS = "dimensions WHERE closed=0";
		const DIMENSIONS = "dimensions";
		const SUPPLIERS = "suppliers";
		const STOCK_ITEMS = "stock_master";
		const BOM_ITEMS = "stock_master WHERE mb_flag=";
		const MANUFACTURE_ITEMS = "stock_master WHERE mb_flag=";
		const PURCHASE_ITEMS = "stock_master WHERE mb_flag=";
		const COST_ITEMS = "stock_master WHERE mb_flag!=";
		const STOCK_CATEGORIES = "stock_category";
		const WORKCENTRES = "workcentres";
		const LOCATIONS = "locations";
		const BANK_ACCOUNTS = "bank_accounts";
		const CASH_ACCOUNTS = "bank_accounts";
		const	GL_ACCOUNTS = "accounts";
		const GL_ACCOUNT_GROUPS = "chart_types";
		const QUICK_ENTRIES = "quick_entries";
		const TAGS = "FROM tags WHERE type=";
		const EMPTY_RESULT = "";

		public static function check($validate, $msg = '', $extra = null)
		{
			//if (!property_exists(__CLASS__, $validate)) return ui_msgs::display_error("TABLE $validate doesn't exist", true);
			if ($extra === false) {
				return 0;
			}
			$extra = ($extra !== null) ? DBOld::escape($extra) : '';

			$result = DBOld::query('SELECT COUNT(*) FROM ' . $validate . ' ' . $extra, 'Could not do check empty query');
			$myrow  = DBOld::fetch_row($result);
			if (!($myrow[0] > 0)) {
				throw new Adv_Exception($msg);
				end_page();
				exit;
			} else {
				return $myrow[0];
			}
		}

		//
		//	Integer input check
		//	Return 1 if number has proper form and is within <min, max> range
		//
		public static function is_int($postname, $min = null, $max = null)
		{
			if (!isset($_POST[$postname])) {
				return 0;
			}
			$num = input_num($postname);
			if (!is_int($num)) {
				return 0;
			}
			if (isset($min) && ($num < $min)) {
				return 0;
			}
			if (isset($max) && ($num > $max)) {
				return 0;
			}
			return 1;
		}

		//
		//	Numeric input check.
		//	Return 1 if number has proper form and is within <min, max> range
		//	Empty/not defined fields are defaulted to $dflt value.
		//
		public static function is_num($postname, $min = null, $max = null, $dflt = 0)
		{
			if (!isset($_POST[$postname])) {
				return 0;
			}
			$num = input_num($postname, $dflt);
			if ($num === false || $num === null) {
				return 0;
			}
			if (isset($min) && ($num < $min)) {
				return 0;
			}
			if (isset($max) && ($num > $max)) {
				return 0;
			}
			return 1;
		}

		public static function user_num($input)
		{
			$num = trim($input);
			$sep = Config::get('separators_thousands', user_tho_sep());
			if ($sep != '') {
				$num = str_replace($sep, '', $num);
			}
			$sep = Config::get('separators_decimal', user_dec_sep());
			if ($sep != '.') {
				$num = str_replace($sep, '.', $num);
			}
			if (!is_numeric($num)) {
				return false;
			}
			$num = (float)$num;
			if ($num == (int)$num) {
				return (int)$num;
			} else
			{
				return $num;
			}
		}
	}