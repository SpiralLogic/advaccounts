<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  Page::start(_($help_context = "Access setup"), SA_SECROLES);
  $new_role = Input::post('role') == '' || Input::post('cancel') || Input::post('clone');
  // Following compare function is used for sorting areas
  // in such a way that security areas defined by module/plugin
  // is properly placed under related section regardless of
  // unique extension number, with order inside sections preserved.
  //
  /**
   * @param $area1
   * @param $area2
   *
   * @return int
   */
  function comp_areas($area1, $area2)
  {
    $sec_comp = ($area1[0] & 0xff00) - ($area2[0] & 0xff00);

    return $sec_comp == 0 ? ($area1[2] - $area2[2]) : $sec_comp;
  }

  /**
   * @param $areas
   *
   * @return array
   */
  function sort_areas($areas)
  {
    $old_order = 0;
    foreach ($areas as $key => $area) {
      $areas[$key][] = $old_order++;
    }
    uasort($areas, 'comp_areas');

    return $areas;
  }

  if (Form::isListUpdated('role')) {
    Ajax::i()->activate('details');
    Ajax::i()->activate('controls');
  }
  function clear_data()
  {
    unset($_POST);
  }

  if (Input::post('addupdate')) {
    $input_error = 0;
    if ($_POST['description'] == '') {
      $input_error = 1;
      Event::error(_("Role description cannot be empty."));
      JS::set_focus('description');
    } elseif ($_POST['name'] == '') {
      $input_error = 1;
      Event::error(_("Role name cannot be empty."));
      JS::set_focus('name');
    }
    // prevent accidental editor lockup by removing SA_SECROLES
    if (Input::post('role') == User::i()->access) {
      if (!isset($_POST['Area' . $security_areas[SA_SECROLES][0]]) || !isset($_POST[Section . SS_SETUP])
      ) {
        Event::error(_("Access level edition in Company setup section have to be enabled for your account."));
        $input_error = 1;
        JS::set_focus(!isset($_POST['Section' . SS_SETUP]) ? 'Section' . SS_SETUP : 'Area' . $security_areas[SA_SECROLES][0]);
      }
    }
    if ($input_error == 0) {
      $sections = array();
      $areas    = array();
      foreach ($_POST as $p => $val) {
        if (substr($p, 0, 4) == 'Area') {
          $a = substr($p, 4);
          if (($a & ~0xffff) && (($a & 0xff00) < (99 << 8))) {
            $sections[] = $a & ~0xff; // add extended section for plugins
          }
          $areas[] = $a;
        }
        if (substr($p, 0, 7) == 'Section') {
          $sections[] = substr($p, 7);
        }
      }
      //		$areas = sort_areas($areas);
      $sections = array_values($sections);
      if ($new_role) {
        Security::i()->add_role($_POST['name'], $_POST['description'], $sections, $areas);
        Event::success(_("New security role has been added."));
      } else {
        Security::i()->update_role($_POST['role'], $_POST['name'], $_POST['description'], $sections, $areas);
        DB::update_record_status($_POST['role'], Input::post('inactive'), 'security_roles', 'id');
        Event::success(_("Security role has been updated."));
      }
      $new_role = true;
      clear_data();
      Ajax::i()->activate('_page_body');
    }
  }
  if (Input::post('delete')) {
    if (Security::i()->check_role_used(Input::post('role'))) {
      Event::error(_("This role is currently assigned to some users and cannot be deleted"));
    } else {
      Security::i()->delete(Input::post('role'));
      Event::notice(_("Security role has been sucessfully deleted."));
      unset($_POST['role']);
    }
    Ajax::i()->activate('_page_body');
  }
  if (Input::post('cancel')) {
    unset($_POST['role']);
    Ajax::i()->activate('_page_body');
  }
  if (!isset($_POST['role']) || Input::post('clone') || Form::isListUpdated('role')) {
    $id    = Input::post('role');
    $clone = Input::post('clone');
    unset($_POST);
    if ($id) {
      $row                  = Security::i()->get_role($id);
      $_POST['description'] = $row['description'];
      $_POST['name']        = $row['role'];
      //	if ($row['inactive']
      //		$_POST['inactive'] = 1;
      $_POST['inactive'] = $row['inactive'];
      $access            = $row['areas'];
      $sections          = $row['sections'];
    } else {
      $_POST['description'] = $_POST['name'] = '';
      unset($_POST['inactive']);
      $access = $sections = array();
    }
    foreach ($access as $a) {
      $_POST['Area' . $a] = 1;
    }
    foreach ($sections as $s) {
      $_POST['Section' . $s] = 1;
    }
    if ($clone) {
      JS::set_focus('name');
      Ajax::i()->activate('_page_body');
    } else {
      $_POST['role'] = $id;
    }
  }
  Form::start();
  Table::start('tablestyle_noborder');
  Row::start();
  Security::i()->roles_cells(_("Role:") . "&nbsp;", 'role', null, true, true, Form::hasPost('show_inactive'));
  $new_role = Input::post('role') == '';
   Form::checkCells(_("Show inactive:"), 'show_inactive', null, true);
  Row::end();
  Table::end();
  echo "<hr>";
  if (Input::post('_show_inactive_update')) {
    Ajax::i()->activate('role');
    JS::set_focus('role');
  }
  if (Form::findPostPrefix('_Section')) {
    Ajax::i()->activate('details');
  }
  Display::div_start('details');
  Table::start('tablestyle2');
   Form::textRow(_("Role name:"), 'name', null, 20, 22);
   Form::textRow(_("Role description:"), 'description', null, 50, 52);
   Form::recordStatusListRow(_("Current status:"), 'inactive');
  Table::end(1);
  Table::start('tablestyle grid width40');
  $k   = $j = 0; //row colour counter
  $ext = $sec = $m = -1;
  foreach (sort_areas($security_areas) as $area => $parms) {
    // system setup areas are accessable only for site admins i.e.
    // admins of first registered company
    if ((($parms[0] & 0xff00) == SS_SADMIN)) {
      continue;
    }
    $newsec = ($parms[0] >> 8) & 0xff;
    $newext = $parms[0] >> 16;
    if ($newsec != $sec || (($newext != $ext) && ($newsec > 99))) { // features set selection
      $ext = $newext;
      $sec = $newsec;
      $m   = $parms[0] & ~0xff;
      //			if(!isset($security_sections[$m]))
      //			 Event::error(sprintf("Bad section %X:", $m));
      Row::label($security_sections[$m] . ':', Form::checkbox(null, 'Section' . $m, null, true, _("On/off set of features")), "class='left tablehead'", "class='tablehead'");
    }
    if (Form::hasPost('Section' . $m)) {
       Form::checkRow($parms[1], 'Area' . $parms[0], null, false, '', "class='center'");
      Row::end();
    } else {
      Form::hidden('Area' . $parms[0]);
    }
  }
  Table::end(1);
  Display::div_end();
  Display::div_start('controls');
  if ($new_role) {
    Form::submitCenterBegin('Update', _("Update view"), '', null);
    Form::submitCenterEnd('addupdate', _("Insert New Role"), '', 'default');
  } else {
    Form::submitCenterBegin('addupdate', _("Save Role"), '', 'default');
    Form::submit('Update', _("Update view"), true, '', null);
    Form::submit('clone', _("Clone This Role"), true, '', true);
    Form::submit('delete', _("Delete This Role"), true, '', true);
    Form::submitCenterEnd('cancel', _("Cancel"), _("Cancel Edition"), 'cancel');
  }
  Display::div_end();
  Form::end();
  Page::end();

