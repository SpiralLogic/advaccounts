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
  Page::start(_($help_context = "Change password"), SA_CHGPASSWD);
  function can_process() {
    if (strlen($_POST['password']) < 4) {
      Event::error(_("The password entered must be at least 4 characters long."));
      JS::set_focus('password');
      return FALSE;
    }
    if (strstr($_POST['password'], User::i()->username) != FALSE) {
      Event::error(_("The password cannot contain the user login."));
      JS::set_focus('password');
      return FALSE;
    }
    if ($_POST['password'] != $_POST['passwordConfirm']) {
      Event::error(_("The passwords entered are not the same."));
      JS::set_focus('password');
      return FALSE;
    }
    return TRUE;
  }

  if (isset($_POST[UPDATE_ITEM])) {
    if (can_process()) {
      if (Config::get('demo_mode')) {
        Event::warning(_("Password cannot be changed in demo mode."));
      }
      else {
        $auth = new Auth(User::i()->username);
        $check = $auth->checkPasswordStrength($_POST['password']);
        if ($check['error'] > 0) {
          Event::error($check['text']);
        }
        elseif ($check['strength'] < 3) {
          Event::error(_("Password Too Weak!"));
        }
        else {
          $auth->update_password(User::i()->user, $_POST['password']);
          User::i()->change_password = FALSE;
          Event::success(_("Password Changed"));
        }
      }
      Ajax::i()->activate('_page_body');
    }
  }
  elseif (User::i()->change_password) {
    Event::warning('You are required to change your password!');
  }
  start_form();
  start_table('tablestyle');
  table_section_title(_("Enter your new password in the fields."));
  $myrow = Users::get(User::i()->user);
  label_row(_("User login:"), $myrow['user_id']);
  $_POST['password'] = $_POST['passwordConfirm'] = "";
  password_row(_("Password:"), 'password', $_POST['password']);
  password_row(_("Repeat password:"), 'passwordConfirm', $_POST['passwordConfirm']);
  end_table(1);
  submit_center(UPDATE_ITEM, _('Change password'), TRUE, '', 'default');
  end_form();
  Page::end();
?>
