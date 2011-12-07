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
	 ***********************************************************************

	Controler part of database table pager with column sort.
	To display actual html object call DB_Pager::display($name) inside
	any form.

	 */
	class DB_Pager
	{
		/**
		 * @static
		 *
		 * @param $name
		 * @param $sql
		 * @param $coldef
		 * @param null $table
		 * @param null $key
		 * @param int $page_len
		 * @param null $sort
		 *
		 * @return DB_Pager
		 */
		static function &new_db_pager($name, $sql, $coldef, $table = null, $key = null, $page_len = 0, $sort = null) {
			if (isset($_SESSION[$name])
			 && ($_SERVER['REQUEST_METHOD'] == 'GET' || $_SESSION[$name]->sql != $sql)
			) {
				unset($_SESSION[$name]); // kill pager if sql has changed
			}
			if (!isset($_SESSION[$name])) {
				$_SESSION[$name] = new static($sql, $name, $table, $page_len);
				$_SESSION[$name]->main_tbl = $table;
				$_SESSION[$name]->key = $key;
				$_SESSION[$name]->set_sql($sql);
				$_SESSION[$name]->set_columns($coldef);
				$_SESSION[$name]->sort_table($sort);
			}
			return $_SESSION[$name];
		}

		/**
		 * @static
		 *
		 * @param bool $table
		 * @param bool $feild
		 * @param bool $where
		 *
		 * @return int
		 */
		static function countFilter($table = false, $feild = false, $where = false) {
			if ($table && $where && $feild) {
				$sql = "SELECT * FROM " . $table . " WHERE " . $feild . " LIKE " . DB::escape($where) . " LIMIT 1";
				$result = DB::query($sql, 'Couldnt do shit');
				return DB::num_rows($result);
			}
		}

		static function link($link_text, $url, $icon = false) {
			if (User::graphic_links() && $icon) {
				$link_text = set_icon($icon, $link_text);
			}
			$href = PATH_TO_ROOT . $url;
			$href = (Input::request('frame')) ? "javascript:window.parent.location='$href'"
			 : PATH_TO_ROOT . $url;
			return "<a href=\"$href\" class='button' >" . $link_text . "</a>";
		}

		static function navi($name, $value, $enabled = true, $icon = false) {
			return "<button " . ($enabled ? '' : 'disabled')
			 . " class=\"navibutton\" type=\"submit\""
			 . " name=\"$name\" id=\"$name\" value=\"$value\">"
			 . ($icon ? "<img src='/themes/" . User::theme() . "/images/" . $icon . "'>" : '')
			 . "<span>$value</span></button>\n";
		}

		static function navi_cell($name, $value, $enabled = true, $align = 'left') {
			label_cell(static::navi($name, $value, $enabled), "class='$align'");
		}

		//
		// Sql paged table view. Call this function inside form.
		//
		static function display(&$pager) {
			$pager->select_records();
			Display::div_start("_{$pager->name}_span");
			$headers = array();
			foreach (
				$pager->columns as $num_col => $col
			) {
				// record status control column is displayed only when control checkbox is on
				if (isset($col['head']) && ($col['type'] != 'inactive' || get_post('show_inactive'))) {
					if (!isset($col['ord'])) {
						$headers[] = $col['head'];
					}
					else {
						$icon = (($col['ord'] == 'desc')
						 ? 'sort_desc.gif'
						 :
						 ($col['ord'] == 'asc' ? 'sort_asc.gif' : 'sort_none.gif'));
						$headers[] = static::navi(
							$pager->name . '_sort_' . $num_col,
							$col['head'], true, $icon
						);
					}
				}
			}
			/* show a table of records returned by the sql */
			start_table('tablestyle width'.$pager->width);
			table_header($headers);
			if ($pager->header_fun) { // if set header handler
				start_row("class='{$pager->header_class}'");
				$fun = $pager->header_fun;
				if (method_exists($pager, $fun)) {
					$h = $pager->$fun($pager);
				} elseif (function_exists($fun)) {
					$h = call_user_func($fun, $pager);
				}
				foreach (
					$h as $c
				) { // draw header columns
					$pars = isset($c[1]) ? $c[1] : '';
					label_cell($c[0], $pars);
				}
				end_row();
			}
			$cc = 0; //row colour counter
			foreach (
				$pager->data as $line_no => $row
			) {
				$marker = $pager->marker;
				if ($marker && call_user_func($marker, $row)) {
					start_row("class='$pager->marker_class'");
				} else {
					alt_table_row_color($cc);
				}
				foreach (
					$pager->columns as $k => $col
				) {
					$coltype = isset($col['type']) ? $col['type'] : '';
					$cell = isset($col['name']) ? $row[$col['name']] : '';
					if (isset($col['fun'])) { // use data input function if defined
						$fun = $col['fun'];
						if (method_exists($pager, $fun)) {
							$cell = $pager->$fun($row, $cell);
						} elseif (function_exists($fun)) {
							$cell = call_user_func($fun, $row, $cell);
						} else
						{
							$cell = '';
						}
					}
					switch ($coltype) { // format column
						case 'time':
							label_cell($cell, "width=40");
							break;
						case 'date':
							label_cell(Dates::sql2date($cell), "class='center' nowrap");
							break;
						case 'dstamp': // time stamp displayed as date
							label_cell(Dates::sql2date(substr($cell, 0, 10)), "class='center' nowrap");
							break;
						case 'tstamp': // time stamp - FIX user format
							label_cell(
								Dates::sql2date(substr($cell, 0, 10)) .
								 ' ' . substr($cell, 10), "class='center'"
							);
							break;
						case 'percent':
							percent_cell($cell);
							break;
						case 'amount':
							if ($cell == '') {
								label_cell('');
							} else {
								amount_cell($cell, false);
							}
							break;
						case 'qty':
							if ($cell == '') {
								label_cell('');
							} else {
								qty_cell($cell, false, isset($col['dec']) ? $col['dec'] : null);
							}
							break;
						case 'email':
							email_cell($cell, isset($col['align']) ? "class='" . $col['align'] . "'" : null);
							break;
						case 'rate':
							label_cell(Num::format($cell, User::exrate_dec()), "class='center'");
							break;
						case 'inactive':
							if (get_post('show_inactive')) {
								$pager->inactive_control_cell($row);
							}
							break;
						case 'id':
							if (isset($col['align'])) {
								label_cell($cell, " class='pagerclick' data-id='" . $row['id'] . "' class='" . $col['align'] . "'");
							} else {
								label_cell($cell, " class='pagerclick' data-id='" . $row['id'] . "'");
							}
							break;
						default:
							//		 case 'text':
							if (isset($col['align'])) {
								label_cell($cell, "class='" . $col['align'] . "'");
							} else {
								label_cell($cell);
							}
						case 'skip': // column not displayed
					}
				}
				end_row();
			}
			//end of while loop
			if ($pager->footer_fun) { // if set footer handler
				start_row("class='{$pager->footer_class}'");
				$fun = $pager->footer_fun;
				if (method_exists($pager, $fun)) {
					$h = $pager->$fun($pager);
				} elseif (function_exists($fun)) {
					$h = call_user_func($fun, $pager);
				}
				foreach (
					$h as $c
				) { // draw footer columns
					$pars = isset($c[1]) ? $c[1] : '';
					label_cell($c[0], $pars);
				}
				end_row();
			}
			start_row("class='navibar'");
			$colspan = count($pager->columns);
			$inact = $pager->inactive_ctrl == true
			 ? ' ' . checkbox(null, 'show_inactive', null, true) . _("Show also Inactive") : '';
			if ($pager->rec_count) {
				echo "<td colspan=$colspan class='navibar' style='border:none;padding:3px;'>";
				echo "<div style='float:right;'>";
				$but_pref = $pager->name . '_page_';
				start_table();
				start_row();
				if (@$pager->inactive_ctrl) {
					submit('Update', _('Update'), true, '', null);
				} // inactive update
				echo static::navi_cell($but_pref . 'first', _('First'), $pager->first_page, 'right');
				echo static::navi_cell($but_pref . 'prev', _('Prev'), $pager->prev_page, 'right');
				echo static::navi_cell($but_pref . 'next', _('Next'), $pager->next_page, 'right');
				echo static::navi_cell($but_pref . 'last', _('Last'), $pager->last_page, 'right');
				end_row();
				end_table();
				echo "</div>";
				$from = ($pager->curr_page - 1) * $pager->page_len + 1;
				$to = $from + $pager->page_len - 1;
				if ($to > $pager->rec_count) {
					$to = $pager->rec_count;
				}
				$all = $pager->rec_count;
				HTML::span(true, "Records $to-$from of $all");
				echo $inact;
				echo "</td>";
			} else {
				label_cell(_('No records') . $inact, "colspan=$colspan class='navibar'");
			}
			end_row();
			end_table();
			if (isset($pager->marker_txt)) {
				Errors::warning($pager->marker_txt, 0, 1, "class='$pager->notice_class'");
			}
			Display::div_end();
			return true;
		}

		/**
		 * @var
		 */
		public $sql;
		/**
		 * @var
		 */
		public $name;
		/**
		 * @var
		 * column definitions (head, type, order)
		 */
		public $columns;
		/**
		 * @var
		 * marker check function
		 */
		public $marker;
		/**
		 * @var
		 */
		public $marker_txt;
		/**
		 * @var
		 */
		public $marker_class;
		/**
		 * @var
		 */
		public $notice_class;
		/**
		 * @var string
		 * table width (default '95%')
		 */
		public $width;
		/**
		 * @var
		 * additional row between title and body
		 */
		public $header_fun;
		/**
		 * @var
		 */
		public $header_class;
		/**
		 * @var
		 */
		public $footer_fun;
		/**
		 * @var
		 */
		public $footer_class;
		/**
		 * @var array
		 */
		public $data = array();
		/**
		 * @var
		 */
		public $curr_page;
		/**
		 * @var
		 */
		public $max_page;
		/**
		 * @var
		 */
		public $last_page;
		/**
		 * @var
		 */
		public $prev_page;
		/**
		 * @var
		 */
		public $next_page;
		/**
		 * @var
		 */
		public $first_page;
		/**
		 *
		 * @var #M#C\User.query_size|int|?
		 */
		public $page_len;
		/**
		 * @var
		 */
		public $rec_count;
		/**
		 * @var
		 */
		public $select;
		/**
		 * @var
		 */
		public $where;
		/**
		 * @var
		 */
		public $from;
		/**
		 * @var
		 */
		public $group;
		/**
		 * @var
		 */
		public $order;
		/**
		 * @var
		 */
		public $extra_where;
		/**
		 * @var bool
		 */
		public $ready = false;
		/**
		 * @var bool
		 * this var is false after change in sql before first
		 *
		and before first query.
		 */
		public $inactive_ctrl = false;
		/**
		 * @var
		 */
		public $main_tbl;
		/**
		 * @var
		 * table and key field name for inactive ctrl and edit/delete links
		 * key field name
		 * db_pager constructor
		 * accepts $sql like 'SELECT ...[FROM ...][WHERE ...][GROUP ...][ORDER ...]'
		 * $name is base name for pager controls
		 */
		public $key;

		/**
		 * @param $sql
		 * @param $name
		 * @param null $table
		 * @param int $page_len
		 */
		public function __construct($sql, $name, $table = null, $page_len = 0) {
			$this->width = "80%";
			if ($page_len == 0) {
				$page_len = User::query_size();
			}
			$this->name = $name;
			$this->page_len = $page_len;
			$this->set_sql($sql);
		}

		/**
		 * @param null $page
		 *
		 * @return bool
		 *
		 * Set query result page
		 *
		 */
		public function change_page($page = null) {
			$this->set_page($page);
			$this->query();
			return true;
		}

		/**
		 * @param $row
		 *
		 * @return string
		 *
		 * Helper for display inactive control cells
		 *
		 */
		public function inactive_control_cell(&$row) {
			if ($this->inactive_ctrl) {
				//	return inactive_control_cell($row[$this->inactive_ctrl['key']],
				// $row['inactive'], $this->inactive_ctrl['table'],
				// $this->inactive_ctrl['key']);
				$Ajax = Ajax::i();
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
					echo '<td class="center">' . checkbox(null, $name, $value, true, '', "class='center'")
					 . hidden("LInact[$id]", $value, false) . '</td>';
				}
			} else
			{
				return '';
			}
		}

		/**
		 * @return bool
		 *
		 * Query database
		 *
		 */
		public function query() {
			$Ajax = Ajax::i();
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
				$dbfeild_names = array_keys($this->data[0]);
				$cnt = min(count($dbfeild_names), count($this->columns));
				for ($c = $i = 0; $c < $cnt; $c++) {
					if (!(isset($this->columns[$c]['insert']) && $this->columns[$c]['insert'])) {
						//	if (!@($this->columns[$c]['type']=='skip'))
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

		/**
		 * @param $name - base name for pager controls and $_SESSION object name
		 * -----------------------------------------------------------------------------
		 * Creates new db_pager $_SESSION object on first page call.
		 * Retrieves from $_SESSION var on subsequent $_POST calls
		 *
		 * $sql	- base sql for data inquiry. Order of fields implies
		 * pager columns order.
		 * $coldef - array of column definitions. Example definitions
		 * Column with title 'User name' and default text format:
		 * 'User name'
		 * Skipped field from sql query. Data for the field is not displayed:
		 * 'dummy' => 'skip'
		 * Column without title, data retrieved form row data with function func():
		 * array('fun'=>'func')
		 * Inserted column with title 'Some', formated with function rowfun().
		 * formated as date:
		 * 'Some' => array('type'=>'date, 'insert'=>true, 'fun'=>'rowfun')
		 * Column with name 'Another', formatted as date,
		 * sortable with ascending start order (available orders: asc,desc, '').
		 * 'Another' => array('type'=>'date', 'ord'=>'asc')
		 *
		 * All available column format types you will find in db_pager_view.php file.
		 * If query result has more fields than count($coldef), rest of data is ignored
		 * during display, but can be used in format handlers for 'spec' and 'insert'
		 * type columns.
		 *
		 * Force pager initialization.
		 *
		 */
		public function refresh_pager($name) {
			if (isset($_SESSION[$name])) {
				$_SESSION[$name]->ready = false;
			}
		}

		/**
		 *
		 * Set current page in response to user control.
		 *
		 */
		public function select_records() {
			$Ajax = Ajax::i();
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

		/**
		 * @param $flds
		 *
		 * Set column definitions
		 * $flds: array( fldname1, fldname2=>type,...)
		 */
		public function set_columns($flds) {
			$this->columns = array();
			if (!is_array($flds)) {
				$flds = array($flds);
			}
			foreach ($flds as $colnum => $coldef) {
				if (is_string($colnum)) { // 'colname'=>params
					$h = $colnum;
					$c = $coldef;
				} else { // n=>params
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

		/**
		 * @param $func
		 * @param string $footercl
		 * Set handler to
		 * lay additional row between pager body and navibar.
		 * Return array of column contents.
		 *
		 */
		public function set_footer($func, $footercl = 'inquirybg') {
			$this->footer_fun = $func;
			$this->footer_class = $footercl;
		}

		/**
		 * @param $to
		 *
		 * Calculates page numbers for html controls.
		 *
		 */
		public function set_page($to) {
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

		/**
		 * @param $sql
		 *
		 * Parse base sql select query.
		 */
		public function set_sql($sql) {
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

		/**
		 * @param null $where
		 *
		 * @return mixed
		 * Set additional constraint on record set
		 */
		public function set_where($where = null) {
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

		/**
		 * @param $col
		 *
		 * @return bool
		 *
		 * Change sort column direction
		 * in order asc->desc->none->asc
		 */
		public function sort_table($col) {
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

		/**
		 * @param $func
		 * @param string $headercl
		 *
		 * Set handler to display additional row between titles and pager body.
		 * Return array of column contents.
		 *
		 */
		public function set_header($func, $headercl = 'inquirybg') {
			$this->header_fun = $func;
			$this->header_class = $headercl;
		}

		/**
		 * @param $table
		 * @param $key
		 *
		 * Setter for table editors with inactive cell control.
		 *
		 */
		public function set_inactive_ctrl($table, $key) {
			$this->inactive_ctrl = array(
				'table' => $table,
				'key' => $key);
		}

		/***
		 * @param $func
		 * @param string $notice
		 * @param string $markercl
		 * @param string $msgclass
		 *
		 * Set check function to mark some rows.
		 */
		public function set_marker($func, $notice = '', $markercl = 'overduebg', $msgclass = 'overduefg') {
			$this->marker = $func;
			$this->marker_txt = $notice;
			$this->marker_class = $markercl;
			$this->notice_class = $msgclass;
		}

		/**
		 * @return bool
		 * Initialization after changing record set
		 */
		protected function _init() {
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
							//for all intially ordered db_pager columns.");
						}
					}
				}
				$this->set_page(1);
				$this->ready = true;
			}
			return true;
		}

		/**
		 * @param bool $count
		 *
		 * @return string
		 *
		 * Generate db query from base sql
		 * $count==false - for current page data retrieval
		 * $count==true	- for total records count
		 *
		 */
		protected function _sql_gen($count = false) {
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
	}