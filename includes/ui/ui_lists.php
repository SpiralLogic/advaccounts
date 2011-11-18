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
	//----------------------------------------------------------------------------
	//	Universal sql combo generator
	//	$sql must return selector values and selector texts in columns 0 & 1
	//	Options are merged with defaults.
	/**
	 *
	 * @param					$name
	 * @param					$selected_id
	 * @param					$sql
	 * @param					$valfield
	 * @param					$namefield
	 * @param null		 $options
	 *
	 * @return string*/
	function combo_input($name, $selected_id = null, $sql, $valfield, $namefield, $options = null)
	{
		$Ajax = Ajax::instance();
		$opts = array( // default options
			'where' => array(), // additional constraints
			'order' => $namefield, // list sort order
			// special option parameters
			'spec_option' => false, // option text or false
			'spec_id' => 0, // option id
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
			$opts['box_hint'] = $search_box && $search_submit != false ? ($by_id ? _('Enter code fragment to search or * for all') : _('Enter description fragment to search or * for all')) : '';
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
				$Ajax->activate('_page_body');
			} else {
				$Ajax->activate($name);
			}
		}
		if (isset($_POST[$search_button])) {
			if (!$opts['async']) {
				$Ajax->activate('_page_body');
			} else {
				$Ajax->activate($name);
			}
		}
		if ($search_box) {
			// search related sql modifications
			$rel = "rel='$search_box'"; // set relation to list
			if ($opts['search_submit']) {
				if (isset($_POST[$search_button])) {
					$selected_id = array(); // ignore selected_id while search
					if (!$opts['async']) {
						$Ajax->activate('_page_body');
					} else {
						$Ajax->activate($name);
					}
				}
				if ($txt == '') {
					if ($spec_option === false && $selected_id == array()) {
						$limit = ' LIMIT 1';
					} else {
						$opts['where'][] = $valfield . "='" . get_post($name, $spec_id) . "'";
					}
				} else {
					if ($txt != '*') {
						$texts = explode(" ", trim($txt));
						foreach ($texts as $text) {
							if (empty($text)) {
								continue;
							}
							$search_feilds = $opts['search'];
							foreach ($search_feilds as $i => $s) {
								$search_feilds[$i] = $s . " LIKE '%{$text}%'";
							}
							$opts['where'][] = '(' . implode($search_feilds, ' OR ') . ')';
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
			} else {
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
		if ($result = DB::query($sql)) {
			while ($contact_row = DB::fetch($result)) {
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
				} else {
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
			DB::free_result($result);
		}
		// Prepend special option.
		if ($spec_option !== false) { // if special option used - add it
			$first_id = $spec_id;
			$first_opt = $spec_option;
			//	}
			//	if($first_id !== false) {
			$sel = $found === false ? 'selected' : '';
			$optclass = @$contact_row['inactive'] ? "class='inactive'" : '';
			$selector = "<option $sel value='$first_id'>$first_opt</option>\n" . $selector;
		}
		if ($found === false) {
			$selected_id = array($first_id);
		}
		$_POST[$name] = $multi ? $selected_id : $selected_id[0];
		$selector = "<select id='$name' autocomplete='off' " . ($multi ? "multiple" : '') . ($opts['height'] !== false ? ' size="' . $opts['height'] . '"' : '') . "$disabled name='$name" . ($multi ? '[]' : '') . "' class='$class' title='" . $opts['sel_hint'] . "' $rel>" . $selector . "</select>\n";
		if ($by_id && ($search_box != false || $opts['editable'])) {
			// on first display show selector list
			if (isset($_POST[$search_box]) && $opts['editable'] && $edit) {
				$selector = "<input type='hidden' name='$name' value='" . $_POST[$name] . "'>";
				if (isset($contact_row['long_description'])) {
					$selector .= "<textarea name='{$name}_text' cols='{$opts['max']}'  id='{$name}_text' $rel rows='2'>{$contact_row['long_description']}</textarea></td>\n";
				} else {
					$selector .= "<input type='text' $disabled name='{$name}_text' id='{$name}_text' size='" . $opts['editable'] . "' maxlength='" . $opts['max'] . "' $rel value='$edit'>\n";
				}
				JS::set_focus($name . '_text'); // prevent lost focus
			} else {
				if (get_post($search_submit ? $search_submit : "_{$name}_button")) {
					JS::set_focus($name);
				}
			} // prevent lost focus
			if (!$opts['editable']) {
				$txt = $found;
			}
			$Ajax->addUpdate($name, $search_box, $txt ? $txt : '');
		}
		$Ajax->addUpdate($name, "_{$name}_sel", $selector);
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
			$edit_entry = "<input $disabled type='text' name='$search_box' id='$search_box' size='" . $opts['size'] . "' maxlength='" . $opts['max'] . "' value='$txt' class='$class' rel='$name' autocomplete='off' title='" . $opts['box_hint'] . "'" . (!User::fallback() && !$by_id ? " style=display:none;" :
			 '') . ">\n";
			if ($search_submit != false || $opts['editable']) {
				$_search_button = "<input %s type='submit' class='combo_submit' style='border:0;background:url(/themes/%s/images/locate.png) no-repeat;%s' aspect='fallback' name='%s' value=' ' title='" . _("Set filter") . "'> ";
				$edit_entry .= sprintf($_search_button, $disabled, User::theme(), (User::fallback() ? '' : 'display:none;'), $search_submit ? $search_submit : "_{$name}_button") . "\n";
			}
		}
		JS::default_focus(($search_box && $by_id) ? $search_box : $name);
		if ($search_box && $opts['cells']) {
			$str = ($edit_entry != '' ? "<td>$edit_entry</td>" : '') . "<td>$selector</td>";
		} else {
			$str = $edit_entry . $selector;
		}
		return $str;
	}

	/*
		Helper function.
		Returns true if selector $name is subject to update.
	 */
	function list_updated($name)
	{
		return isset($_POST['_' . $name . '_update']) || isset($_POST['_' . $name . '_button']);
	}

	//----------------------------------------------------------------------------------------------
	//	Universal array combo generator
	//	$items is array of options 'value' => 'description'
	//	Options is reduced set of combo_selector options and is merged with defaults.
	function array_selector($name, $selected_id, $items, $options = null)
	{
		$Ajax = Ajax::instance();
		$opts = array( // default options
			'spec_option' => false, // option text or false
			'spec_id' => 0, // option id
			'select_submit' => false, //submit on select: true/false
			'async' => true, // select update via ajax (true) vs _page_body reload
			'default' => '', // default value when $_POST is not set
			'multi' => false, // multiple select
			// search box parameters
			'height' => false, // number of lines in select box
			'sel_hint' => null, 'disabled' => false);
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
				$Ajax->activate('_page_body');
			} else {
				$Ajax->activate($name);
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
		$selector = "<select " . ($multi ? "multiple" : '') . ($opts['height'] !== false ? ' size="' . $opts['height'] . '"' : '') . "$disabled name='$name" . ($multi ? '[]' : '') . "' class='combo' title='" . $opts['sel_hint'] . "'>" . $selector . "</select>\n";
		$Ajax->addUpdate($name, "_{$name}_sel", $selector);
		$selector = "<span id='_{$name}_sel'>" . $selector . "</span>\n";
		if ($select_submit != false) { // if submit on change is used - add select button
			$_select_button = "<input %s type='submit' class='combo_select' style='border:0;background:url(/themes/%s/images/button_ok.png) no-repeat;%s' aspect='fallback' name='%s' value=' ' title='" . _("Select") . "'> ";
			$selector .= sprintf($_select_button, $disabled, User::theme(), (User::fallback() ? '' : 'display:none;'), '_' . $name . '_update') . "\n";
		}
		JS::default_focus($name);
		return $selector;
	}

	//----------------------------------------------------------------------------------------------
	function _format_add_curr($row)
	{
		static $company_currency;
		if ($company_currency == null) {
			$company_currency = Banking::get_company_currency();
		}
		return $row[1] . ($row[2] == $company_currency ? '' : ("&nbsp;-&nbsp;" . $row[2]));
	}

	function supplier_list($name, $selected_id = null, $spec_option = false, $submit_on_change = false, $all = false, $editkey = false)
	{
		$sql = "SELECT supplier_id, supp_ref, curr_code, inactive FROM suppliers ";
		$mode = DB_Company::get_pref('no_supplier_list');
		if ($editkey) {
			set_editor('supplier', $name, $editkey);
		}
		return combo_input($name, $selected_id, $sql, 'supplier_id', 'supp_name', array(
			'format' => '_format_add_curr',
			'order' => array('supp_ref'),
			'search_box' => $mode != 0,
			'type' => 1,
			'spec_option' => $spec_option === true ? _("All Suppliers") : $spec_option,
			'spec_id' => ALL_TEXT,
			'select_submit' => $submit_on_change,
			'async' => false,
			'sel_hint' => $mode ? _('Press Space tab to filter by name fragment') : _('Select supplier'),
			'show_inactive' => $all));
	}

	function supplier_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false)
	{
		if ($label != null) {
			echo "<td>$label</td><td>\n";
		}
		echo supplier_list($name, $selected_id, $all_option, $submit_on_change, $all, $editkey);
		echo "</td>\n";
	}

	function supplier_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false)
	{
		echo "<tr><td class='label' name='supplier_name'>$label</td><td>";
		echo supplier_list($name, $selected_id, $all_option, $submit_on_change, $all, $editkey);
		echo "</td></tr>\n";
	}

	//----------------------------------------------------------------------------------------------
	function customer_list($name, $selected_id = null, $spec_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false, $async = false)
	{
		$sql = "SELECT debtor_no, debtor_ref, curr_code, inactive FROM debtors_master ";
		$mode = DB_Company::get_pref('no_customer_list');
		if ($editkey) {
			set_editor('customer', $name, $editkey);
		}

		return combo_input($name, $selected_id, $sql, 'debtor_no', 'name', array(
			'format' => '_format_add_curr',
			'order' => array('debtor_ref'),
			'search_box' => $mode != 0,
			'type' => 1,
			'size' => 20,
			'spec_option' => $spec_option === true ? _("All Customers") : $spec_option,
			'spec_id' => ALL_TEXT,
			'select_submit' => $submit_on_change,
			'async' => $async,
			'sel_hint' => $mode ? _('Press Space tab to filter by name fragment; F2 - entry new customer') : _('Select customer'),
			'show_inactive' => $show_inactive));
	}

	function customer_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false, $async = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td nowrap>";
		echo customer_list($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey, $async);
		echo "</td>\n";
	}

	function customer_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $show_inactive = false, $editkey = false)
	{
		echo "<tr><td id='customer_id_label' class='label pointer'>$label</td><td nowrap>";
		echo customer_list($name, $selected_id, $all_option, $submit_on_change, $show_inactive, $editkey);
		echo "</td>\n</tr>\n";
	}

	//------------------------------------------------------------------------------------------------
	function customer_branches_list($customer_id, $name, $selected_id = null, $spec_option = true, $enabled = true, $submit_on_change = false, $editkey = false)
	{
		$sql = "SELECT branch_code, branch_ref FROM cust_branch
		WHERE branch_ref <> 'accounts' AND debtor_no='" . $customer_id . "' ";
		if ($editkey) {
			set_editor('branch', $name, $editkey);
		}
		$where = $enabled ? array("disable_trans = 0") : array();
		return combo_input($name, $selected_id, $sql, 'branch_code', 'br_name', array(
			'where' => $where, 'order' => array('branch_ref'), 'spec_option' => $spec_option === true ? _('All branches') : $spec_option, 'spec_id' => ALL_TEXT, 'select_submit' => $submit_on_change, 'sel_hint' => _('Select customer branch')));
	}

	//------------------------------------------------------------------------------------------------
	function customer_branches_list_cells($label, $customer_id, $name, $selected_id = null, $all_option = true, $enabled = true, $submit_on_change = false, $editkey = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo customer_branches_list($customer_id, $name, $selected_id, $all_option, $enabled, $submit_on_change, $editkey);
		echo "</td>\n";
	}

	function customer_branches_list_row($label, $customer_id, $name, $selected_id = null, $all_option = true, $enabled = true, $submit_on_change = false, $editkey = false)
	{
		echo "<tr><td class='label'>$label</td>";
		customer_branches_list_cells(null, $customer_id, $name, $selected_id, $all_option, $enabled, $submit_on_change, $editkey);
		echo "</tr>";
	}

	//------------------------------------------------------------------------------------------------
	function locations_list($name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		$sql = "SELECT loc_code, location_name, inactive FROM locations";
		return combo_input($name, $selected_id, $sql, 'loc_code', 'location_name', array(
			'spec_option' => $all_option === true ? _("All Locations") : $all_option, 'spec_id' => ALL_TEXT, 'select_submit' => $submit_on_change));
	}

	function locations_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo locations_list($name, $selected_id, $all_option, $submit_on_change);
		echo "</td>\n";
	}

	function locations_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		echo "<tr><td class='label'>$label</td>";
		locations_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
		echo "</tr>\n";
	}

	//-----------------------------------------------------------------------------------------------
	function currencies_list($name, $selected_id = null, $submit_on_change = false)
	{
		$sql = "SELECT curr_abrev, currency, inactive FROM currencies";
		// default to the company currency
		return combo_input($name, $selected_id, $sql, 'curr_abrev', 'currency', array(
			'select_submit' => $submit_on_change, 'default' => Banking::get_company_currency(), 'async' => false));
	}

	function currencies_list_cells($label, $name, $selected_id = null, $submit_on_change = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo currencies_list($name, $selected_id, $submit_on_change);
		echo "</td>\n";
	}

	function currencies_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		echo "<tr><td class='label'>$label</td>";
		currencies_list_cells(null, $name, $selected_id, $submit_on_change);
		echo "</tr>\n";
	}

	//---------------------------------------------------------------------------------------------------
	function fiscalyears_list($name, $selected_id = null, $submit_on_change = false)
	{
		$sql = "SELECT * FROM fiscal_year";
		// default to the company current fiscal year
		return combo_input($name, $selected_id, $sql, 'id', '', array(
			'order' => 'begin', 'default' => DB_Company::get_pref('f_year'), 'format' => '_format_fiscalyears', 'select_submit' => $submit_on_change, 'async' => false));
	}

	function _format_fiscalyears($row)
	{
		return Dates::sql2date($row[1]) . "&nbsp;-&nbsp;" . Dates::sql2date($row[2]) . "&nbsp;&nbsp;" . ($row[3] ? _('Closed') : _('Active')) . "</option>\n";
	}

	function fiscalyears_list_cells($label, $name, $selected_id = null)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo fiscalyears_list($name, $selected_id);
		echo "</td>\n";
	}

	function fiscalyears_list_row($label, $name, $selected_id = null)
	{
		echo "<tr><td class='label'>$label</td>";
		fiscalyears_list_cells(null, $name, $selected_id);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------
	function dimensions_list($name, $selected_id = null, $no_option = false, $showname = ' ', $submit_on_change = false, $showclosed = false, $showtype = 1)
	{
		$sql = "SELECT id, CONCAT(reference,'  ',name) as ref FROM dimensions";
		$options = array(
			'order' => 'reference', 'spec_option' => $no_option ? $showname : false, 'spec_id' => 0, 'select_submit' => $submit_on_change, 'async' => false);
		if (!$showclosed) {
			$options['where'][] = "closed=0";
		}
		if ($showtype) {
			$options['where'][] = "type_=$showtype";
		}
		return combo_input($name, $selected_id, $sql, 'id', 'ref', $options);
	}

	function dimensions_list_cells($label, $name, $selected_id = null, $no_option = false, $showname = null, $showclosed = false, $showtype = 0, $submit_on_change = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo dimensions_list($name, $selected_id, $no_option, $showname, $submit_on_change, $showclosed, $showtype);
		echo "</td>\n";
	}

	function dimensions_list_row($label, $name, $selected_id = null, $no_option = false, $showname = null, $showclosed = false, $showtype = 0, $submit_on_change = false)
	{
		echo "<tr><td class='label'>$label</td>";
		dimensions_list_cells(null, $name, $selected_id, $no_option, $showname, $showclosed, $showtype, $submit_on_change);
		echo "</tr>\n";
	}

	function stock_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false, $opts = array(), $editkey = false, $legacy = false)
	{
		if (!$legacy) {
			return Item::addSearchBox($name, array_merge(array(
				'submitonselect' => $submit_on_change, 'selected' => $selected_id, 'purchase' => true, 'cells' => true), $opts));
		}
		$sql = "SELECT stock_id, s.description, c.description, s.inactive, s.editable, s.long_description
			FROM stock_master s,stock_category c WHERE s.category_id=c.category_id";
		if ($editkey) {
			set_editor('item', $name, $editkey);
		}
		return combo_input($name, $selected_id, $sql, 'stock_id', 's.description', array_merge(array(
			'format' => '_format_stock_items',
			'spec_option' => $all_option === true ? _("All Items") : $all_option,
			'spec_id' => ALL_TEXT,
			'search_box' => false,
			'search' => array("stock_id", "c.description", "s.description"),
			'search_submit' => DB_Company::get_pref('no_item_list') != 0,
			'size' => 10,
			'select_submit' => $submit_on_change,
			'category' => 2,
			'order' => array('c.description', 'stock_id'),
			'editable' => 30,
			'max' => 50), $opts));
	}

	function _format_stock_items($row)
	{
		return (User::show_codes() ? ($row[0] . "&nbsp;-&nbsp;") : "") . $row[1];
	}

	function stock_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false, $legacy = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
			'submitonselect' => $submit_on_change, 'cells' => true, 'purchase' => false, 'show_inactive' => $all, 'editable' => $editkey), $editkey, $legacy);
	}

	//---------------------------------------------------------------------------------------------------
	//
	// Select item via foreign code.
	//
	function sales_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false, $type = '', $opts = array(), $legacy = false)
	{
		// all sales codes
		if (!$legacy) {
			return Item::addSearchBox($name, array_merge(array(
				'selected' => $selected_id, 'type' => $type, 'cells' => true, 'sale' => true), $opts));
		}
		$where = ($type == 'local') ? " AND !i.is_foreign" : ' ';
		if ($type == 'kits') {
			$where .= " AND !i.is_foreign AND i.item_code!=i.stock_id ";
		}
		$sql = "SELECT i.item_code, i.description, c.description, count(*)>1 as kit,
			 i.inactive, if(count(*)>1, '0', s.editable) as editable, s.long_description
			FROM stock_master s, item_codes i LEFT JOIN stock_category c ON i.category_id=c.category_id
			WHERE i.stock_id=s.stock_id $where AND !i.inactive AND !s.inactive AND !s.no_sale GROUP BY i.item_code";
		return combo_input($name, $selected_id, $sql, 'i.item_code', 'c.description', array_merge(array(
			'format' => '_format_stock_items',
			'spec_option' => $all_option === true ? _("All Items") : $all_option,
			'spec_id' => ALL_TEXT,
			'search_box' => true,
			'search' => array("i.item_code", "c.description", "i.description"),
			'search_submit' => DB_Company::get_pref('no_item_list') != 0,
			'size' => 15,
			'select_submit' => $submit_on_change,
			'category' => 2,
			'order' => array('c.description', 'i.item_code'),
			'editable' => 30,
			'max' => 50), $opts));
	}

	function sales_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $opts)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo sales_items_list($name, $selected_id, $all_option, $submit_on_change, '', array_merge(array(
			'cells' => true, 'description' => ''), $opts));
	}

	function sales_kits_list($name, $selected_id = null, $all_option = false, $submit_on_change = false, $legacy = true)
	{
		return sales_items_list($name, $selected_id, $all_option, $submit_on_change, 'kits', array('cells' => false), $legacy);
	}

	function sales_local_items_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $legacy = true)
	{
		echo "<tr>";
		if ($label != null) {
			echo "<td class='label'>$label</td>\n<td>";
		}
		echo sales_items_list($name, $selected_id, $all_option, $submit_on_change, 'local', array('cells' => false), $legacy);
		echo "</td></tr>";
	}

	//------------------------------------------------------------------------------------
	function stock_manufactured_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		return stock_items_list($name, $selected_id, $all_option, $submit_on_change, array('where' => array("mb_flag= '" . STOCK_MANUFACTURE . "'")));
	}

	function stock_manufactured_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo stock_manufactured_items_list($name, $selected_id, $all_option, $submit_on_change, array('cells' => true));
		echo "\n";
	}

	function stock_manufactured_items_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		echo "<tr><td class='label'>$label</td>";
		stock_manufactured_items_list_cells(null, $name, $selected_id, $all_option, $submit_on_change);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------
	function stock_component_items_list($name, $parent_stock_id, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false)
	{
		return stock_items_list($name, $selected_id, $all_option, $submit_on_change, array('where' => " stock_id != '$parent_stock_id' "));
	}

	function stock_component_items_list_cells($label, $name, $parent_stock_id, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
			'where' => "stock_id != '$parent_stock_id'", 'cells' => true));
	}

	//------------------------------------------------------------------------------------
	function stock_costable_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		return stock_items_list($name, $selected_id, $all_option, $submit_on_change, array('where' => "mb_flag!='" . STOCK_SERVICE . "'"));
	}

	function stock_costable_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
			'where' => "mb_flag!='" . STOCK_SERVICE . "'", 'cells' => true, 'description' => ''));
	}

	//------------------------------------------------------------------------------------
	function stock_purchasable_items_list($name, $selected_id = null, $all_option = false, $submit_on_change = false, $all = false, $editkey = false, $legacy = false)
	{
		return stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
			'where' => "mb_flag!= '" . STOCK_MANUFACTURE . "'", 'show_inactive' => $all, 'editable' => false), false, $legacy);
	}

	function stock_purchasable_items_list_cells($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo stock_items_list($name, $selected_id, $all_option, $submit_on_change, array(
			'where' => "mb_flag!= '" . STOCK_MANUFACTURE . "'", 'editable' => 30, 'cells' => true, 'description' => ''));
	}

	function stock_purchasable_items_list_row($label, $name, $selected_id = null, $all_option = false, $submit_on_change = false, $editkey = false)
	{
		echo "<tr><td class='label'>$label</td>";
		stock_purchasable_items_list_cells(null, $name, $selected_id = null, $all_option, $submit_on_change, $editkey);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------
	function stock_item_types_list_row($label, $name, $selected_id = null, $enabled = true)
	{
		global $stock_types;
		echo "<tr>";
		if ($label != null) {
			echo "<td class='label'>$label</td>\n";
		}
		echo "<td>";
		echo array_selector($name, $selected_id, $stock_types, array(
			'select_submit' => true, 'disabled' => !$enabled));
		echo "</td></tr>\n";
	}

	function stock_units_list_row($label, $name, $value = null, $enabled = true)
	{
		$result = Item_Unit::get_all();
		echo "<tr>";
		if ($label != null) {
			echo "<td class='label'>$label</td>\n";
		}
		echo "<td>";
		while ($unit = DB::fetch($result)) {
			$units[$unit['abbr']] = $unit['name'];
		}
		echo array_selector($name, $value, $units, array('disabled' => !$enabled));
		echo "</td></tr>\n";
	}

	//---------------------------------------------------------------------------------------------------------
	function tax_types_list($name, $selected_id = null, $none_option = false, $submit_on_change = false)
	{
		$sql = "SELECT id, CONCAT(name, ' (',rate,'%)') as name FROM tax_types";
		return combo_input($name, $selected_id, $sql, 'id', 'name', array(
			'spec_option' => $none_option, 'spec_id' => ALL_NUMERIC, 'select_submit' => $submit_on_change, 'async' => false,));
	}

	function tax_types_list_cells($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo tax_types_list($name, $selected_id, $none_option, $submit_on_change);
		echo "</td>\n";
	}

	function tax_types_list_row($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false)
	{
		echo "<tr><td class='label'>$label</td>";
		tax_types_list_cells(null, $name, $selected_id, $none_option, $submit_on_change);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------
	function tax_groups_list($name, $selected_id = null, $none_option = false, $submit_on_change = false)
	{
		$sql = "SELECT id, name FROM tax_groups";
		return combo_input($name, $selected_id, $sql, 'id', 'name', array(
			'order' => 'id', 'spec_option' => $none_option, 'spec_id' => ALL_NUMERIC, 'select_submit' => $submit_on_change, 'async' => false,));
	}

	function tax_groups_list_cells($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo tax_groups_list($name, $selected_id, $none_option, $submit_on_change);
		echo "</td>\n";
	}

	function tax_groups_list_row($label, $name, $selected_id = null, $none_option = false, $submit_on_change = false)
	{
		echo "<tr><td class='label'>$label</td>";
		tax_groups_list_cells(null, $name, $selected_id, $none_option, $submit_on_change);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------
	function item_tax_types_list($name, $selected_id = null)
	{
		$sql = "SELECT id, name FROM item_tax_types";
		return combo_input($name, $selected_id, $sql, 'id', 'name', array('order' => 'id'));
	}

	function item_tax_types_list_cells($label, $name, $selected_id = null)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo item_tax_types_list($name, $selected_id);
		echo "</td>\n";
	}

	function item_tax_types_list_row($label, $name, $selected_id = null)
	{
		echo "<tr><td class='label'>$label</td>";
		item_tax_types_list_cells(null, $name, $selected_id);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------
	function shippers_list($name, $selected_id = null)
	{
		$sql = "SELECT shipper_id, shipper_name, inactive FROM shippers";
		return combo_input($name, $selected_id, $sql, 'shipper_id', 'shipper_name', array('order' => array('shipper_name')));
	}

	function shippers_list_cells($label, $name, $selected_id = null)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo shippers_list($name, $selected_id);
		echo "</td>\n";
	}

	function shippers_list_row($label, $name, $selected_id = null)
	{
		echo "<tr><td class='label'>$label</td>";
		shippers_list_cells(null, $name, $selected_id);
		echo "</tr>\n";
	}

	//-------------------------------------------------------------------------------------
	function sales_persons_list($name, $selected_id = null, $spec_opt = false)
	{
		$sql = "SELECT salesman_code, salesman_name, inactive FROM salesman";
		return combo_input($name, $selected_id, $sql, 'salesman_code', 'salesman_name', array(
			'order' => array('salesman_name'), 'spec_option' => $spec_opt, 'spec_id' => ALL_NUMERIC));
	}

	function sales_persons_list_cells($label, $name, $selected_id = null, $spec_opt = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>\n";
		echo sales_persons_list($name, $selected_id, $spec_opt);
		echo "</td>\n";
	}

	function sales_persons_list_row($label, $name, $selected_id = null, $spec_opt = false)
	{
		echo "<tr><td class='label'>$label</td>";
		sales_persons_list_cells(null, $name, $selected_id, $spec_opt);
		echo "</tr>\n";
	}

	//-------------------------------------------------------------------------------------
	function user_list($name, $selected_id = null, $spec_opt = false)
	{
		$sql = "SELECT id, real_name, inactive FROM users";
		return combo_input($name, $selected_id, $sql, 'id', 'real_name', array(
			'order' => array('real_name'), 'spec_option' => $spec_opt, 'spec_id' => ALL_NUMERIC));
	}

	function user_list_cells($label, $name, $selected_id = null, $spec_opt = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>\n";
		echo user_list($name, $selected_id, $spec_opt);
		echo "</td>\n";
	}

	function user_list_row($label, $name, $selected_id = null, $spec_opt = false)
	{
		echo "<tr><td class='label'>$label</td>";
		user_list_cells(null, $name, $selected_id, $spec_opt);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------
	function sales_areas_list($name, $selected_id = null)
	{
		$sql = "SELECT area_code, description, inactive FROM areas";
		return combo_input($name, $selected_id, $sql, 'area_code', 'description', array());
	}

	function sales_areas_list_cells($label, $name, $selected_id = null)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo sales_areas_list($name, $selected_id);
		echo "</td>\n";
	}

	function sales_areas_list_row($label, $name, $selected_id = null)
	{
		echo "<tr><td class='label'>$label</td>";
		sales_areas_list_cells(null, $name, $selected_id);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------
	function sales_groups_list($name, $selected_id = null, $special_option = false)
	{
		$sql = "SELECT id, description, inactive FROM groups";
		return combo_input($name, $selected_id, $sql, 'id', 'description', array(
			'spec_option' => $special_option === true ? ' ' : $special_option, 'order' => 'description', 'spec_id' => 0,));
	}

	function sales_groups_list_cells($label, $name, $selected_id = null, $special_option = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo sales_groups_list($name, $selected_id, $special_option);
		echo "</td>\n";
	}

	function sales_groups_list_row($label, $name, $selected_id = null, $special_option = false)
	{
		echo "<tr><td class='label'>$label</td>";
		sales_groups_list_cells(null, $name, $selected_id, $special_option);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------
	function _format_template_items($row)
	{
		return ($row[0] . "&nbsp;- &nbsp;" . _("Amount") . "&nbsp;" . $row[1]);
	}

	function templates_list($name, $selected_id = null, $special_option = false)
	{
		$sql = "SELECT sorder.order_no,	Sum(line.unit_price*line.quantity*(1-line.discount_percent)) AS OrderValue
		FROM sales_orders as sorder, sales_order_details as line
		WHERE sorder.order_no = line.order_no AND sorder.type = 1 GROUP BY line.order_no";
		return combo_input($name, $selected_id, $sql, 'order_no', 'OrderValue', array(
			'format' => '_format_template_items', 'spec_option' => $special_option === true ? ' ' : $special_option, 'order' => 'order_no', 'spec_id' => 0,));
	}

	function templates_list_cells($label, $name, $selected_id = null, $special_option = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo templates_list($name, $selected_id, $special_option);
		echo "</td>\n";
	}

	function templates_list_row($label, $name, $selected_id = null, $special_option = false)
	{
		echo "<tr><td class='label'>$label</td>";
		templates_list_cells(null, $name, $selected_id, $special_option);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------
	function workorders_list($name, $selected_id = null)
	{
		$sql = "SELECT id, wo_ref FROM workorders WHERE closed=0";
		return combo_input($name, $selected_id, $sql, 'id', 'wo_ref', array());
	}

	function workorders_list_cells($label, $name, $selected_id = null)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo workorders_list($name, $selected_id);
		echo "</td>\n";
	}

	function workorders_list_row($label, $name, $selected_id = null)
	{
		echo "<tr><td class='label'>$label</td>";
		workorders_list_cells(null, $name, $selected_id);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------
	function payment_terms_list($name, $selected_id = null, $disabled = null)
	{
		if ($disabled === null) {
			$disabled = (!$_SESSION['current_user']->can_access('SA_CUSTOMER_CREDIT'));
		}
		$sql = "SELECT terms_indicator, terms, inactive FROM payment_terms";
		return combo_input($name, $selected_id, $sql, 'terms_indicator', 'terms_indicator', array('disabled' => $disabled));
	}

	function payment_terms_list_cells($label, $name, $selected_id = null, $disabled = null)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo payment_terms_list($name, $selected_id, $disabled);
		echo "</td>\n";
	}

	function payment_terms_list_row($label, $name, $selected_id = null, $disabled = null)
	{
		echo "<tr><td class='label'>$label</td>";
		payment_terms_list_cells(null, $name, $selected_id, $disabled);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------
	function credit_status_list($name, $selected_id = null, $disabled = null)
	{
		if ($disabled === null) {
			$disabled = (!$_SESSION['current_user']->can_access('SA_CUSTOMER_CREDIT'));
		}
		$sql = "SELECT id, reason_description, inactive FROM credit_status";
		return combo_input($name, $selected_id, $sql, 'id', 'reason_description', array('disabled' => $disabled));
	}

	function credit_status_list_cells($label, $name, $selected_id = null, $disabled = null)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo credit_status_list($name, $selected_id, $disabled);
		echo "</td>\n";
	}

	function credit_status_list_row($label, $name, $selected_id = null, $disabled = null)
	{
		echo "<tr><td class='label'>$label</td>";
		credit_status_list_cells(null, $name, $selected_id, $disabled);
		echo "</tr>\n";
	}

	//-----------------------------------------------------------------------------------------------
	function sales_types_list($name, $selected_id = null, $submit_on_change = false, $special_option = false)
	{
		$sql = "SELECT id, sales_type, inactive FROM sales_types";
		return combo_input($name, $selected_id, $sql, 'id', 'sales_type', array(
			'spec_option' => $special_option === true ? _("All Sales Types") : $special_option, 'spec_id' => 0, 'select_submit' => $submit_on_change, //	  'async' => false,
		));
	}

	function sales_types_list_cells($label, $name, $selected_id = null, $submit_on_change = false, $special_option = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo sales_types_list($name, $selected_id, $submit_on_change, $special_option);
		echo "</td>\n";
	}

	function sales_types_list_row($label, $name, $selected_id = null, $submit_on_change = false, $special_option = false)
	{
		echo "<tr><td class='label'>$label</td>";
		sales_types_list_cells(null, $name, $selected_id, $submit_on_change, $special_option);
		echo "</tr>\n";
	}

	//-----------------------------------------------------------------------------------------------
	function movement_types_list($name, $selected_id = null)
	{
		$sql = "SELECT id, name FROM movement_types";
		return combo_input($name, $selected_id, $sql, 'id', 'name', array());
	}

	function movement_types_list_cells($label, $name, $selected_id = null)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo movement_types_list($name, $selected_id);
		echo "</td>\n";
	}

	function movement_types_list_row($label, $name, $selected_id = null)
	{
		echo "<tr><td class='label'>$label</td>";
		movement_types_list_cells(null, $name, $selected_id);
		echo "</tr>\n";
	}

	//-----------------------------------------------------------------------------------------------
	function _format_date($row)
	{
		return Dates::sql2date($row['reconciled']);
	}

	function bank_reconciliation_list($account, $name, $selected_id = null, $submit_on_change = false, $special_option = false)
	{
		$sql = "SELECT reconciled, reconciled FROM bank_trans
		WHERE bank_act=" . DB::escape($account) . " AND reconciled IS NOT NULL
		GROUP BY reconciled";
		return combo_input($name, $selected_id, $sql, 'id', 'reconciled', array(
			'spec_option' => $special_option, 'format' => '_format_date', 'spec_id' => '', 'select_submit' => $submit_on_change, 'order' => 'reconciled DESC'));
	}

	function bank_reconciliation_list_cells($label, $account, $name, $selected_id = null, $submit_on_change = false, $special_option = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo bank_reconciliation_list($account, $name, $selected_id, $submit_on_change, $special_option);
		echo "</td>\n";
	}

	/*
	 function bank_reconciliation_list_row($label, $account, $name, $selected_id=null, $submit_on_change=false, $special_option=false)
	 {
		 echo "<tr>\n";
		 bank_reconciliation_list_cells($label, $account, $name, $selected_id, $submit_on_change, $special_option);
		 echo "</tr>\n";
	 }
	 */
	//-----------------------------------------------------------------------------------------------
	function workcenter_list($name, $selected_id = null, $all_option = false)
	{
		$sql = "SELECT id, name, inactive FROM workcentres";
		return combo_input($name, $selected_id, $sql, 'id', 'name', array(
			'spec_option' => $all_option === true ? _("All Suppliers") : $all_option, 'spec_id' => ALL_TEXT,));
	}

	function workcenter_list_cells($label, $name, $selected_id = null, $all_option = false)
	{
		JS::default_focus($name);
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo workcenter_list($name, $selected_id, $all_option);
		echo "</td>\n";
	}

	function workcenter_list_row($label, $name, $selected_id = null, $all_option = false)
	{
		echo "<tr><td class='label'>$label</td>";
		workcenter_list_cells(null, $name, $selected_id, $all_option);
		echo "</tr>\n";
	}

	//-----------------------------------------------------------------------------------------------
	function bank_accounts_list($name, $selected_id = null, $submit_on_change = false)
	{
		$sql = "SELECT bank_accounts.id, bank_account_name, bank_curr_code, inactive
		FROM bank_accounts";
		return combo_input($name, $selected_id, $sql, 'id', 'bank_account_name', array(
			'format' => '_format_add_curr', 'select_submit' => $submit_on_change, 'async' => false));
	}

	function bank_accounts_list_cells($label, $name, $selected_id = null, $submit_on_change = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo bank_accounts_list($name, $selected_id, $submit_on_change);
		echo "</td>\n";
	}

	function bank_accounts_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		echo "<tr><td class='label'>$label</td>";
		bank_accounts_list_cells(null, $name, $selected_id, $submit_on_change);
		echo "</tr>\n";
	}

	//-----------------------------------------------------------------------------------------------
	function cash_accounts_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		$sql = "SELECT bank_accounts.id, bank_account_name, bank_curr_code, inactive
		FROM bank_accounts
		WHERE bank_accounts.account_type=3";
		if ($label != null) {
			echo "<tr><td class='label'>$label</td>\n";
		}
		echo "<td>";
		echo combo_input($name, $selected_id, $sql, 'id', 'bank_account_name', array(
			'format' => '_format_add_curr', 'select_submit' => $submit_on_change, 'async' => true));
		echo "</td></tr>\n";
	}

	//-----------------------------------------------------------------------------------------------
	function pos_list_row($label, $name, $selected_id = null, $spec_option = false, $submit_on_change = false)
	{
		$sql = "SELECT id, pos_name, inactive FROM sales_pos";
		JS::default_focus($name);
		echo '<tr>';
		if ($label != null) {
			echo "<td class='label'>$label</td>\n";
		}
		echo "<td>";
		echo combo_input($name, $selected_id, $sql, 'id', 'pos_name', array(
			'select_submit' => $submit_on_change, 'async' => true, 'spec_option' => $spec_option, 'spec_id' => -1, 'order' => array('pos_name')));
		echo "</td></tr>\n";
	}

	//-----------------------------------------------------------------------------------------------
	function sale_payment_list_cells($label, $name, $selected_id = null, $submit_on_change = false)
	{
		if ($label != null) {
			echo "<td class='label'>$label</td>\n";
		}
		echo "<td>";
		echo yesno_list($name, $selected_id, _('Cash'), _('Delayed'), $submit_on_change);
		echo "</td>\n";
	}

	//-----------------------------------------------------------------------------------------------
	function class_list($name, $selected_id = null, $submit_on_change = false)
	{
		$sql = "SELECT cid, class_name FROM chart_class";
		return combo_input($name, $selected_id, $sql, 'cid', 'class_name', array(
			'select_submit' => $submit_on_change, 'async' => false));
	}

	function class_list_cells($label, $name, $selected_id = null, $submit_on_change = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo class_list($name, $selected_id, $submit_on_change);
		echo "</td>\n";
	}

	function class_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		echo "<tr><td class='label'>$label</td>";
		class_list_cells(null, $name, $selected_id, $submit_on_change);
		echo "</tr>\n";
	}

	//-----------------------------------------------------------------------------------------------
	function stock_categories_list($name, $selected_id = null, $spec_opt = false, $submit_on_change = false)
	{
		$sql = "SELECT category_id, description, inactive FROM stock_category";
		return combo_input($name, $selected_id, $sql, 'category_id', 'description', array(
			'order' => 'category_id', 'spec_option' => $spec_opt, 'spec_id' => -1, 'select_submit' => $submit_on_change, 'async' => true));
	}

	function stock_categories_list_cells($label, $name, $selected_id = null, $spec_opt = false, $submit_on_change = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo stock_categories_list($name, $selected_id, $spec_opt, $submit_on_change);
		echo "</td>\n";
	}

	function stock_categories_list_row($label, $name, $selected_id = null, $spec_opt = false, $submit_on_change = false)
	{
		echo "<tr><td class='label'>$label</td>";
		stock_categories_list_cells(null, $name, $selected_id, $spec_opt, $submit_on_change);
		echo "</tr>\n";
	}

	//-----------------------------------------------------------------------------------------------
	function gl_account_types_list($name, $selected_id = null, $all_option = false, $all_option_numeric = true)
	{
		$sql = "SELECT id, name FROM chart_types";
		return combo_input($name, $selected_id, $sql, 'id', 'name', array(
			'order' => 'id', 'spec_option' => $all_option, 'spec_id' => $all_option_numeric ? 0 : ALL_TEXT));
	}

	function gl_account_types_list_cells($label, $name, $selected_id = null, $all_option = false, $all_option_numeric = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo gl_account_types_list($name, $selected_id, $all_option, $all_option_numeric);
		echo "</td>\n";
	}

	function gl_account_types_list_row($label, $name, $selected_id = null, $all_option = false, $all_option_numeric = false)
	{
		echo "<tr><td class='label'>$label</td>";
		gl_account_types_list_cells(null, $name, $selected_id, $all_option, $all_option_numeric);
		echo "</tr>\n";
	}

	//-----------------------------------------------------------------------------------------------
	function gl_all_accounts_list($name, $selected_id = null, $skip_bank_accounts = false, $cells = false, $all_option = false, $submit_on_change = false, $all = false)
	{
		if ($skip_bank_accounts) {
			$sql = "SELECT chart.account_code, chart.account_name, type.name, chart.inactive, type.id
			FROM (chart_master chart,chart_types type) LEFT JOIN bank_accounts acc ON chart.account_code=acc.account_code
				WHERE acc.account_code  IS NULL
			AND chart.account_type=type.id";
		} else {
			$sql = "SELECT chart.account_code, chart.account_name, type.name, chart.inactive, type.id
			FROM chart_master chart,chart_types type
			WHERE chart.account_type=type.id";
		}
		return combo_input($name, $selected_id, $sql, 'chart.account_code', 'chart.account_name', array(
			'format' => '_format_account',
			'type' => 2,
			'spec_option' => $all_option === true ? _("Use Item Sales Accounts") : $all_option,
			'spec_id' => '',
			'order' => array('type.id', 'account_code'),
			'search_box' => $cells,
			'search_submit' => false,
			'size' => 12,
			'max' => 10,
			'cells' => true,
			'select_submit' => $submit_on_change,
			'async' => false,
			'category' => 2,
			'show_inactive' => $all));
	}

	function _format_account($row)
	{
		return $row[0] . "&nbsp;&nbsp;&nbsp;&nbsp;" . $row[1];
	}

	function gl_all_accounts_list_cells($label, $name, $selected_id = null, $skip_bank_accounts = false, $cells = false, $all_option = false, $submit_on_change = false, $all = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo gl_all_accounts_list($name, $selected_id, $skip_bank_accounts, $cells, $all_option, $submit_on_change, $all);
		echo "</td>\n";
	}

	function gl_all_accounts_list_row($label, $name, $selected_id = null, $skip_bank_accounts = false, $cells = false, $all_option = false)
	{
		echo "<tr><td class='label'>$label</td>";
		gl_all_accounts_list_cells(null, $name, $selected_id, $skip_bank_accounts, $cells, $all_option);
		echo "</tr>\n";
	}

	function yesno_list($name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false)
	{
		$items = array();
		$items['0'] = strlen($name_no) ? $name_no : _("No");
		$items['1'] = strlen($name_yes) ? $name_yes : _("Yes");
		return array_selector($name, $selected_id, $items, array(
			'select_submit' => $submit_on_change, 'async' => false)); // FIX?
	}

	function yesno_list_cells($label, $name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo yesno_list($name, $selected_id, $name_yes, $name_no, $submit_on_change);
		echo "</td>\n";
	}

	function yesno_list_row($label, $name, $selected_id = null, $name_yes = "", $name_no = "", $submit_on_change = false)
	{
		echo "<tr><td class='label'>$label</td>";
		yesno_list_cells(null, $name, $selected_id, $name_yes, $name_no, $submit_on_change);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------------------
	function languages_list($name, $selected_id = null)
	{
		$items = array();
		$langs = Config::get('languages.installed');
		foreach ($langs as $lang) {
			$items[$lang['code']] = $lang['name'];
		}
		return array_selector($name, $selected_id, $items);
	}

	function languages_list_cells($label, $name, $selected_id = null)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo languages_list($name, $selected_id);
		echo "</td>\n";
	}

	function languages_list_row($label, $name, $selected_id = null)
	{
		echo "<tr><td class='label'>$label</td>";
		languages_list_cells(null, $name, $selected_id);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------------------
	function bank_account_types_list($name, $selected_id = null)
	{
		global $bank_account_types;
		return array_selector($name, $selected_id, $bank_account_types);
	}

	function bank_account_types_list_cells($label, $name, $selected_id = null)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo bank_account_types_list($name, $selected_id);
		echo "</td>\n";
	}

	function bank_account_types_list_row($label, $name, $selected_id = null)
	{
		echo "<tr><td class='label'>$label</td>";
		bank_account_types_list_cells(null, $name, $selected_id);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------------------
	function payment_person_types_list($name, $selected_id = null, $submit_on_change = false)
	{
		global $payment_person_types;
		$items = array();
		foreach ($payment_person_types as $key => $type) {
			if ($key != PT_WORKORDER) {
				$items[$key] = $type;
			}
		}
		return array_selector($name, $selected_id, $items, array('select_submit' => $submit_on_change));
	}

	function payment_person_types_list_cells($label, $name, $selected_id = null, $related = null)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo payment_person_types_list($name, $selected_id, $related);
		echo "</td>\n";
	}

	function payment_person_types_list_row($label, $name, $selected_id = null, $related = null)
	{
		echo "<tr><td class='label'>$label</td>";
		payment_person_types_list_cells(null, $name, $selected_id, $related);
		echo "</tr>\n";
	}

	//------------------------------------------------------------------------------------------------
	function wo_types_list($name, $selected_id = null)
	{
		global $wo_types_array;
		return array_selector($name, $selected_id, $wo_types_array, array(
			'select_submit' => true, 'async' => true));
	}

	function wo_types_list_row($label, $name, $selected_id = null)
	{
		echo "<tr><td class='label'>$label</td><td>\n";
		echo wo_types_list($name, $selected_id);
		echo "</td></tr>\n";
	}

	//------------------------------------------------------------------------------------------------
	function dateformats_list_row($label, $name, $value = null)
	{
		echo "<tr><td class='label'>$label</td>\n<td>";
		echo array_selector($name, $value, Config::get('formats_date'));
		echo "</td></tr>\n";
	}

	function dateseps_list_row($label, $name, $value = null)
	{
		echo "<tr><td class='label'>$label</td>\n<td>";
		echo array_selector($name, $value, Config::get('separators_date'));
		echo "</td></tr>\n";
	}

	function thoseps_list_row($label, $name, $value = null)
	{
		echo "<tr><td class='label'>$label</td>\n<td>";
		echo array_selector($name, $value, Config::get('separators_thousands'));
		echo "</td></tr>\n";
	}

	function decseps_list_row($label, $name, $value = null)
	{
		echo "<tr><td class='label'>$label</td>\n<td>";
		echo array_selector($name, $value, Config::get('separators_decimal'));
		echo "</td></tr>\n";
	}

	function themes_list_row($label, $name, $value = null)
	{
		$themes = array();
		$themedir = opendir(THEME_PATH);
		while (false !== ($fname = readdir($themedir))) {
			if ($fname != '.' && $fname != '..' && $fname != 'CVS' && is_dir(THEME_PATH . $fname)) {
				$themes[$fname] = $fname;
			}
		}
		ksort($themes);
		echo "<tr><td class='label'>$label</td>\n<td>";
		echo array_selector($name, $value, $themes);
		echo "</td></tr>\n";
	}

	function pagesizes_list_row($label, $name, $value = null)
	{
		$items = array();
		foreach (Config::get('formats_paper_size') as $pz) {
			$items[$pz] = $pz;
		}
		echo "<tr><td class='label'>$label</td>\n<td>";
		echo array_selector($name, $value, $items);
		echo "</td></tr>\n";
	}

	function systypes_list($name, $value = null, $spec_opt = false, $submit_on_change = false)
	{
		global $systypes_array;
		return array_selector($name, $value, $systypes_array, array(
			'spec_option' => $spec_opt, 'spec_id' => ALL_NUMERIC, 'select_submit' => $submit_on_change, 'async' => false,));
	}

	function systypes_list_cells($label, $name, $value = null, $submit_on_change = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo systypes_list($name, $value, false, $submit_on_change);
		echo "</td>\n";
	}

	function systypes_list_row($label, $name, $value = null, $submit_on_change = false)
	{
		echo "<tr><td class='label'>$label</td>";
		systypes_list_cells(null, $name, $value, $submit_on_change);
		echo "</tr>\n";
	}

	function journal_types_list_cells($label, $name, $value = null, $submit_on_change = false)
	{
		global $systypes_array;
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		$items = $systypes_array;
		// exclude quotes, orders and dimensions
		foreach (array(ST_PURCHORDER, ST_WORKORDER, ST_SALESORDER, ST_DIMENSION, ST_SALESQUOTE) as $excl) {
			unset($items[$excl]);
		}
		echo array_selector($name, $value, $items, array(
			'spec_option' => _("All"), 'spec_id' => -1, 'select_submit' => $submit_on_change, 'async' => false));
		echo "</td>\n";
	}

	function cust_allocations_list_cells($label, $name, $selected = null)
	{
		if ($label != null) {
			label_cell($label);
		}
		echo "<td>\n";
		$allocs = array(
			ALL_TEXT => _("All Types"), '1' => _("Sales Invoices"), '2' => _("Overdue Invoices"), '3' => _("Payments"), '4' => _("Credit Notes"), '5' => _("Delivery Notes"), '6' => _("Invoices Only"));
		echo array_selector($name, $selected, $allocs);
		echo "</td>\n";
	}

	function supp_allocations_list_cell($name, $selected = null)
	{
		echo "<td>\n";
		$allocs = array(
			ALL_TEXT => _("All Types"), '1' => _("Invoices"), '2' => _("Overdue Invoices"), '6' => _("Unpaid Invoices"), '3' => _("Payments"), '4' => _("Credit Notes"), '5' => _("Overdue Credit Notes"));
		echo array_selector($name, $selected, $allocs);
		echo "</td>\n";
	}

	function policy_list_cells($label, $name, $selected = null)
	{
		if ($label != null) {
			label_cell($label);
		}
		echo "<td>\n";
		echo array_selector($name, $selected, array(
			'' => _("Automatically put balance on back order"), 'CAN' => _("Cancel any quantites not delivered")));
		echo "</td>\n";
	}

	function policy_list_row($label, $name, $selected = null)
	{
		echo "<tr><td class='label'>$label</td>";
		policy_list_cells(null, $name, $selected);
		echo "</tr>\n";
	}

	function credit_type_list_cells($label, $name, $selected = null, $submit_on_change = false)
	{
		if ($label != null) {
			label_cell($label);
		}
		echo "<td>\n";
		echo array_selector($name, $selected, array(
			'Return' => _("Items Returned to Inventory Location"), 'WriteOff' => _("Items Written Off")), array('select_submit' => $submit_on_change));
		echo "</td>\n";
	}

	function credit_type_list_row($label, $name, $selected = null, $submit_on_change = false)
	{
		echo "<tr><td class='label'>$label</td>";
		credit_type_list_cells(null, $name, $selected, $submit_on_change);
		echo "</tr>\n";
	}

	function number_list($name, $selected, $from, $to, $no_option = false)
	{
		$items = array();
		for ($i = $from; $i <= $to; $i++) {
			$items[$i] = "$i";
		}
		return array_selector($name, $selected, $items, array(
			'spec_option' => $no_option, 'spec_id' => ALL_NUMERIC));
	}

	function number_list_cells($label, $name, $selected, $from, $to, $no_option = false)
	{
		if ($label != null) {
			label_cell($label);
		}
		echo "<td>\n";
		echo number_list($name, $selected, $from, $to, $no_option);
		echo "</td>\n";
	}

	function number_list_row($label, $name, $selected, $from, $to, $no_option = false)
	{
		echo "<tr><td class='label'>$label</td>";
		echo number_list_cells(null, $name, $selected, $from, $to, $no_option);
		echo "</tr>\n";
	}

	function print_profiles_list_row($label, $name, $selected_id = null, $spec_opt = false, $submit_on_change = true)
	{
		$sql = "SELECT profile FROM print_profiles GROUP BY profile";
		$result = DB::query($sql, 'cannot get all profile names');
		$profiles = array();
		while ($myrow = DB::fetch($result)) {
			$profiles[$myrow['profile']] = $myrow['profile'];
		}
		echo "<tr>";
		if ($label != null) {
			echo "<td class='label'>$label</td>\n";
		}
		echo "<td>";
		echo array_selector($name, $selected_id, $profiles, array(
			'select_submit' => $submit_on_change, 'spec_option' => $spec_opt, 'spec_id' => ''));
		echo "</td></tr>\n";
	}

	function printers_list($name, $selected_id = null, $spec_opt = false, $submit_on_change = false)
	{
		static $printers; // query only once for page display
		if (!$printers) {
			$sql = "SELECT id, name, description FROM printers";
			$result = DB::query($sql, 'cannot get all printers');
			$printers = array();
			while ($myrow = DB::fetch($result)) {
				$printers[$myrow['id']] = $myrow['name'] . '&nbsp;-&nbsp;' . $myrow['description'];
			}
		}
		return array_selector($name, $selected_id, $printers, array(
			'select_submit' => $submit_on_change, 'spec_option' => $spec_opt, 'spec_id' => ''));
	}

	//------------------------------------------------------------------------------------------------
	function quick_entries_list($name, $selected_id = null, $type = null, $submit_on_change = false)
	{
		$where = false;
		$sql = "SELECT id, description FROM quick_entries";
		if ($type != null) {
			$sql .= " WHERE type=$type";
		}
		return combo_input($name, $selected_id, $sql, 'id', 'description', array(
			'spec_id' => '', 'order' => 'description', 'select_submit' => $submit_on_change, 'async' => false));
	}

	function quick_entries_list_cells($label, $name, $selected_id = null, $type, $submit_on_change = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo quick_entries_list($name, $selected_id, $type, $submit_on_change);
		echo "</td>";
	}

	function quick_entries_list_row($label, $name, $selected_id = null, $type, $submit_on_change = false)
	{
		echo "<tr><td class='label'>$label</td>";
		quick_entries_list_cells(null, $name, $selected_id, $type, $submit_on_change);
		echo "</tr>\n";
	}

	function quick_actions_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		global $quick_actions;
		echo "<tr><td class='label'>$label</td><td>";
		echo array_selector($name, $selected_id, $quick_actions, array('select_submit' => $submit_on_change));
		echo "</td></tr>\n";
	}

	function quick_entry_types_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		global $quick_entry_types;
		echo "<tr><td class='label'>$label</td><td>";
		echo array_selector($name, $selected_id, $quick_entry_types, array('select_submit' => $submit_on_change));
		echo "</td></tr>\n";
	}

	function record_status_list_row($label, $name)
	{
		return yesno_list_row($label, $name, null, _('Inactive'), _('Active'));
	}

	function class_types_list_row($label, $name, $selected_id = null, $submit_on_change = false)
	{
		global $class_types;
		echo "<tr><td class='label'>$label</td><td>";
		echo array_selector($name, $selected_id, $class_types, array('select_submit' => $submit_on_change));
		echo "</td></tr>\n";
	}

	//------------------------------------------------------------------------------------------------
	function security_roles_list($name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false)
	{
		$sql = "SELECT id, role, inactive FROM security_roles";
		return combo_input($name, $selected_id, $sql, 'id', 'description', array(
			'spec_option' => $new_item ? _("New role") : false, 'spec_id' => '', 'select_submit' => $submit_on_change, 'show_inactive' => $show_inactive));
	}

	function security_roles_list_cells($label, $name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>";
		echo security_roles_list($name, $selected_id, $new_item, $submit_on_change, $show_inactive);
		echo "</td>\n";
	}

	function security_roles_list_row($label, $name, $selected_id = null, $new_item = false, $submit_on_change = false, $show_inactive = false)
	{
		echo "<tr><td class='label'>$label</td>";
		security_roles_list_cells(null, $name, $selected_id, $new_item, $submit_on_change, $show_inactive);
		echo "</tr>\n";
	}

	function tab_list_row($label, $name, $selected_id = null, $all = false)
	{
		global $installed_extensions;
		$tabs = array();
		foreach (Session::get()->App->applications as $app) {
			$tabs[$app->id] = access_string($app->name, true);
		}
		if ($all) { // add also not active ext. modules
			foreach ($installed_extensions as $ext) {
				if ($ext['type'] == 'module' && !$ext['active']) {
					$tabs[$ext['tab']] = access_string($ext['title'], true);
				}
			}
		}
		echo "<tr>\n";
		echo "<td class='label'>$label</td><td>\n";
		echo array_selector($name, $selected_id, $tabs);
		echo "</td></tr>\n";
	}

	//-----------------------------------------------------------------------------------------------
	function tag_list($name, $height, $type, $multi = false, $all = false, $spec_opt = false)
	{
		// Get tags
		$results = Tags::get_all($type, $all);
		while ($tag = DB::fetch($results)) {
			$tags[$tag['id']] = $tag['name'];
		}
		if (!isset($tags)) {
			$tags[''] = $all ? _("No tags defined.") : _("No active tags defined.");
			$spec_opt = false;
		}
		return array_selector($name, null, $tags, array(
			'multi' => $multi, 'height' => $height, 'spec_option' => $spec_opt, 'spec_id' => -1,));
	}

	function tag_list_cells($label, $name, $height, $type, $mult = false, $all = false, $spec_opt = false)
	{
		if ($label != null) {
			echo "<td>$label</td>\n";
		}
		echo "<td>\n";
		echo tag_list($name, $height, $type, $mult, $all, $spec_opt);
		echo "</td>\n";
	}

	function tag_list_row($label, $name, $height, $type, $mult = false, $all = false, $spec_opt = false)
	{
		echo "<tr><td class='label'>$label</td>";
		tag_list_cells(null, $name, $height, $type, $mult, $all, $spec_opt);
		echo "</tr>\n";
	}

	//---------------------------------------------------------------------------------------------
	//	List of sets of active extensions
	//
	function extset_list($name, $value = null, $submit_on_change = false)
	{
		$items = array();
		foreach (Config::get_all('db') as $comp) {
			$items[] = sprintf(_("Activated for '%s'"), $comp['name']);
		}
		return array_selector($name, $value, $items, array(
			'spec_option' => _("Installed on system"), 'spec_id' => -1, 'select_submit' => $submit_on_change, 'async' => true));
	}

