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

	Page::set_security(SA_GLANALYTIC);
	print_audit_trail();
  /**
   * @param $from
   * @param $to
   * @param $type
   * @param $user
   *
   * @return null|PDOStatement
   */function get_transactions($from, $to, $type, $user) {
		$fromdate = Dates::date2sql($from) . " 00:00:00";
		$todate = Dates::date2sql($to) . " 23:59.59";
		$sql = "SELECT a.*,
		SUM(IF(ISNULL(g.amount), NULL, IF(g.amount > 0, g.amount, 0))) AS amount,
		u.user_id,
		UNIX_TIMESTAMP(a.stamp) as unix_stamp
		FROM audit_trail AS a JOIN users AS u
		LEFT JOIN gl_trans AS g ON (g.type_no=a.trans_no
			AND g.type=a.type)
		WHERE a.user = u.id ";
		if ($type != -1) {
			$sql .= "AND a.type=$type ";
		}
		if ($user != -1) {
			$sql .= "AND a.user='$user' ";
		}
		$sql .= "AND a.stamp >= '$fromdate'
			AND a.stamp <= '$todate'
		GROUP BY a.trans_no,a.gl_seq,a.stamp	
		ORDER BY a.stamp,a.gl_seq";
		return DB::query($sql, "No transactions were returned");
	}

	function print_audit_trail() {
		global $systypes_array;
		$from = $_POST['PARAM_0'];
		$to = $_POST['PARAM_1'];
		$systype = $_POST['PARAM_2'];
		$user = $_POST['PARAM_3'];
		$comments = $_POST['PARAM_4'];
		$destination = $_POST['PARAM_5'];
		if ($destination) {
			include_once(APPPATH . "reports/excel.php");
		}
		else {
			include_once(APPPATH . "reports/pdf.php");
		}
		$dec = User::price_dec();
		$cols = array(0, 60, 120, 180, 240, 340, 400, 460, 520);
		$headers = array(
			_('Date'), _('Time'), _('User'), _('Trans Date'), _('Type'), _('#'), _('Action'), _('Amount')
		);
		$aligns = array('left', 'left', 'left', 'left', 'left', 'left', 'left', 'right');
		$usr = Users::get($user);
		$user_id = $usr['user_id'];
		$params = array(
			0 => $comments, 1 => array(
				'text' => _('Period'), 'from' => $from, 'to' => $to
			), 2 => array(
				'text' => _('Type'), 'from' => ($systype != -1 ? $systypes_array[$systype] : _('All')), 'to' => ''
			), 3 => array(
				'text' => _('User'), 'from' => ($user != -1 ? $user_id : _('All')), 'to' => ''
			)
		);
		$rep = new ADVReport(_('Audit Trail'), "AuditTrail", User::pagesize());
		$rep->Font();
		$rep->Info($params, $cols, $headers, $aligns);
		$rep->Header();
		$trans = get_transactions($from, $to, $systype, $user);
		while ($myrow = DB::fetch($trans)) {
			$rep->TextCol(0, 1, Dates::sql2date(date("Y-m-d", $myrow['unix_stamp'])));
			if (User::date_format() == 0) {
				$rep->TextCol(1, 2, date("h:i:s a", $myrow['unix_stamp']));
			}
			else {
				$rep->TextCol(1, 2, date("H:i:s", $myrow['unix_stamp']));
			}
			$rep->TextCol(2, 3, $myrow['user_id']);
			$rep->TextCol(3, 4, Dates::sql2date($myrow['gl_date']));
			$rep->TextCol(4, 5, $systypes_array[$myrow['type']]);
			$rep->TextCol(5, 6, $myrow['trans_no']);
			if ($myrow['gl_seq'] == NULL) {
				$action = _('Changed');
			}
			else {
				$action = _('Closed');
			}
			$rep->TextCol(6, 7, $action);
			if ($myrow['amount'] != NULL) {
				$rep->AmountCol(7, 8, $myrow['amount'], $dec);
			}
			$rep->NewLine(1, 2);
		}
		$rep->Line($rep->row + 4);
		$rep->End();
	}

