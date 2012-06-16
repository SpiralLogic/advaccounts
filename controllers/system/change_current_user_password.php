<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/

  Page::start(_($help_context = "Change password"), SA_CHGPASSWD);
  /**
   * @return bool
   */
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
  Forms::start();
  Table::start('tablestyle');
  Table::sectionTitle(_("Enter your new password in the fields."));
  $myrow = Users::get(User::i()->user);
  Row::label(_("User login:"), $myrow['user_id']);
  $_POST['password'] = $_POST['passwordConfirm'] = "";
   Forms::passwordRow(_("Password:"), 'password', $_POST['password']);
   Forms::passwordRow(_("Repeat password:"), 'passwordConfirm', $_POST['passwordConfirm']);
  Table::end(1);
  Forms::submitCenter(UPDATE_ITEM, _('Change password'), TRUE, '', 'default');
  Forms::end();
  Page::end();

