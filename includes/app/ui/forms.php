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
	function start_form($multi = false, $action = "", $name = "") {
		if ($name != "") {
			$name = "name='$name'";
		}
		if ($action == "") {
			$action = $_SERVER['PHP_SELF'];
		}
		if ($multi) {
			echo "<form enctype='multipart/form-data' method='post' action='$action' $name>\n";
		}
		else {
			echo "<form method='post' action='$action' $name>\n";
		}
	}

	function end_form($breaks = 0) {
		if ($breaks) {
			Display::br($breaks);
		}
		echo "<input type=\"hidden\" name=\"_focus\" value=\"" . get_post('_focus') . "\">\n";
		echo "</form>\n";
	}

	/**
	 * Seek for _POST variable with $prefix.
	 * If var is found returns variable name with prefix stripped,
	 * and null or -1 otherwise.
	 *
	 * @param			$prefix
	 * @param bool $numeric
	 *
	 * @return int|null|string
	 */
	function find_submit($prefix, $numeric = true) {
		foreach ($_POST as $postkey => $postval) {
			if (strpos($postkey, $prefix) === 0) {
				$id = substr($postkey, strlen($prefix));
				return $numeric ? (int)$id : $id;
			}
		}
		return $numeric ? -1 : null;
	}

	function get_post($name, $dflt = '') {
		return ((!isset($_POST[$name]) || $_POST[$name] === '') ? $dflt : $_POST[$name]);
	}

	/**
	 *	Helper function.
	 *	Returns true if selector $name is subject to update.
	 *
	 * @param $name
	 *
	 * @return bool
	 */
	function list_updated($name) {
		return isset($_POST['_' . $name . '_update']) || isset($_POST['_' . $name . '_button']);
	}

	function hidden($name, $value = null, $echo = true) {
		if ($value === null) {
			$value = get_post($name);
		}
		$ret = "<input type=\"hidden\" id=\"$name\" name=\"$name\" value=\"$value\">";
		Ajax::i()->addUpdate($name, $name, $value);
		if ($echo) {
			echo $ret . "\n";
		}
		else {
			return $ret;
		}
	}

	/**
	 *
	 * Universal sql combo generator
	 * $sql must return selector values and selector texts in columns 0 & 1
	 * Options are merged with defaults.
	 *
	 * @param					$name
	 * @param					$selected_id
	 * @param					$sql
	 * @param					$valfield
	 * @param					$namefield
	 * @param null		 $options
	 *
	 * @return string
	 */
	function select_box($name, $selected_id = null, $sql, $valfield, $namefield, $options = null) {
		$opts = array( // default options
			'where' => array(), // additional constraints
			'order' => $namefield, // list sort order
			// special option parameters
			'spec_option' => false, // option text or false
			'spec_id' => 0, // option id
			'cache' => false, // option id
			// submit on select parameters
			'default' => '', // default value when $_POST is not set
			'multi' => false, // multiple select
			'select_submit' => false, //submit on select: true/false
			'async' => true, // select update via ajax (true) vs _page_body reload
			// search box parameters
			'sel_hint' => null, 'search_box' => false, // name or true/false
			'type' => 0, // type of extended selector:
			// 0 - with (optional) visible search box, search by id
			// 1 - with hidden search box, search by option text
			// 2 - TODO reverse: box with hidden selector available via enter; this
			// would be convenient for optional ad hoc adding of new item
			'search_submit' => true, //search submit button: true/false
			'size' => 8, // size and max of box tag
			'max' => 50, 'height' => false, // number of lines in select box
			'cells' => false, // combo displayed as 2 <td></td> cells
			'search' => array(), // sql field names to search
			'format' => null, // format functions for regular options
			'disabled' => false, 'box_hint' => null, // box/selectors hints; null = std see below
			'category' => false, // category column name or false
			'show_inactive' => false, // show inactive records.
			'editable' => false // false, or length of editable entry field
		);
		// ------ merge options with defaults ----------
		if ($options != null) {
			$opts = array_merge($opts, $options);
		}
		if (!is_array($opts['where'])) {
			$opts['where'] = array($opts['where']);
		}
		$search_box = $opts['search_box'] === true ? '_' . $name . '_edit' : $opts['search_box'];
		// select content filtered by search field:
		$search_submit = $opts['search_submit'] === true ? '_' . $name . '_button' : $opts['search_submit'];
		// select set by select content field
		$search_button = $opts['editable'] ? '_' . $name . '_button' : ($search_box ? $search_submit : false);
		$select_submit = $opts['select_submit'];
		$spec_id = $opts['spec_id'];
		$spec_option = $opts['spec_option'];
		$by_id = ($opts['type'] == 0);
		$class = $by_id ? 'combo' : 'combo2';
		$disabled = $opts['disabled'] ? "disabled" : '';
		$multi = $opts['multi'];
		if (!count($opts['search'])) {
			$opts['search'] = array($by_id ? $valfield : $namefield);
		}
		if ($opts['sel_hint'] === null) {
			$opts['sel_hint'] = $by_id || $search_box == false ? '' : _('Press Space tab for search pattern entry');
		}
		if ($opts['box_hint'] === null) {
			$opts['box_hint'] = $search_box && $search_submit != false ?
			 ($by_id ? _('Enter code fragment to search or * for all') : _('Enter description fragment to search or * for all')) : '';
		}
		if ($selected_id == null) {
			$selected_id = get_post($name, (string)$opts['default']);
		}
		if (!is_array($selected_id)) {
			$selected_id = array((string)$selected_id);
		} // code is generalized for multiple selection support
		$txt = get_post($search_box);
		$rel = '';
		$limit = '';
		if (isset($_POST['_' . $name . '_update'])) { // select list or search box change
			if ($by_id) {
				$txt = $_POST[$name];
			}
			if (!$opts['async']) {
				Ajax::i()->activate('_page_body');
			}
			else {
				Ajax::i()->activate($name);
			}
		}
		if (isset($_POST[$search_button])) {
			if (!$opts['async']) {
				Ajax::i()->activate('_page_body');
			}
			else {
				Ajax::i()->activate($name);
			}
		}
		if ($search_box) {
			// search related sql modifications
			$rel = "rel='$search_box'"; // set relation to list
			if ($opts['search_submit']) {
				if (isset($_POST[$search_button])) {
					$selected_id = array(); // ignore selected_id while search
					if (!$opts['async']) {
						Ajax::i()->activate('_page_body');
					}
					else {
						Ajax::i()->activate($name);
					}
				}
				if ($txt == '') {
					if ($spec_option === false && $selected_id == array()) {
						$limit = ' LIMIT 1';
					}
					else {
						$opts['where'][] = $valfield . "='" . get_post($name, $spec_id) . "'";
					}
				}
				else {
					if ($txt != '*') {
						$texts = explode(" ", trim($txt));
						foreach ($texts as $text) {
							if (empty($text)) {
								continue;
							}
							$search_fields = $opts['search'];
							foreach ($search_fields as $i => $s) {
								$search_fields[$i] = $s . " LIKE " . DB::quote("%{$text}%");
							}
							$opts['where'][] = '(' . implode($search_fields, ' OR ') . ')';
						}
					}
				}
			}
		}
		// sql completion
		if (count($opts['where'])) {
			$where = strpos($sql, 'WHERE') == false ? ' WHERE ' : ' AND ';
			$where .= '(' . implode($opts['where'], ' AND ') . ')';
			$group_pos = strpos($sql, 'GROUP BY');
			if ($group_pos) {
				$group = substr($sql, $group_pos);
				$sql = substr($sql, 0, $group_pos) . $where . ' ' . $group;
			}
			else {
				$sql .= $where;
			}
		}
		if ($opts['order'] != false) {
			if (!is_array($opts['order'])) {
				$opts['order'] = array($opts['order']);
			}
			$sql .= ' ORDER BY ' . implode(',', $opts['order']);
		}
		$sql .= $limit;
		// ------ make selector ----------
		$selector = $first_opt = '';
		$first_id = false;
		$found = false;
		$lastcat = null;
		$edit = false;
		//if($name=='stock_id')	Errors::notice('<pre>'.print_r($_POST, true).'</pre>');
		//if($name=='curr_default') Errors::notice($opts['search_submit']);
		if ($results = DB::query($sql, null, true)) {
			$results = DB::fetch_all(PDO::FETCH_BOTH);
			foreach ($results as $contact_row) {
				$value = $contact_row[0];
				$descr = $opts['format'] == null ? $contact_row[1] : call_user_func($opts['format'], $contact_row);
				$sel = '';
				if (get_post($search_button) && ($txt == $value)) {
					$selected_id[] = $value;
				}
				if (in_array((string)$value, $selected_id, true)) {
					$sel = 'selected';
					$found = $value;
					$edit = $opts['editable'] && $contact_row['editable'] && (@$_POST[$search_box] == $value) ? $contact_row[1] : false; // get non-formatted description
					if ($edit) {
						break; // selected field is editable - abandon list construction
					}
				}
				// show selected option even if inactive
				if ((!isset($opts['show_inactive']) || !$opts['show_inactive']) && isset($contact_row['inactive']) && @$contact_row['inactive'] && $sel === '') {
					continue;
				}
				else {
					$optclass = (isset($contact_row['inactive']) && $contact_row['inactive']) ? "class='inactive'" : '';
				}
				if ($first_id === false) {
					$first_id = $value;
					$first_opt = $descr;
				}
				$cat = $contact_row[$opts['category']];
				if ($opts['category'] !== false && $cat != $lastcat) {
					$selector .= "<optgroup label='" . $cat . "'>\n";
					$lastcat = $cat;
				}
				$selector .= "<option $sel $optclass value='$value'>$descr</option>\n";
			}
			DB::free_result($results);
		}
		// Prepend special option.
		if ($spec_option !== false) { // if special option used - add it
			$first_id = $spec_id;
			$first_opt = $spec_option;
			//	}
			//	if($first_id !== false) {
			$sel = $found === false ? 'selected' : '';
			$optclass = isset($contact_row['inactive']) and $contact_row['inactive'] ? "class='inactive'" : '';
			$selector = "<option $sel value='$first_id'>$first_opt</option>\n" . $selector;
		}
		if ($found === false) {
			$selected_id = array($first_id);
		}
		$_POST[$name] = $multi ? $selected_id : $selected_id[0];
		$selector = "<select id='$name' autocomplete='off' " . ($multi ? "multiple" : '') . ($opts['height'] !== false ? ' size="' . $opts['height'] . '"' :
		 '') . "$disabled name='$name" . ($multi ? '[]' : '') . "' class='$class' title='" . $opts['sel_hint'] . "' $rel>" . $selector . "</select>\n";
		if ($by_id && ($search_box != false || $opts['editable'])) {
			// on first display show selector list
			if (isset($_POST[$search_box]) && $opts['editable'] && $edit) {
				$selector = "<input type='hidden' name='$name' value='" . $_POST[$name] . "'>";
				if (isset($contact_row['long_description'])) {
					$selector .= "<textarea name='{$name}_text' cols='{$opts['max']}' id='{$name}_text' $rel rows='2'>{$contact_row['long_description']}</textarea></td>\n";
				}
				else {
					$selector .= "<input type='text' $disabled name='{$name}_text' id='{$name}_text' size='" . $opts['editable'] . "' maxlength='" . $opts['max'] . "' $rel value='$edit'>\n";
				}
				JS::set_focus($name . '_text'); // prevent lost focus
			}
			else {
				if (get_post($search_submit ? $search_submit : "_{$name}_button")) {
					JS::set_focus($name);
				}
			} // prevent lost focus
			if (!$opts['editable']) {
				$txt = $found;
			}
			Ajax::i()->addUpdate($name, $search_box, $txt ? $txt : '');
		}
		Ajax::i()->addUpdate($name, "_{$name}_sel", $selector);
		// span for select list/input field update
		$selector = "<span id='_{$name}_sel'>" . $selector . "</span>\n";
		// if selectable or editable list is used - add select button
		if ($select_submit != false || $search_button) {
			$_select_button = "<input %s type='submit' class='combo_select' style='border:0;background:url(/themes/%s/images/button_ok.png) no-repeat;%s' aspect='fallback' name='%s' value=' ' title='" . _("Select") . "'> "; // button class selects form reload/ajax selector update
			$selector .= sprintf($_select_button, $disabled, User::theme(), (User::fallback() ? '' : 'display:none;'), '_' . $name . '_update') . "\n";
		}
		// ------ make combo ----------
		$edit_entry = '';
		if ($search_box != false) {
			$edit_entry = "<input $disabled type='text' name='$search_box' id='$search_box' size='" . $opts['size'] . "' maxlength='" . $opts['max'] . "' value='$txt' class='$class' rel='$name' autocomplete='off' title='" . $opts['box_hint'] . "'" . (!User::fallback() && !$by_id ?
			 " style=display:none;" : '') . ">\n";
			if ($search_submit != false || $opts['editable']) {
				$_search_button = "<input %s type='submit' class='combo_submit' style='border:0;background:url(/themes/%s/images/locate.png) no-repeat;%s' aspect='fallback' name='%s' value=' ' title='" . _("Set filter") . "'> ";
				$edit_entry .= sprintf($_search_button, $disabled, User::theme(), (User::fallback() ? '' : 'display:none;'), $search_submit ? $search_submit :
				 "_{$name}_button") . "\n";
			}
		}
		JS::default_focus(($search_box && $by_id) ? $search_box : $name);
		if ($search_box && $opts['cells']) {
			$str = ($edit_entry != '' ? "<td>$edit_entry</td>" : '') . "<td>$selector</td>";
		}
		else {
			$str = $edit_entry . $selector;
		}
		return $str;
	}

	/**
	 *	Universal array combo generator
	 *	$items is array of options 'value' => 'description'
	 *	Options is reduced set of combo_selector options and is merged with defaults.
	 *
	 * @param			$name
	 * @param			$selected_id
	 * @param			$items
	 * @param null $options
	 *
	 * @return string
	 */
	function array_selector($name, $selected_id, $items, $options = null) {
		$opts = array( // default options
			'spec_option' => false, // option text or false
			'spec_id' => 0, // option id
			'select_submit' => false, //submit on select: true/false
			'async' => true, // select update via ajax (true) vs _page_body reload
			'default' => '', // default value when $_POST is not set
			'multi' => false, // multiple select
			// search box parameters
			'height' => false, // number of lines in select box
			'sel_hint' => null, 'disabled' => false
		);
		// ------ merge options with defaults ----------
		if ($options != null) {
			$opts = array_merge($opts, $options);
		}
		$select_submit = $opts['select_submit'];
		$spec_id = $opts['spec_id'];
		$spec_option = $opts['spec_option'];
		$disabled = $opts['disabled'] ? "disabled" : '';
		$multi = $opts['multi'];
		if ($selected_id == null) {
			$selected_id = get_post($name, $opts['default']);
		}
		if (!is_array($selected_id)) {
			$selected_id = array($selected_id);
		} // code is generalized for multiple selection support
		if (isset($_POST['_' . $name . '_update'])) {
			if (!$opts['async']) {
				Ajax::i()->activate('_page_body');
			}
			else {
				Ajax::i()->activate($name);
			}
		}
		// ------ make selector ----------
		$selector = $first_opt = '';
		$first_id = false;
		$found = false;
		//if($name=='SelectStockFromList') Errors::error($sql);
		foreach ($items as $value => $descr) {
			$sel = '';
			if (in_array((string)$value, $selected_id)) {
				$sel = 'selected';
				$found = $value;
			}
			if ($first_id === false) {
				$first_id = $value;
				$first_opt = $descr;
			}
			$selector .= "<option $sel value='$value'>$descr</option>\n";
		}
		// Prepend special option.
		if ($spec_option !== false) { // if special option used - add it
			$first_id = $spec_id;
			$first_opt = $spec_option;
			$sel = $found === false ? 'selected' : '';
			$selector = "<option $sel value='$spec_id'>$spec_option</option>\n" . $selector;
		}
		if ($found === false) {
			$selected_id = array($first_id);
		}
		$_POST[$name] = $multi ? $selected_id : $selected_id[0];
		$selector = "<select " . ($multi ? "multiple" : '') . ($opts['height'] !== false ? ' size="' . $opts['height'] . '"' : '') . "$disabled name='$name" . ($multi ?
		 '[]' : '') . "' class='combo' title='" . $opts['sel_hint'] . "'>" . $selector . "</select>\n";
		Ajax::i()->addUpdate($name, "_{$name}_sel", $selector);
		$selector = "<span id='_{$name}_sel'>" . $selector . "</span>\n";
		if ($select_submit != false) { // if submit on change is used - add select button
			$_select_button = "<input %s type='submit' class='combo_select' style='border:0;background:url(/themes/%s/images/button_ok.png) no-repeat;%s' aspect='fallback' name='%s' value=' ' title='" . _("Select") . "'> ";
			$selector .= sprintf($_select_button, $disabled, User::theme(), (User::fallback() ? '' : 'display:none;'), '_' . $name . '_update') . "\n";
		}
		JS::default_focus($name);
		return $selector;
	}

	/**
	 * Universal submit form button.
	 * $atype - type of submit:
	 *	Normal submit:
	 *	 false - normal button; optional icon
	 *	 null	- button visible only in fallback mode; optional icon
	 *	Ajax submit:
	 *	 true		- standard button; optional icon
	 *
	 *	 'default' - default form submit on Ctrl-Enter press; dflt ICON_OK icon
	 *	 'selector' - ditto with closing current popup editor window
	 *	 'cancel'	- cancel form entry on Escape press; dflt ICON_CANCEL
	 *	 'process' - displays progress bar during call; optional icon
	 *
	 * $atype can contain also multiply type selectors separated by space,
	 * however make sense only combination of 'process' and one of defualt/selector/cancel
	 *
	 * @param			$name
	 * @param			$value
	 * @param bool $echo
	 * @param bool $title
	 * @param bool $atype
	 * @param bool $icon
	 *
	 * @return string
	 */
	function submit($name, $value, $echo = true, $title = false, $atype = false, $icon = false) {
		$aspect = '';
		if ($atype === null) {
			$aspect = User::fallback() ? " aspect='fallback'" : " style='display:none;'";
		}
		elseif (!is_bool($atype)) { // necessary: switch uses '=='
			$aspect = "aspect='$atype' ";
			$types = explode(' ', $atype);
			foreach ($types as $type) {
				switch ($type) {
					case 'selector':
						$aspect = " aspect='selector' rel = '$value'";
						$value = _("Select");
						if ($icon === false) {
							$icon = ICON_SUBMIT;
						}
						break;
					case 'default':
						if ($icon === false) {
							$icon = ICON_SUBMIT;
						}
						break;
					case 'cancel':
						if ($icon === false) {
							$icon = ICON_ESCAPE;
						}
						break;
				}
			}
		}
		$submit_str = "<button class=\"" . (($atype === true || $atype === false) ? (($atype) ? 'ajaxsubmit' : 'inputsubmit') :
		 $atype) . "\" type=\"submit\"" . $aspect . " name=\"$name\" id=\"$name\" value=\"$value\"" . ($title ? " title='$title'" : '') . ">" . ($icon ?
		 "<img src='/themes/" . User::theme() . "/images/$icon' height='12'>" : '') . "<span>$value</span>" . "</button>\n";
		if ($echo) {
			echo $submit_str;
		}
		else {
			return $submit_str;
		}
	}

	function submit_center($name, $value, $echo = true, $title = false, $async = false, $icon = false) {
		if ($echo) {
			echo "<div class='center'>";
		}
		submit($name, $value, $echo, $title, $async, $icon);
		if ($echo) {
			echo "</div>";
		}
	}

	function submit_center_first($name, $value, $title = false, $async = false, $icon = false) {
		echo "<div class='center'>";
		submit($name, $value, true, $title, $async, $icon);
		echo "&nbsp;";
	}

	function submit_center_middle($name, $value, $title = false, $async = false, $icon = false) {
		submit($name, $value, true, $title, $async, $icon);
		echo "&nbsp;";
	}

	function submit_center_last($name, $value, $title = false, $async = false, $icon = false) {
		echo "&nbsp;";
		submit($name, $value, true, $title, $async, $icon);
		echo "</div>";
	}

	/**
	 * For following controls:
	 * 'both' - use both Ctrl-Enter and Escape hotkeys
	 * 'cancel' - apply to 'RESET' button
	 *
	 * @param bool $add
	 * @param bool $title
	 * @param bool $async
	 * @param bool $clone
	 */
	function submit_add_or_update($add = true, $title = false, $async = false, $clone = false) {
		$cancel = $async;
		if ($async === 'both') {
			$async = 'default';
			$cancel = 'cancel';
		}
		else {
			if ($async === 'default') {
				$cancel = true;
			}
			else {
				if ($async === 'cancel') {
					$async = true;
				}
			}
		}
		if ($add) {
			submit('ADD_ITEM', _("Add new"), true, $title, $async);
		}
		else {
			submit('UPDATE_ITEM', _("Update"), true, _('Submit changes'), $async);
			if ($clone) {
				submit('CLONE', _("Clone"), true, _('Edit new record with current data'), $async);
			}
			submit('RESET', _("Cancel"), true, _('Cancel edition'), $cancel);
		}
	}

	function submit_add_or_update_center($add = true, $title = false, $async = false, $clone = false) {
		echo "<div class='center'>";
		submit_add_or_update($add, $title, $async, $clone);
		echo "</div>";
	}

	function submit_add_or_update_row($add = true, $right = true, $extra = "", $title = false, $async = false, $clone = false) {
		echo "<tr>";
		if ($right) {
			echo "<td>&nbsp;</td>\n";
		}
		echo "<td $extra>";
		submit_add_or_update($add, $title, $async, $clone);
		echo "</td></tr>\n";
	}

	function submit_cells($name, $value, $extra = "", $title = false, $async = false) {
		echo "<td $extra>";
		submit($name, $value, true, $title, $async);
		echo "</td>\n";
	}

	function submit_row($name, $value, $right = true, $extra = "", $title = false, $async = false) {
		echo "<tr>";
		if ($right) {
			echo "<td>&nbsp;</td>\n";
		}
		submit_cells($name, $value, $extra, $title, $async);
		echo "</tr>\n";
	}

	function submit_return($name, $value, $title = false) {
		if (Input::request('popup')) {
			submit($name, $value, true, $title, 'selector');
		}
	}

	function submit_js_confirm($name, $msg) {
		JS::beforeload("_validate.$name=function(){ return confirm('" . strtr($msg, array("\n" => '\\n')) . "');};");
	}

	function set_icon($icon, $title = false) {
		return "<img src='/themes/" . User::theme() . "/images/$icon' style='width:12' height='12' " . ($title ? " title='$title'" : "") . " />\n";
	}

	function button($name, $value, $title = false, $icon = false, $aspect = '') {
		// php silently changes dots,spaces,'[' and characters 128-159
		// to underscore in POST names, to maintain compatibility with register_globals
		$rel = '';
		if ($aspect == 'selector') {
			$rel = " rel='$value'";
			$value = _("Select");
		}
		if (User::graphic_links() && $icon) {
			if ($value == _("Delete")) // Helper during implementation
			{
				$icon = ICON_DELETE;
			}
			return "<button type='submit' class='editbutton' name='" . htmlentities(strtr($name, array(
																																																'.' => '=2E', ' ' => '=20', '=' => '=3D', '[' => '=5B'
																																													 ))) . "' value='1'" . ($title ? " title='$title'" :
			 " title='$value'") . ($aspect ? " aspect='$aspect'" : '') . $rel . " />" . set_icon($icon) . "</button>\n";
		}
		else {
			return "<input type='submit' class='editbutton' name='" . htmlentities(strtr($name, array(
																																															 '.' => '=2E', ' ' => '=20', '=' => '=3D', '[' => '=5B'
																																													))) . "' value='$value'" . ($title ? " title='$title'" : '') . ($aspect ?
			 " aspect='$aspect'" : '') . $rel . " />\n";
		}
	}

	function button_cell($name, $value, $title = false, $icon = false, $aspect = '') {
		echo "<td class='center'>";
		echo button($name, $value, $title, $icon, $aspect);
		echo "</td>";
	}

	function delete_button_cell($name, $value, $title = false) {
		button_cell($name, $value, $title, ICON_DELETE);
	}

	function edit_button_cell($name, $value, $title = false) {
		button_cell($name, $value, $title, ICON_EDIT);
	}

	function select_button_cell($name, $value, $title = false) {
		button_cell($name, $value, $title, ICON_ADD, 'selector');
	}

	function check_value($name) {
		if (!isset($_POST[$name])) {
			return 0;
		}
		return 1;
	}

	function checkbox($label, $name, $value = null, $submit_on_change = false, $title = false) {
		$str = '';
		if ($label) {
			$str .= $label . " ";
		}
		if ($submit_on_change !== false) {
			if ($submit_on_change === true) {
				$submit_on_change = "JsHttpRequest.request(\"_{$name}_update\", this.form);";
			}
		}
		if ($value === null) {
			$value = get_post($name, 0);
		}
		$str .= "<input" . ($value == 1 ? ' checked' : '') . " type='checkbox' name='$name' id='$name' value='1'" . ($submit_on_change ? " onclick='$submit_on_change'" :
		 '') . ($title ? " title='$title'" : '') . " >\n";
		Ajax::i()->addUpdate($name, $name, $value);
		return $str;
	}

	function check($label, $name, $value = null, $submit_on_change = false, $title = false) {
		echo checkbox($label, $name, $value, $submit_on_change, $title);
	}

	function check_cells($label, $name, $value = null, $submit_on_change = false, $title = false, $params = '') {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td $params>";
		echo check(null, $name, $value, $submit_on_change, $title);
		echo "</td>";
	}

	function check_row($label, $name, $value = null, $submit_on_change = false, $title = false) {
		echo "<tr><td class='label'>$label</td>";
		echo check_cells(NULL, $name, $value, $submit_on_change, $title);
		echo "</tr>\n";
	}

	function text_cells($label, $name, $value = null, $size = "", $max = "", $title = false, $labparams = "", $post_label = "", $inparams = "") {
		JS::default_focus($name);
		if ($label != null) {
			label_cell($label, $labparams);
		}
		echo "<td>";
		if ($value === null) {
			$value = get_post($name);
		}
		echo "<input $inparams type=\"text\" name=\"$name\" size=\"$size\" maxlength=\"$max\" value=\"$value\"" . ($title ? " title='$title'" : '') . ">";
		if ($post_label != "") {
			echo " " . $post_label;
		}
		echo "</td>\n";
		Ajax::i()->addUpdate($name, $name, $value);
	}

	function text_cells_ex($label, $name, $size, $max = null, $init = null, $title = null, $labparams = null, $post_label = null, $submit_on_change = false) {
		JS::default_focus($name);
		if (!isset($_POST[$name]) || $_POST[$name] == "") {
			if ($init !== null) {
				$_POST[$name] = $init;
			}
			else {
				$_POST[$name] = "";
			}
		}
		if ($label != null) {
			echo "<td class='label'>$label</td>";
		}
		if (!isset($max)) {
			$max = $size;
		}
		echo "<td>";
		$class = $submit_on_change ? 'class="searchbox"' : '';
		echo "<input $class type=\"text\" name=\"$name\" size=\"$size\" maxlength=\"$max\" value=\"" . $_POST[$name] . "\"" . ($title ? " title='$title'" : '') . " >";
		if ($post_label) {
			echo " " . $post_label;
		}
		echo "</td>\n";
		Ajax::i()->addUpdate($name, $name, $_POST[$name]);
	}

	function text_row($label, $name, $value, $size = false, $max, $title = null, $params = "", $post_label = "") {
		echo "<tr><td class='label'>$label</td>";
		text_cells(null, $name, $value, $size, $max, $title, $params, $post_label);
		echo "</tr>\n";
	}

	function text_row_ex($label, $name, $size, $max = null, $title = null, $value = null, $params = null, $post_label = null, $params2 = '', $submit_on_change = false) {
		echo "<tr {$params}><td class='label' {$params2}>$label</td>";
		text_cells_ex(null, $name, $size, $max, $value, $title, $params, $post_label, $submit_on_change);
		echo "</tr>\n";
	}

	function email_row($label, $name, $value, $size, $max, $title = null, $params = "", $post_label = "") {
		if (get_post($name)) {
			$label = "<a href='Mailto:" . $_POST[$name] . "'>$label</a>";
		}
		text_row($label, $name, $value, $size, $max, $title, $params, $post_label);
	}

	function email_row_ex($label, $name, $size, $max = null, $title = null, $value = null, $params = null, $post_label = null) {
		if (get_post($name)) {
			$label = "<a href='Mailto:" . $_POST[$name] . "'>$label</a>";
		}
		text_row_ex($label, $name, $size, $max, $title, $value, $params, $post_label);
	}

	function link_row($label, $name, $value, $size, $max, $title = null, $params = "", $post_label = "") {
		$val = get_post($name);
		if ($val) {
			if (strpos($val, 'http://') === false) {
				$val = 'http://' . $val;
			}
			$label = "<a href='$val' target='_blank'>$label</a>";
		}
		text_row($label, $name, $value, $size, $max, $title, $params, $post_label);
	}

	function link_row_ex($label, $name, $size, $max = null, $title = null, $value = null, $params = null, $post_label = null) {
		$val = get_post($name);
		if ($val) {
			if (strpos($val, 'http://') === false) {
				$val = 'http://' . $val;
			}
			$label = "<a href='$val' target='_blank'>$label</a>";
		}
		text_row_ex($label, $name, $size, $max, $title, $value, $params, $post_label);
	}

	/**
	 *
	 *	 Since ADV 2.2	$init parameter is superseded by $check.
	 *	 When $check!=null current date is displayed in red when set to other
	 *	 than current date.
	 *
	 * @param			 $label
	 * @param			 $name
	 * @param null	$title
	 * @param null	$check
	 * @param int	 $inc_days
	 * @param int	 $inc_months
	 * @param int	 $inc_years
	 * @param null	$params
	 * @param bool	$submit_on_change
	 * @param array $options
	 */
	function date_cells($label, $name, $title = null, $check = null, $inc_days = 0, $inc_months = 0, $inc_years = 0, $params = null, $submit_on_change = false, $options = array()) {
		if (!isset($_POST[$name]) || $_POST[$name] == "") {
			if ($inc_years == 1001) {
				$_POST[$name] = null;
			}
			else {
				$dd = Dates::Today();
				if ($inc_days != 0) {
					$dd = Dates::add_days($dd, $inc_days);
				}
				if ($inc_months != 0) {
					$dd = Dates::add_months($dd, $inc_months);
				}
				if ($inc_years != 0) {
					$dd = Dates::add_years($dd, $inc_years);
				}
				$_POST[$name] = $dd;
			}
		}
		$post_label = "";
		if ($label != null) {
			label_cell($label, $params);
		}
		echo "<td>";
		$class = $submit_on_change ? 'searchbox datepicker' : 'datepicker';
		$aspect = $check ? 'aspect="cdate"' : '';
		if ($check && (get_post($name) != Dates::Today())) {
			$aspect .= ' style="color:#FF0000"';
		}
		echo "<input id='$name' type=\"text\" name=\"$name\" class=\"$class\" $aspect size=\"9\" maxlength=\"12\" value=\"" . $_POST[$name] . "\"" . ($title ?
		 " title='$title'" : '') . " > $post_label";
		echo "</td>\n";
		Ajax::i()->addUpdate($name, $name, $_POST[$name]);
	}

	function date_row($label, $name, $title = null, $check = null, $inc_days = 0, $inc_months = 0, $inc_years = 0, $params = null, $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		date_cells(null, $name, $title, $check, $inc_days, $inc_months, $inc_years, $params, $submit_on_change);
		echo "</tr>\n";
	}

	function password_row($label, $name, $value) {
		echo "<tr><td class='label'>$label</td>";
		label_cell("<input type='password' name='$name' size=20 maxlength=20 value='$value' />");
		echo "</tr>\n";
	}

	function file_cells($label, $name, $id = "") {
		if ($id != "") {
			$id = "id='$id'";
		}
		label_cells($label, "<input type='file' name='$name' $id />");
	}

	function file_row($label, $name, $id = "") {
		echo "<tr><td class='label'>$label</td>";
		file_cells(null, $name, $id);
		echo "</tr>\n";
	}

	function ref_cells($label, $name, $title = null, $init = null, $params = null, $submit_on_change = false) {
		text_cells_ex($label, $name, 13, 18, $init, $title, $params, null, $submit_on_change);
	}

	function ref_row($label, $name, $title = null, $init = null, $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		ref_cells(null, $name, $title, $init, null, $submit_on_change);
		echo "</tr>\n";
	}

	function percent_row($label, $name, $init = null, $params = '') {
		if (!isset($_POST[$name]) || $_POST[$name] == "") {
			$_POST[$name] = ($init === null) ? '' : $init;
		}
		small_amount_row($label, $name . '" ' . $params, $_POST[$name], null, "%", User::percent_dec());
	}

	function amount_cells_ex($label, $name, $size, $max = null, $init = null, $params = null, $post_label = null, $dec = null, $id = null) {
		if (!isset($dec)) {
			$dec = User::price_dec();
		}
		if (!isset($_POST[$name]) || $_POST[$name] == "") {
			if ($init !== null) {
				$_POST[$name] = $init;
			}
			else {
				$_POST[$name] = 0;
			}
		}
		if ($label != null) {
			if ($params == null) {
				$params = "class='label'";
			}
			label_cell($label, $params);
		}
		if (!isset($max)) {
			$max = $size;
		}
		if ($label != null) {
			echo "<td>";
		}
		else {
			echo "<td class='right'>";
		}
		echo "<input ";
		if ($id != null) {
			echo "id='$id'";
		}
		if ($name == 'freight') {
			echo "class='freight' ";
		}
		else {
			echo "class='amount' ";
		}
		echo "type=\"text\" name=\"$name\" maxlength=\"$max\" dec=\"$dec\" value=\"" . $_POST[$name] . "\">";
		if ($post_label) {
			echo "<span id='_{$name}_label'> $post_label</span>";
			Ajax::i()->addUpdate($name, '_' . $name . '_label', $post_label);
		}
		echo "</td>\n";
		Ajax::i()->addUpdate($name, $name, $_POST[$name]);
		Ajax::i()->addAssign($name, $name, 'dec', $dec);
	}

	function amount_cells($label, $name, $init = null, $params = null, $post_label = null, $dec = null, $id = null) {
		amount_cells_ex($label, $name, 10, 15, $init, $params, $post_label, $dec, $id);
	}

	/**
	 *	 JAM	Allow entered unit prices to be fractional
	 *
	 * @param			$label
	 * @param			$name
	 * @param null $init
	 * @param null $params
	 * @param null $post_label
	 * @param null $dec
	 */
	function unit_amount_cells($label, $name, $init = null, $params = null, $post_label = null, $dec = null) {
		if (!isset($dec)) {
			$dec = User::price_dec() + 2;
		}
		amount_cells_ex($label, $name, 10, 15, $init, $params, $post_label, $dec + 2);
	}

	function amount_row($label, $name, $init = null, $params = null, $post_label = null, $dec = null) {
		echo "<tr>";
		amount_cells($label, $name, $init, $params, $post_label, $dec);
		echo "</tr>\n";
	}

	function small_amount_row($label, $name, $init = null, $params = null, $post_label = null, $dec = null, $leftfill = 0) {
		echo "<tr>";
		small_amount_cells($label, $name, $init, $params, $post_label, $dec);
		if ($leftfill != 0) {
			echo "<td colspan=$leftfill></td>";
		}
		echo "</tr>\n";
	}

	function qty_cells($label, $name, $init = null, $params = null, $post_label = null, $dec = null) {
		if (!isset($dec)) {
			$dec = User::qty_dec();
		}
		amount_cells_ex($label, $name, 15, 15, $init, $params, $post_label, $dec);
	}

	function qty_row($label, $name, $init = null, $params = null, $post_label = null, $dec = null) {
		if (!isset($dec)) {
			$dec = User::qty_dec();
		}
		echo "<tr>";
		amount_cells($label, $name, $init, $params, $post_label, $dec);
		echo "</tr>\n";
	}

	function small_qty_row($label, $name, $init = null, $params = null, $post_label = null, $dec = null) {
		if (!isset($dec)) {
			$dec = User::qty_dec();
		}
		echo "<tr>";
		small_amount_cells($label, $name, $init, $params, $post_label, $dec);
		echo "</tr>\n";
	}

	function small_amount_cells($label, $name, $init = null, $params = null, $post_label = null, $dec = null) {
		amount_cells_ex($label, $name, 7, 12, $init, $params, $post_label, $dec);
	}

	function small_qty_cells($label, $name, $init = null, $params = null, $post_label = null, $dec = null) {
		if (!isset($dec)) {
			$dec = User::qty_dec();
		}
		amount_cells_ex($label, $name, 7, 12, $init, $params, $post_label, $dec);
	}

	function textarea_cells($label, $name, $value, $cols, $rows, $title = null, $params = "") {
		JS::default_focus($name);
		if ($label != null) {
			echo "<td $params>$label</td>\n";
		}
		if ($value === null) {
			$value = (!isset($_POST[$name]) ? "" : $_POST[$name]);
		}
		echo "<td><textarea id='$name' name='$name' cols='$cols' rows='$rows'" . ($title ? " title='$title'" : '') . ">$value</textarea></td>\n";
		Ajax::i()->addUpdate($name, $name, $value);
	}

	function textarea_row($label, $name, $value, $cols, $rows, $title = null, $params = "", $labelparams = "") {
		echo "<tr><td class='label' $labelparams>$label</td>";
		textarea_cells(null, $name, $value, $cols, $rows, $title, $params);
		echo "</tr>\n";
	}

	/**
	 *
	 *	 When show_inactive page option is set
	 *	 displays value of inactive field as checkbox cell.
	 *	 Also updates database record after status change.
	 *
	 * @param $id
	 * @param $value
	 * @param $table
	 * @param $key
	 */
	function inactive_control_cell($id, $value, $table, $key) {
		$name = "Inactive" . $id;
		$value = $value ? 1 : 0;
		if (check_value('show_inactive')) {
			if (isset($_POST['LInact'][$id]) && (get_post('_Inactive' . $id . '_update') || get_post('Update')) && (check_value('Inactive' . $id) != $value)
			) {
				DB::update_record_status($id, !$value, $table, $key);
			}
			echo '<td class="center">' . checkbox(null, $name, $value, true, '', "class='center'") . hidden("LInact[$id]", $value, false) . '</td>';
		}
	}

	/**
	 *
	 *	 Displays controls for optional display of inactive records
	 *
	 * @param $th
	 */
	function inactive_control_row($th) {
		echo	"<tr><td colspan=" . (count($th)) . ">" . "<div style='float:left;'>" . checkbox(null, 'show_inactive', null, true) . _("Show also Inactive") . "</div><div style='float:right;'>" . submit('Update', _('Update'), false, '', null) . "</div></td></tr>";
	}

	/**
	 *
	 *	 Inserts additional column header when display of inactive records is on.
	 *
	 * @param $th
	 */
	function inactive_control_column(&$th) {
		if (check_value('show_inactive')) {
			Arr::insert($th, count($th) - 2, _("Inactive"));
		}
		if (get_post('_show_inactive_update')) {
			Ajax::i()->activate('_page_body');
		}
	}

	function yesno_list($name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false) {
		$items = array();
		$items['0'] = strlen($name_no) ? $name_no : _("No");
		$items['1'] = strlen($name_yes) ? $name_yes : _("Yes");
		return array_selector($name, $selected_id, $items, array(
																														'select_submit' => $submit_on_change, 'async' => false
																											 )); // FIX?
	}

	function yesno_list_cells($label, $name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false) {
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo yesno_list($name, $selected_id, $name_yes, $name_no, $submit_on_change);
		echo "</td>\n";
	}

	function yesno_list_row($label, $name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false) {
		echo "<tr><td class='label'>$label</td>";
		yesno_list_cells(null, $name, $selected_id, $name_yes, $name_no, $submit_on_change);
		echo "</tr>\n";
	}

	function record_status_list_row($label, $name) {
		return yesno_list_row($label, $name, null, _('Inactive'), _('Active'));
	}

	function number_list($name, $selected, $from, $to, $no_option = false) {
		$items = array();
		for ($i = $from; $i <= $to; $i++) {
			$items[$i] = "$i";
		}
		return array_selector($name, $selected, $items, array(
																												 'spec_option' => $no_option, 'spec_id' => ALL_NUMERIC
																										));
	}

	function number_list_cells($label, $name, $selected, $from, $to, $no_option = false) {
		if ($label != null) {
			label_cell($label);
		}
		echo "<td>\n";
		echo number_list($name, $selected, $from, $to, $no_option);
		echo "</td>\n";
	}

	function number_list_row($label, $name, $selected, $from, $to, $no_option = false) {
		echo "<tr><td class='label'>$label</td>";
		echo number_list_cells(null, $name, $selected, $from, $to, $no_option);
		echo "</tr>\n";
	}

	function dateformats_list_row($label, $name, $value = null) {
		echo "<tr><td class='label'>$label</td>\n<td>";
		echo array_selector($name, $value, Config::get('formats_date'));
		echo "</td></tr>\n";
	}

	function dateseps_list_row($label, $name, $value = null) {
		echo "<tr><td class='label'>$label</td>\n<td>";
		echo array_selector($name, $value, Config::get('separators_date'));
		echo "</td></tr>\n";
	}

	function thoseps_list_row($label, $name, $value = null) {
		echo "<tr><td class='label'>$label</td>\n<td>";
		echo array_selector($name, $value, Config::get('separators_thousands'));
		echo "</td></tr>\n";
	}

	function decseps_list_row($label, $name, $value = null) {
		echo "<tr><td class='label'>$label</td>\n<td>";
		echo array_selector($name, $value, Config::get('separators_decimal'));
		echo "</td></tr>\n";
	}

	function _format_date($row) {
		return Dates::sql2date($row['reconciled']);
	}

	function _format_add_curr($row) {
		static $company_currency;
		if ($company_currency == null) {
			$company_currency = Bank_Currency::for_company();
		}
		return $row[1] . ($row[2] == $company_currency ? '' : ("&nbsp;-&nbsp;" . $row[2]));
	}

	function _format_stock_items($row) {
		return (User::show_codes() ? ($row[0] . "&nbsp;-&nbsp;") : "") . $row[1];
	}

	function _format_template_items($row) {
		return ($row[0] . "&nbsp;- &nbsp;" . _("Amount") . "&nbsp;" . $row[1]);
	}

	function _format_fiscalyears($row) {
		return Dates::sql2date($row[1]) . "&nbsp;-&nbsp;" . Dates::sql2date($row[2]) . "&nbsp;&nbsp;" . ($row[3] ? _('Closed') : _('Active')) . "</option>\n";
	}

	function _format_account($row) {
		return $row[0] . "&nbsp;&nbsp;&nbsp;&nbsp;" . $row[1];
	}

?>
