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
	Page::set_security(SA_GLREP);
	function display_type($type, $typename, &$dec, &$rep, $showbalance) {
		$printtitle = 0; //Flag for printing type name
		//Get Accounts directly under this group/type
		$result = GL_Account::get_all(null, null, $type);
		while ($account = DB::fetch($result))
		{
			//Print Type Title if it has atleast one non-zero account
			if (!$printtitle) {
				$printtitle = 1;
				$rep->row -= 4;
				$rep->TextCol(0, 1, $type);
				$rep->TextCol(1, 4, $typename);
				$rep->row -= 4;
				$rep->Line($rep->row);
				$rep->NewLine();
			}
			if ($showbalance == 1) {
				$begin = Dates::begin_fiscalyear();
				if (GL_Account::is_balancesheet($account["account_code"])) {
					$begin = "";
				}
				$balance = GL_Trans::get_from_to($begin, Dates::today(), $account["account_code"], 0);
			}
			$rep->TextCol(0, 1, $account['account_code']);
			$rep->TextCol(1, 2, $account['account_name']);
			$rep->TextCol(2, 3, $account['account_code2']);
			if ($showbalance == 1) {
				$rep->AmountCol(3, 4, $balance, $dec);
			}
			$rep->NewLine();
		}
		//Get Account groups/types under this group/type
		$result = GL_Type::get_all(false, false, $type);
		while ($accounttype = DB::fetch($result))
		{
			//Print Type Title if has sub types and not previously printed
			if (!$printtitle) {
				$printtitle = 1;
				$rep->row -= 4;
				$rep->TextCol(0, 1, $type);
				$rep->TextCol(1, 4, $typename);
				$rep->row -= 4;
				$rep->Line($rep->row);
				$rep->NewLine();
			}
			display_type($accounttype["id"], $accounttype["name"], $dec, $rep, $showbalance);
		}
	}

	print_Chart_of_Accounts();
	function print_Chart_of_Accounts() {
		$showbalance = $_POST['PARAM_0'];
		$comments = $_POST['PARAM_1'];
		$destination = $_POST['PARAM_2'];
		if ($destination) {
			include_once(APPPATH . "reports/excel.php");
		}
		else
		{
			include_once(APPPATH . "reports/pdf.php");
		}
		$dec = 0;
		$cols = array(0, 50, 300, 425, 500);
		$headers = array(_('Account'), _('Account Name'), _('Account Code'), _('Balance'));
		$aligns = array('left', 'left', 'left', 'right');
		$params = array(0 => $comments);
		$rep = new ADVReport(_('Chart of Accounts'), "ChartOfAccounts", User::pagesize());
		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header();
		$classresult = GL_Class::get_all(false);
		while ($class = DB::fetch($classresult))
		{
			$rep->Font('bold');
			$rep->TextCol(0, 1, $class['cid']);
			$rep->TextCol(1, 4, $class['class_name']);
			$rep->Font();
			$rep->NewLine();
			//Get Account groups/types under this group/type with no parents
			$typeresult = GL_Type::get_all(false, $class['cid'], -1);
			while ($accounttype = DB::fetch($typeresult))
			{
				display_type($accounttype["id"], $accounttype["name"], $dec, $rep, $showbalance);
			}
			$rep->NewLine();
		}
		$rep->Line($rep->row + 10);
		$rep->End();
	}


