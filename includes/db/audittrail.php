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
	class DB_AuditTrail {
		public static function add($trans_type, $trans_no, $trans_date, $descr = '') {
			$insertid = DB::insert('audit_trail')
			 ->values(array('type' => $trans_type, 'trans_no' => $trans_no,
				'user' => $_SESSION["wa_current_user"]->user, 'fiscal_year' => get_company_pref('f_year'),
				'gl_date' => Dates::date2sql($trans_date), 'description' => $descr, 'gl_seq' => 0
			))->exec();

			// all audit records beside latest one should have gl_seq set to NULL
			// to avoid need for subqueries (not existing in MySQL 3) all over the code
			DB::update('audit_trail')->value('gl_seq', null)->where('type=', $trans_type)->and_where('trans_no=', $trans_no)
			 ->and_where('id!=', $insertid)->exec();
		}

		public static function get_all($trans_type, $trans_no) {
			$result = DB::select()->from('audit_trail')->where('type=', $trans_type)->and_where('trans_no-', $trans_no)->fetch()->all();
			return $result;
		}

		public static function get_last($trans_type, $trans_no) {
			$result = DB::select()->from('audit_trail')->where('type=', $trans_type)->and_where('trans_no-', $trans_no)->and_where("NOT ISNULL(gl_seq)")->fetch()->one();
			return $result;
		}

		/*
			 Confirm and close for edition all transactions up to date $todate,
			 and reindex	journal.
		 */
		public static function close_transactions($todate) {
			$errors = 0;
			$sql = "SELECT DISTINCT a.id, a.gl_date, a.fiscal_year"
			 . " FROM gl_trans gl"
			 . " LEFT JOIN audit_trail a ON
			(gl.type=a.type AND gl.type_no=a.trans_no)"
			 . " WHERE gl_date<='" . Dates::date2sql($todate) . "'"
			 . " AND NOT ISNULL(gl_seq)"
			 . " ORDER BY a.fiscal_year, a.gl_date, a.id";

			$result = DBOld::query($sql, "Cannot select transactions for closing");

			if (DBOld::num_rows($result)) {
				$last_year = $counter = 0;
				while ($row = DBOld::fetch($result)) {
					if ($row['fiscal_year'] == null) {
						$errors = 1;
					} elseif ($last_year != $row['fiscal_year']) {
						$last_year = $row['fiscal_year'];
						$counter = 1; // reset counter on fiscal year change
					} else {
						$counter++;
					}
					DB::update('audit_trail')->value('gl_seq', $counter)->where('id=', $row['id'])->exec();
				}
			}
			if ($errors)
				ui_msgs::display_warning(_("Some transactions journal GL postings were not indexed due to lack of audit trail record."));
		}

		/*
			 Reopen all transactions for edition up from date $fromdate
		 */
		public static function open_transactions($fromdate) {

			$sql = "SELECT a.id, a.gl_date, a.fiscal_year"
			 . " FROM gl_trans gl"
			 . " LEFT JOIN audit_trail a ON
			(gl.type=a.type AND gl.type_no=a.trans_no)"
			 . " WHERE gl_date>='" . Dates::date2sql($fromdate) . "'"
			 . " AND !ISNULL(gl_seq)"
			 . " ORDER BY a.fiscal_year, a.gl_date, a.id";
			$result = DBOld::query($sql, "Cannot select transactions for openning");

			if (DBOld::num_rows($result)) {
				while ($row = DBOld::fetch($result)) {
					if ($row['fiscal_year'] == null) {
						continue;
					}
					DB::update('audit_trail')->value('gl_seq', 0)->where('id=', $row['id'])->exec();
				}
			}
		}

		/*
			 Closed transactions have gl_seq number assigned.
		 */
		public static function is_closed_trans($type, $trans_no) {
			$sql = "SELECT	gl_seq  FROM audit_trail"
			 . " WHERE type=" . DBOld::escape($type)
			 . " AND trans_no=" . DBOld::escape($trans_no)
			 . " AND gl_seq>0";

			$res = DBOld::query($sql, "Cannot check transaction");
			return DBOld::num_rows($res);
		}
	}