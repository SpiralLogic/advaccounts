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
	$page_security = 'SA_PRINTPROFILE';
	require_once($_SERVER['DOCUMENT_ROOT'] . "/bootstrap.php");
	Page::start(_($help_context = "Printing Profiles"));
	$selected_id = Display::get_post('profile_id', '');

	// Returns array of defined reports
	//
	function get_reports()
	{
		if (Config::get('debug') || !isset($_SESSION['reports'])) {
			// to save time, store in session.
			$paths   = array(
				PATH_TO_ROOT . '/reporting/',
				COMPANY_PATH . '/reporting/'
			);
			$reports = array('' => _('Default printing destination'));
			foreach (
				$paths as $dirno => $path
			) {
				$repdir = opendir($path);
				while (false !== ($fname = readdir($repdir)))
				{
					// reports have filenames in form rep(repid).php
					// where repid must contain at least one digit (reports_main.php is not ;)
					if (is_file($path . $fname)
					 //				&& preg_match('/.*[^0-9]([0-9]+)[.]php/', $fname, $match))
					 && preg_match('/rep(.*[0-9]+.*)[.]php/', $fname, $match)
					) {
						$repno = $match[1];
						$title = '';
						$line = file_get_contents($path . $fname);
						if (preg_match('/.*(FrontReport\()\s*_\([\'"]([^\'"]*)/', $line, $match)) {
							$title = trim($match[2]);
						}
						else // for any 3rd party printouts without FrontReport() class use
							if (preg_match('/.*(\$Title).*[\'"](.*)[\'"].+/', $line, $match)) {
								$title = trim($match[2]);
							}
						$reports[$repno] = $title;
					}
				}
				closedir();
			}
			ksort($reports);
			$_SESSION['reports'] = $reports;
		}
		return $_SESSION['reports'];
	}

	function clear_form()
	{
		global $selected_id;
		$Ajax          = Ajax::i();
		$selected_id   = '';
		$_POST['name'] = '';
		$Ajax->activate('_page_body');
	}

	function check_delete($name)
	{
		// check if selected profile is used by any user
		if ($name == '') {
			return 0;
		} // cannot delete system default profile
		$sql = "SELECT * FROM users WHERE print_profile=" . DB::escape($name);
		$res = DB::query($sql, 'cannot check printing profile usage');
		return DB::num_rows($res);
	}


	if (Display::get_post('submit')) {
		$error = 0;
		if ($_POST['profile_id'] == '' && empty($_POST['name'])) {
			$error = 1;
			Errors::error(_("Printing profile name cannot be empty."));
			JS::set_focus('name');
		}
		if (!$error) {
			$prof = array('' => Display::get_post('Prn')); // store default value/profile name
			foreach (
				get_reports() as $rep => $descr
			) {
				$val        = Display::get_post('Prn' . $rep);
				$prof[$rep] = $val;
			}
			if ($_POST['profile_id'] == '') {
				$_POST['profile_id'] = Display::get_post('name');
			}
			Printer::update_profile($_POST['profile_id'], $prof);
			if ($selected_id == '') {
				Errors::notice(_('New printing profile has been created'));
				clear_form();
			} else {
				Errors::notice(_('Printing profile has been updated'));
			}
		}
	}
	if (Display::get_post('delete')) {
		if (!check_delete(Display::get_post('name'))) {
			Printer::delete_profile($selected_id);
			Errors::notice(_('Selected printing profile has been deleted'));
			clear_form();
		}
	}
	if (Display::get_post('_profile_id_update')) {
		$Ajax->activate('_page_body');
	}
	Display::start_form();
	Display::start_table();
	Reports_UI::print_profiles_row(
		_('Select printing profile') . ':', 'profile_id', null,
		_('New printing profile'), true
	);
	Display::end_table();
	echo '<hr>';
	Display::start_table();
	if (Display::get_post('profile_id') == '') {
		text_row(_("Printing Profile Name") . ':', 'name', null, 30, 30);
} else {
		label_cells(_("Printing Profile Name") . ':', Display::get_post('profile_id'));
	}
	Display::end_table(1);
	$result = Printer::get_profile(Display::get_post('profile_id'));
	$prints = array();
	while ($myrow = DB::fetch($result)) {
		$prints[$myrow['report']] = $myrow['printer'];
	}
	Display::start_table(Config::get('tables_style'));
	$th = array(_("Report Id"), _("Description"), _("Printer"));
	Display::table_header($th);
	$k    = 0;
	$unkn = 0;
	foreach (
		get_reports() as $rep => $descr
	)
	{
		Display::alt_table_row_color($k);
		label_cell($rep == '' ? '-' : $rep, 'class=center');
		label_cell($descr == '' ? '???<sup>1)</sup>' : _($descr));
		$_POST['Prn' . $rep] = isset($prints[$rep]) ? $prints[$rep] : '';
		echo '<td>';
		echo Reports_UI::printers(
			'Prn' . $rep, null,
			$rep == '' ? _('Browser support') : _('Default')
		);
		echo '</td>';
		if ($descr == '') {
			$unkn = 1;
		}
		Display::end_row();
	}
	Display::end_table();
	if ($unkn) {
		Errors::warning('<sup>1)</sup>&nbsp;-&nbsp;' . _("no title was found in this report definition file."), 0, 1, '');
} else {
		echo '<br>';
	}
	Display::div_start('controls');
	if (Display::get_post('profile_id') == '') {
		submit_center('submit', _("Add New Profile"), true, '', 'default');
	} else {
		submit_center_first(
			'submit', _("Update Profile"),
			_('Update printer profile'), 'default'
		);
		submit_center_last(
			'delete', _("Delete Profile"),
			_('Delete printer profile (only if not used by any user)'), true
		);
	}
	Display::div_end();
	Display::end_form();
	end_page();

?>
