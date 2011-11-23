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
	//
	//	Controler part of database table pager with column sort.
	//	To display actual html object call display_db_pager($name) inside
	//  any form.
	//
	//	View definition you will find in the following file:
	//require_once("$path_to_root/includes/ChromePHP.php");
	//CP::useFile('/tmp/chromelogs'.'/chromelogs');
	//require_once('FirePHPCore/fb.php');
	//require_once($path_to_root . "/includes/firephp.php");
	include_once($_SERVER['DOCUMENT_ROOT'] . "/includes/ui/db_pager_view.php");
	class db_pager
	{
		public $sql;
		public $name;
		public $columns; // column definitions (head, type, order)
		public $marker; // marker check function
		public $marker_txt;
		public $marker_class;
		public $notice_class;
		public $width; // table width (default '95%')
		public $header_fun; // additional row between title and body
		public $header_class;
		public $footer_fun;
		public $footer_class;
		public $data = array();
		public $curr_page,
		$max_page,
		$last_page,
		$prev_page,
		$next_page,
		$first_page;
		public $page_len,
		$rec_count;
		public $select,
		$where,
		$from,
		$group,
		$order;
		public $extra_where;
		public $ready = false; // this var is false after change in sql before first
		// and before first query.
		public $inactive_ctrl = false;
		public $main_tbl; // table and key field name for inactive ctrl and edit/delete links
		public $key; // key field name
		//  db_pager constructor
		//  accepts $sql like 'SELECT ...[FROM ...][WHERE ...][GROUP ...][ORDER ...]'
		//	$name is base name for pager controls
		function db_pager($sql, $name, $table = null, $page_len = 0)
		{
			$this->width = "80%";
			if ($page_len == 0) {
				$page_len = User::query_size();
			}
			$this->name = $name;
			$this->page_len = $page_len;
			$this->set_sql($sql);
		}

		//
		//	Parse base sql select query.
		//
		function set_sql($sql)
		{
			if ($sql != $this->sql) {
				$this->sql = $sql;
				$this->ready = false;
				$parts = preg_split('/\sORDER\s*BY\s/si', $sql, 2);
				if (count($parts) == 2) {
					$sql = $parts[0];
					$this->order = $parts[1];
				}
				$parts = preg_split('/\sGROUP\s*BY\s/si', $sql, 2);
				if (count($parts) == 2) {
					$sql = $parts[0];
					$this->group = $parts[1];
				}
				$parts = preg_split('/\sWHERE\s/si', $sql, 2);
				if (count($parts) == 2) {
					$sql = $parts[0];
					$this->where = $parts[1];
				}
				$parts = preg_split('/\sFROM\s/si', $sql, 2);
				if (count($parts) == 2) {
					$sql = $parts[0];
					$this->from = $parts[1];
				}
				$this->select = $sql;
			}
		}

		//
		//	Set additional constraint on record set
		//
		function set_where($where = null)
		{
			if ($where) {
				if (!is_array($where)) {
					$where = array($where);
				}
				if (count($where) == count($this->extra_where)
				 && !count(array_diff($this->extra_where, $where))
				) {
					return;
				}
			}
			$this->extra_where = $where;
			$this->ready = false;
		}

		//
		//	Set query result page
		//
		function change_page($page = null)
		{
			$this->set_page($page);
			$this->query();
			return true;
		}

		//
		//	Change sort column direction
		//	in order asc->desc->none->asc
		//
		function sort_table($col)
		{
			if (is_null($col)) {
				return false;
			}
			$ord = (!isset($this->columns[$col]['ord'])) ? '' : $this->columns[$col]['ord'];
			$ord = ($ord == '') ? 'asc' : (($ord == 'asc') ? 'desc' : '');
			$this->columns[$col]['ord'] = $ord;
			$this->set_page(1);
			$this->query();
			return true;
		}

		//
		// Query database
		//
		function query()
		{
			$Ajax = Ajax::instance();
			$Ajax->activate("_{$this->name}_span");
			$this->data = array();
			if (!$this->_init()) {
				return false;
			}
			if ($this->rec_count == 0) {
				return true;
			}
			$sql = $this->_sql_gen(false);
			$result = DB::query($sql, 'Error browsing database: ' . $sql);
			if ($result) {
				// setting field names for subsequent queries
				$c = 0;
				// add result field names to column defs for
				// col value retrieve and sort purposes
				while ($row = DB::fetch_assoc($result)) {
													$this->data[] = $row;
												}
				$dbfeild_names= array_keys($this->data[0]);
				$cnt = min(count($dbfeild_names), count($this->columns));


				for ($c = $i = 0; $c < $cnt; $c++) {
					if (!(isset($this->columns[$c]['insert']) && $this->columns[$c]['insert'])) {
						//					if (!@($this->columns[$c]['type']=='skip'))
						$this->columns[$c]['name'] = $dbfeild_names[$c];
						if (!@($this->columns[$c]['type'] == 'insert')) {
							$i++;
						}
					}
				}

			} else
			{
				return false;
			}
			return true;
		}

		//
		//	Calculates page numbers for html controls.
		//
		function set_page($to)
		{
			switch ($to) {
			case 'next':
				$page = $this->curr_page + 1;
				break;
			case 'prev':
				$page = $this->curr_page - 1;
				break;
			case 'last':
				$page = $this->last_page;
				break;
			default:
				if (is_numeric($to)) {
					$page = $to;
					break;
				}
			case 'first':
				$page = 1;
				break;
			}
			if ($page < 1) {
				$page = 1;
			}
			$max = $this->max_page;
			if ($page > $max) {
				$page = $max;
			}
			$this->curr_page = $page;
			$this->next_page = ($page < $max) ? $page + 1 : null;
			$this->prev_page = ($page > 1) ? ($page - 1) : null;
			$this->last_page = ($page < $max) ? $max : null;
			$this->first_page = ($page != 1) ? 1 : null;
		}

		//
		//	Set column definitions
		//  $flds: array( fldname1, fldname2=>type,...)
		function set_columns($flds)
		{
			$this->columns = array();
			if (!is_array($flds)) {
				$flds = array($flds);
			}
			foreach ($flds as $colnum => $coldef) {
				if (is_string($colnum)) { // 'colname'=>params
					$h = $colnum;
					$c = $coldef;
				} else { //  n=>params
					if (is_array($coldef)) {
						$h = '';
						$c = $coldef;
					} else {
						$h = $coldef;
						$c = 'text';
					}
				}
				if (is_string($c)) // params is simple column type
				{
					$c = array('type' => $c);
				}
				if (!isset($c['type'])) {
					$c['type'] = 'text';
				}
				switch ($c['type']) {
				case 'inactive':
					$this->inactive_ctrl = true;
				case 'insert':
				default:
					$c['head'] = $h;
					break;
				case 'skip': // skip the column (no header)
					unset($c['head']);
					break;
				}
				$this->columns[] = $c;
			}
		}

		//
		// Generate db query from base sql
		// $count==false - for current page data retrieval
		// $count==true  - for total records count
		//
		function _sql_gen($count = false)
		{
			$select = $this->select;
			$from = $this->from;
			$where = $this->where;
			$group = $this->group;
			$order = $this->order;
			if (count($this->extra_where)) {
				$where .= ($where == '' ? '' : ' AND ')
				 . implode($this->extra_where, ' AND ');
			}
			if ($where) {
				$where = " WHERE ($where)";
			}
			if ($count) {
				$group = $group == '' ? "*" : "DISTINCT $group";
				return "SELECT COUNT($group) FROM $from $where";
			}
			$sql = "$select FROM $from $where";
			if ($group) {
				$sql .= " GROUP BY $group";
			}
			$ord = array();
			foreach ($this->columns as $col) {
				if (isset($col['ord'])) {
					if ($col['ord'] != '' && isset($col['name'])) {
						$ord[] = $col['name'] . ' ' . $col['ord'];
					}
				}
			}
			if (count($ord)) {
				$sql .= " ORDER BY " . implode($ord, ',');
			} else {
				if ($order) {
					$sql .= " ORDER BY $order";
				} // original base query order
			}
			$page_len = $this->page_len;
			$offset = ($this->curr_page - 1) * $page_len;
			$sql .= " LIMIT $offset, $page_len";
			return $sql;
		}

		//
		//	Initialization after changing record set
		//
		function _init()
		{
			if ($this->ready == false) {
				$sql = $this->_sql_gen(true);
				$result = DB::query($sql, 'Error reading record set');
				if ($result == false) {
					return false;
				}
				$row = DB::fetch_row($result);
				$this->rec_count = $row[0];
				$this->max_page = $this->page_len ?
				 ceil($this->rec_count / $this->page_len) : 0;
				if (Config::get('debug')) { // FIX - need column name parsing, but for now:
					// check if field names are set explicite in col def
					// for all initially ordered columns
					foreach ($this->columns as $col) {
						if (isset($col['ord']) && $col['ord'] != ''
						 && !isset($col['name'])
						) {
							//Errors::warning("Result field names must be set
							//		for all intially ordered db_pager columns.");
						}
					}
				}
				$this->set_page(1);
				$this->ready = true;
			}
			return true;
		}

		//
		//	Set current page in response to user control.
		//
		function select_records()
		{
			$Ajax = Ajax::instance();
			$page = find_submit($this->name . '_page_', false);
			$sort = find_submit($this->name . '_sort_', true);
			if ($page) {
				$this->change_page($page);
				if ($page == 'next' && !$this->next_page
				 || $page == 'last' && !$this->last_page
				) {
					JS::set_focus($this->name . '_page_prev');
				}
				if ($page == 'prev' && !$this->prev_page
				 || $page == 'first' && !$this->first_page
				) {
					JS::set_focus($this->name . '_page_next');
				}
			} elseif ($sort != -1) {
				$this->sort_table($sort);
			} else
			{
				$this->query();
			}
		}

		//
		//	Set check function to mark some rows.
		//
		function set_marker($func, $notice = '', $markercl = 'overduebg', $msgclass = 'overduefg')
		{
			$this->marker = $func;
			$this->marker_txt = $notice;
			$this->marker_class = $markercl;
			$this->notice_class = $msgclass;
		}

		//
		//	Set handler to display additional row between titles and pager body.
		//	Return array of column contents.
		//
		function set_header($func, $headercl = 'inquirybg')
		{
			$this->header_fun = $func;
			$this->header_class = $headercl;
		}

		//
		//	Set handler to display additional row between pager body and navibar.
		//	Return array of column contents.
		//
		function set_footer($func, $footercl = 'inquirybg')
		{
			$this->footer_fun = $func;
			$this->footer_class = $footercl;
		}

		//
		//	Setter for table editors with inactive cell control.
		//
		function set_inactive_ctrl($table, $key)
		{
			$this->inactive_ctrl = array('table' => $table,
				'key' => $key);
		}

		//
		//	Helper for display inactive control cells
		//
		function inactive_control_cell(&$row)
		{
			if ($this->inactive_ctrl) {
				//			return inactive_control_cell($row[$this->inactive_ctrl['key']],
				//				 $row['inactive'], $this->inactive_ctrl['table'],
				//				 $this->inactive_ctrl['key']);
				$Ajax = Ajax::instance();
				$key = $this->key ?
				 $this->key : $this->columns[0]['name']; // TODO - support for complex keys
				$id = $row[$key];
				$table = $this->main_tbl;
				$name = "Inactive" . $id;
				$value = $row['inactive'] ? 1 : 0;
				if (check_value('show_inactive')) {
					if (isset($_POST['LInact'][$id])
					 && (get_post('_Inactive' . $id . '_update')
						|| get_post('Update'))
					 && (check_value('Inactive' . $id) != $value)
					) {
						DB::update_record_status($id, !$value, $table, $key);
						$value = !$value;
					}
					echo '<td align="center">' . checkbox(null, $name, $value, true, '', "align='center'")
					 . hidden("LInact[$id]", $value, false) . '</td>';
				}
			} else
			{
				return '';
			}
		}

		static function &new_db_pager($name, $sql, $coldef, $table = null, $key = null, $page_len = 0, $sort = null)
		{
			if (isset($_SESSION[$name])
			 && ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SESSION[$name]->sql != $sql)
			) {
				unset($_SESSION[$name]); // kill pager if sql has changed
			}
			if (!isset($_SESSION[$name])) {
				$_SESSION[$name] = new db_pager($sql, $name, $table, $page_len);
				$_SESSION[$name]->main_tbl = $table;
				$_SESSION[$name]->key = $key;
				$_SESSION[$name]->set_sql($sql);
				$_SESSION[$name]->set_columns($coldef);
				$_SESSION[$name]->sort_table($sort);
			}
			return $_SESSION[$name];
		}

		static function countFilter($table = false, $feild = false, $where = false)
		{
			if ($table && $where && $feild) {
				$sql = "SELECT * FROM " . $table . " WHERE " . $feild . " LIKE " . DB::escape($where) . " LIMIT 1";
				$result = DB::query($sql, 'Couldnt do shit');
				return DB::num_rows($result);
			}
		}

		//-----------------------------------------------------------------------------
		//	Creates new db_pager $_SESSION object on first page call.
		//  Retrieves from $_SESSION var on subsequent $_POST calls
		//
		//  $name - base name for pager controls and $_SESSION object name
		//  $sql  - base sql for data inquiry. Order of fields implies
		//		pager columns order.
		//	$coldef - array of column definitions. Example definitions
		//		Column with title 'User name' and default text format:
		//				'User name'
		//		Skipped field from sql query. Data for the field is not displayed:
		//				'dummy' => 'skip'
		//		Column without title, data retrieved form row data with function func():
		//	 			array('fun'=>'func')
		// 		Inserted column with title 'Some', formated with function rowfun().
		//  	formated as date:
		//				'Some' => array('type'=>'date, 'insert'=>true, 'fun'=>'rowfun')
		// 		Column with name 'Another', formatted as date,
		// sortable with ascending start order (available orders: asc,desc, '').
		//				'Another' => array('type'=>'date', 'ord'=>'asc')
		//
		//	All available column format types you will find in db_pager_view.php file.
		//		If query result has more fields than count($coldef), rest of data is ignored
		//  during display, but can be used in format handlers for 'spec' and 'insert'
		//	type columns.
		//
		//	Force pager initialization.
		//
		function refresh_pager($name)
		{
			if (isset($_SESSION[$name])) {
				$_SESSION[$name]->ready = false;
			}
		}
	}
