<?php
  /**
     * PHP version 5.4
     * @category  PHP
     * @package   ADVAccounts
     * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
     * @copyright 2010 - 2012
     * @link      http://www.advancedgroup.com.au
     **/

  Page::start(_($help_context = "Users"), SA_USERS);
  list($Mode, $selected_id) = list($Mode, $selected_id) = Page::simple_mode(TRUE);
  if (!empty($_POST['password']) && ($Mode == ADD_ITEM || $Mode == UPDATE_ITEM)) {
    $auth = new Auth($_POST['user_id']);
    if (can_process($auth)) {
      $password = $auth->hash_password();
      if ($Mode == UPDATE_ITEM) {
        Users::update($selected_id, $_POST['user_id'], $_POST['real_name'], $_POST['phone'], $_POST['email'], $_POST['Access'], $_POST['language'], $_POST['profile'], check_value('rep_popup'), $_POST['pos']);
        Users::update_password($selected_id, $_POST['user_id'], $password);
        Event::success(_("The selected user has been updated."));
      }
      else {
        Users::add($_POST['user_id'], $_POST['real_name'], $password, $_POST['phone'], $_POST['email'], $_POST['Access'], $_POST['language'], $_POST['profile'], check_value('rep_popup'), $_POST['pos']);
        // use current user display preferences as start point for new user
        Users::update_display_prefs(DB::insert_id(), User::price_dec(), User::qty_dec(), User::exrate_dec(),
          User::percent_dec(), User::show_gl_info(), User::show_codes(), User::date_format(), User::date_sep(), User::prefs()->tho_sep, User::prefs()->dec_sep, User::theme(), User::pagesize(), User::hints(), $_POST['profile'], check_value('rep_popup'), User::query_size(), User::graphic_links(),
          $_POST['language'], User::sticky_date(), User::startup_tab());
        Event::success(_("A new user has been added."));
      }
      $Mode = MODE_RESET;
    }
  }
  if ($Mode == MODE_DELETE) {
    Users::delete($selected_id);
    Event::notice(_("User has been deleted."));
    $Mode = MODE_RESET;
  }
  if ($Mode == MODE_RESET) {
    $selected_id = -1;
    $sav = get_post('show_inactive');
    unset($_POST); // clean all input fields
    $_POST['show_inactive'] = $sav;
  }
  $result = Users::get_all(check_value('show_inactive'));
  start_form();
  start_table('tablestyle');
  $th = array(
    _("User login"), _("Full Name"), _("Phone"), _("E-mail"), _("Last Visit"), _("Access Level"), "", ""
  );
  inactive_control_column($th);
  table_header($th);
  $k = 0; //row colour counter
  while ($myrow = DB::fetch($result)) {
    alt_table_row_color($k);
    $last_visit_date = Dates::sql2date($myrow["last_visit_date"]);
    /*The security_headings array is defined in config.php */
    $not_me = strcasecmp($myrow["user_id"], User::i()->username);
    label_cell($myrow["user_id"]);
    label_cell($myrow["real_name"]);
    label_cell($myrow["phone"]);
    email_cell($myrow["email"]);
    label_cell($last_visit_date, ' class="nowrap"');
    label_cell($myrow["role"]);
    if ($not_me) {
      inactive_control_cell($myrow["id"], $myrow["inactive"], 'users', 'id');
    }
    elseif (check_value('show_inactive')) {
      label_cell('');
    }
    edit_button_cell("Edit" . $myrow["id"], _("Edit"));
    if ($not_me) {
      delete_button_cell("Delete" . $myrow["id"], _("Delete"));
    }
    else {
      label_cell('');
    }
    end_row();
  } //END WHILE LIST LOOP
  inactive_control_row($th);
  end_table(1);
  start_table('tablestyle2');
  $_POST['email'] = "";
  if ($selected_id != -1) {
    if ($Mode == MODE_EDIT) {
      //editing an existing User
      $myrow = Users::get($selected_id);
      $_POST['id'] = $myrow["id"];
      $_POST['user_id'] = $myrow["user_id"];
      $_POST['real_name'] = $myrow["real_name"];
      $_POST['phone'] = $myrow["phone"];
      $_POST['email'] = $myrow["email"];
      $_POST['Access'] = $myrow["role_id"];
      $_POST['language'] = $myrow["language"];
      $_POST['profile'] = $myrow["print_profile"];
      $_POST['rep_popup'] = $myrow["rep_popup"];
      $_POST['pos'] = $myrow["pos"];
    }
    hidden('selected_id', $selected_id);
    hidden('user_id');
    start_row();
    label_row(_("User login:"), Input::post('user_id'));
  }
  else { //end of if $selected_id only do the else when a new record is being entered
    text_row(_("User Login:"), "user_id", NULL, 22, 20);
    $_POST['language'] = User::language();
    $_POST['profile'] = User::print_profile();
    $_POST['rep_popup'] = User::rep_popup();
    $_POST['pos'] = User::pos();
  }
  $_POST['password'] = "";
  password_row(_("Password:"), 'password', $_POST['password']);
  if ($selected_id != -1) {
    table_section_title(_("Enter a new password to change, leave empty to keep current."));
  }
  text_row_ex(_("Full Name") . ":", 'real_name', 50);
  text_row_ex(_("Telephone No.:"), 'phone', 30);
  email_row_ex(_("Email Address:"), 'email', 50);
  Security::roles_row(_("Access Level:"), 'Access', NULL);
  Languages::row(_("Language:"), 'language', NULL);
  Sales_Point::row(_("User's POS") . ':', 'pos', NULL);
  Reports_UI::print_profiles_row(_("Printing profile") . ':', 'profile', NULL, _('Browser printing support'));
  check_row(_("Use popup window for reports:"), 'rep_popup', Input::post('rep_popup'), FALSE, _('Set this option to on if your browser directly supports pdf files'));
  end_table(1);
  submit_add_or_update_center($selected_id == -1, '', 'both');
  end_form();
  Page::end();
  /**
   * @param \Auth $auth
   *
   * @internal param $user
   *
   * @return bool
   */
  function can_process(Auth $auth) {
    if (strlen($_POST['user_id']) < 4) {
      Event::error(_("The user login entered must be at least 4 characters long."));
      JS::set_focus('user_id');
      return FALSE;
    }

    $check = (is_a($auth, 'Auth')) ? $auth->checkPasswordStrength() : FALSE;
    if (!$check && $check['error'] > 0) {
      Event::error($check['text']);
      return FALSE;
    }
    if (!$check && $check['strength'] < 3) {
      Event::error(_("Password Too Weak!"));
      return FALSE;
    }

    return TRUE;
  }


