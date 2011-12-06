<?php
	/**
	 * Created by JetBrains PhpStorm.
	 * User: Complex
	 * Date: 6/11/11
	 * Time: 1:37 AM
	 * To change this template use File | Settings | File Templates.
	 */
	$GLOBALS['ajax_divs'] = array();
	class Display
	{
		public static function heading($msg) {
			echo "<div class='center'><span class='headingtext'>$msg</span></div>\n";
		}

		public static function note($msg, $br = 0, $br2 = 0, $extra = "") {
			for ($i = 0; $i < $br; $i++) {
				echo "<br>";
			}
			if ($extra != "") {
				echo "<div class='center'><span $extra>$msg</span></div>\n";
			} else {
				echo "<div class='center'>$msg</div>\n";
			}
			for ($i = 0; $i < $br2; $i++) {
				echo "<br>";
			}
		}

		public static function item_heading($stock_id) {
			if ($stock_id != "") {
				$result = DB::query("SELECT description, units FROM stock_master WHERE stock_id=" . DB::escape($stock_id));
				$myrow = DB::fetch_row($result);
				static::heading("$stock_id - $myrow[0]");
				$units = $myrow[1];
				static::heading(_("in units of : ") . $units);
			}
		}

		public static function backtrace($cond = true, $msg = '') {
			if ($cond) {
				if ($msg) {
					$str = "<div class='center'><span class='headingtext'>$msg</span></div>\n";
				} else {
					$str = '';
				}
				$str .= '<table >';
				$trace = debug_backtrace();
				foreach ($trace as $trn => $tr) {
					if (!$trn) {
						continue;
					}
					$str .= '<tr><td>';
					$str .= $tr['file'] . ':' . $tr['line'] . ': ';
					$str .= '</td><td>';
					if (isset($tr['type'])) {
						if ($tr['type'] == '::') {
							$str .= $tr['class'] . '::';
						} else {
							if ($tr['type'] == '->') {
								$str .= '(' . $tr['class'] . ' Object)' . '->';
							}
						}
					}
					foreach ($tr['args'] as $n => $a) {
						if (is_object($tr['args'][$n])) {
							$tr['args'][$n] = "(" . get_class($tr['args'][$n]) . " Object)";
						}
						if (is_array($tr['args'][$n])) {
							$tr['args'][$n] = "(Array[" . count($tr['args'][$n]) . "])";
						} else {
							$tr['args'][$n] = "'" . $tr['args'][$n] . "'";
						}
					}
					$str .= $tr['function'] . '(' . implode(',', $tr['args']) . ')</td>';
					$str .= '</tr>';
				}
				$str .= '</table>';
				Errors::error($str);
			}
		}

		public static function is_voided($type, $id, $label) {
			$void_entry = Voiding::get($type, $id);
			if ($void_entry == null) {
				return false;
			}
			start_table('tablestyle width50');
			echo "<tr><td class=center><span class='red'>$label</span><br>";
			echo "<span class='red'>" . _("Date Voided:") . " " . Dates::sql2date($void_entry["date_"]) . "</span><br>";
			if (strlen($void_entry["memo_"]) > 0) {
				echo "<div class='center'><span class='red'>" . _("Memo:") . " " . $void_entry["memo_"] . "</span></div><br>";
			}
			echo "</td></tr>";
			end_table(1);
			return true;
		}

		public static function meta_forward($forward_to, $params = "") {
			$Ajax = Ajax::i();
			echo "<meta http-equiv='Refresh' content='0; url=$forward_to?$params'>\n";
			echo "<div class='center'><br>" . _("You should automatically be forwarded.");
			echo " " . _("If this does not happen") . " <a href='$forward_to?$params'>" . _("click here") . "</a> " . _("to continue") . ".<br><br></div>\n";
			if ($params != '') {
				$params = '?' . $params;
			}
			$Ajax->redirect($forward_to . $params);
			exit;
		}

		// Find and replace hotkey marker.
		// if $clean == true marker is removed and clean label is returned
		// (for use in wiki help system), otherwise result is array of label
		// with underlined hotkey letter and access property string.
		//
		public static function access_string($label, $clean = false) {
			$access = '';
			$slices = array();
			if (preg_match('/(.*)&([a-zA-Z0-9])(.*)/', $label, $slices)) {
				$label = $clean ? $slices[1] . $slices[2] . $slices[3] : $slices[1] . '<u>' . $slices[2] . '</u>' . $slices[3];
				$access = " accesskey='" . strtoupper($slices[2]) . "'";
			}
			$label = str_replace('&&', '&', $label);
			return $clean ? $label : array($label, $access);
		}

		public static function link_back($center = true, $no_menu = true) {
			if ($center) {
				echo "<div class='center margin20'>";
			}
			echo "<td class=center><a class='button' href='javascript:(window.history.length <= 1) ? window.close() : window.history.go(-1);'>" . ($no_menu ?
			 _("Close") : _("Back")) . "</a></td>\n";
			if ($center) {
				echo "</div>";
			}
		}

		public static function link_no_params($target, $label, $center = true, $button = false) {
			$id = JS::default_focus();
			$pars = Display::access_string($label);
			if ($target == '') {
				$target = $_SERVER['PHP_SELF'];
			}
			if ($center) {
				echo "<br><div class='center'>";
			}
			if ($button) {
				$pars[1] .= " class='button'";
			}
			echo "<a href='$target' id='$id' $pars[1] >$pars[0]</a>\n";
			if ($center) {
				echo "</div>";
			}
		}

		public static function link_no_params_td($target, $label) {
			echo "<td>";
			Display::link_no_params($target, $label);
			echo "</td>\n";
		}

		public static function viewer_link($label, $url = '', $class = '', $id = '', $icon = null) {
			if ($url != '') {
				$class .= " openWindow";
			}
			if ($class != '') {
				$class = " class='$class'";
			}
			if ($id != '') {
				$class = " id='$id'";
			}
			if ($url != "") {
				$pars = Display::access_string($label);
				if (User::graphic_links() && $icon) {
					$pars[0] = set_icon($icon, $pars[0]);
				}
				$preview_str = "<a target='_blank' $class $id href='" . PATH_TO_ROOT . "/$url' $pars[1]>$pars[0]</a>";
			} else {
				$preview_str = $label;
			}
			return $preview_str;
		}

		public static function menu_link($url, $label, $id = null) {
			$id = JS::default_focus($id);
			$pars = Display::access_string($label);
			return "<a href='$url' class='menu_option' id='$id' $pars[1]>$pars[0]</a>";
		}

		public static function menu_button($url, $label, $id = null) {
			$id = JS::default_focus($id);
			$pars = Display::access_string($label);
			return "<a href='$url' class='button' id='$id' $pars[1]>$pars[0]</a>";
		}

		public static function submenu_option($title, $url, $id = null) {
			Display::note(Display::menu_button(PATH_TO_ROOT . $url, $title, $id), 0, 1);
		}

		public static function submenu_button($title, $url, $id = null) {
			Display::note(Display::menu_button(PATH_TO_ROOT . $url, $title, $id), 0, 1);
		}

		public static function submenu_view($title, $type, $number, $id = null) {
			Display::note(GL_UI::trans_view($type, $number, $title, false, 'menu_option button', $id), 0, 1, false);
		}

		public static function submenu_print($title, $type, $number, $id = null, $email = 0, $extra = 0) {
			Display::note(Reporting::print_doc_link($number, $title, true, $type, false, 'button printlink', $id, $email, $extra), 0, 1);
		}

		public static function link_params($target, $label, $link_params = '', $center = true, $params = '') {
			$id = JS::default_focus();
			$pars = Display::access_string($label);
			if ($target == '') {
				$target = $_SERVER['PHP_SELF'];
			}
			if ($center) {
				echo "<br><div class='center'>";
			}
			echo "<a id='$id' href='$target?$link_params' $params $pars[1] >$pars[0]</a>\n";
			if ($center) {
				echo "</div>";
			}
		}

		public static function link_button($target, $label, $link_params = '', $center = true, $params = '') {
			$id = JS::default_focus();
			$pars = Display::access_string($label);
			if ($target == '') {
				$target = $_SERVER['PHP_SELF'];
			}
			if ($center) {
				echo "<br><div class='center'>";
			}
			echo "<a id='$id' class='button' href='$target?$link_params' $params $pars[1] >$pars[0]</a>\n";
			if ($center) {
				echo "</div>";
			}
		}

		public static function link_params_td($target, $label, $link_params, $params = '') {
			echo "<td>";
			Display::link_params($target, $label, $link_params, false, $params);
			echo "</td>\n";
		}

		public static function link_params_separate($target, $label, $params, $center = false, $nobr = false) {
			$id = JS::default_focus();
			$pars = Display::access_string($label);
			if (!$nobr) {
				echo "<br>";
			}
			if ($center) {
				echo "<div class='center'>";
			}
			echo "<a target='_blank' id='$id' href='$target?$params' $pars[1]>$pars[0]</a>\n";
			if ($center) {
				echo "</div>";
			}
		}

		public static function link_params_separate_td($target, $label, $params) {
			echo "<td>";
			Display::link_params_separate($target, $label, $params);
			echo "</td>\n";
		}


		public static function br($num = 1) {
			for ($i = 0; $i < $num; $i++) {
				echo "<br>";
			}
		}

		public static function div_start($id = '', $trigger = null, $non_ajax = false) {
			global $ajax_divs;
			if ($non_ajax) { // div for non-ajax elements
				array_push($ajax_divs, array($id, null));
				echo "<div class='js_only hidden' " . ($id != '' ? "id='$id'" : '') . ">";
			} else { // ajax ready div
				array_push($ajax_divs, array($id, $trigger === null ? $id : $trigger));
				echo "<div " . ($id != '' ? "id='$id'" : '') . ">";
				ob_start();
			}
		}

		public static function div_end() {
			global $ajax_divs;
			$Ajax = Ajax::i();
			if (count($ajax_divs)) {
				$div = array_pop($ajax_divs);
				if ($div[1] !== null) {
					$Ajax->addUpdate($div[1], $div[0], ob_get_flush());
				}
				echo "</div>";
			}
		}

		/*
						 Bind editors for various selectors.
						 $type - type of editor
						 $input - name of related input field
						 $caller - optional function key code (available values F1-F12: 112-123,
							 true: default)
					 */
		//$Pagehelp = array();
		public static function set_editor($type, $input, $caller = true) {
			static $Editors = array();
			/* Table editor interfaces. Key is editor type
							 0 => url of editor page
							 1 => hotkey code
							 2 => context help
						 */
			if ($type === false && $input === false) {
				return $Editors;
			}
			$popup_editors = array(
				'customer' => array(
					'/sales/manage/customers.php?debtor_no=', 113, _("Customers")), 'branch' => array(
					'/sales/manage/customer_branches.php?SelectedBranch=', 114, _("Branches")), 'supplier' => array(
					'/purchases/manage/suppliers.php?supplier_id=', 113, _("Suppliers")), 'item' => array(
					'/inventory/manage/items.php?stock_id=', 115, _("Items")));
			$key = $caller === true ? $popup_editors[$type][1] : $caller;
			$Editors[$key] = array(PATH_TO_ROOT . $popup_editors[$type][0], $input);
			/*	$help = 'F' . ($key - 111) . ' - ';
									$help .= $popup_editors[$type][2];
									$Pagehelp[] = $help;*/
		}
	}
