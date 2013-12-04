<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  if (isset($_POST[UPDATE_ITEM])) {
    if (can_process()) {
      if (Config::_get('demo_mode')) {
        Event::warning(_("Password cannot be changed in demo mode."));
      } else {
        $auth  = new Auth(User::_i()->username);
        $check = $auth->checkPasswordStrength($_POST['password']);
        if ($check['error'] > 0) {
          Event::error($check['text']);
        } elseif ($check['strength'] < 3) {
          Event::error(_("Password Too Weak!"));
        } else {
          $auth->updatePassword(User::_i()->user, $_POST['password']);
          User::_i()->change_password = false;
          Event::success(_("Password Changed"));
        }
      }
      Ajax::_activate('_page_body');
    }
  } elseif (User::_i()->change_password) {
    Event::warning('You are required to change your password!');
  }
  Page::end();

