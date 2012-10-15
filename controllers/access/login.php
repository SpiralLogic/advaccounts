<?php
  use ADV\App\Forms;
  use ADV\Core\Table;

  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  // Display demo user name and password within login form if "Config::_get('demo_mode') " is true
  if (Config::_get('demo_mode') == true) {
    $demo_text = _("Login as user: demouser and password: password");
  } else {
    $demo_text = _("Please login here");
  }
  $def_theme     = "default";
  $login_timeout = User::i()->last_action;
  $title         = $login_timeout ? _('Authorization timeout') : APP_TITLE . " " . VERSION . " - " . _("Login");
  $encoding      = isset($_SESSION['language']->encoding) ? $_SESSION['language']->encoding : "utf-8";
  $rtl           = isset($_SESSION['language']->dir) ? $_SESSION['language']->dir : "ltr";
  if (!headers_sent()) {
    header("Content-type: text/html; charset=UTF-8");
  }
  echo "<!DOCTYPE HTML>\n";
  echo "<html lang='en'  dir='$rtl' >\n";
  echo "<meta charset='utf-8'> ";
  echo "<head><title>$title</title>\n";
  echo "<link rel='apple-touch-icon' href='/company/images/advanced-icon.png'/>";
  echo "<link href='/themes/{$def_theme}/default.css' rel='stylesheet'> \n";
  echo "</head>\n";
  echo "<body class='loginscreen' >\n";
  echo "<table class='titletext'><tr><td>$title</td></tr></table>\n";
  Ajax::_start_div('_page_body');
  echo "<br><br>";
  Forms::start(false, REQUEST_POST ? $_SESSION['timeout']['uri'] : '#', "loginform");
  if (REQUEST_GET) {
    Forms::hidden('uri', $_SESSION['timeout']['uri']);
  }
  Table::start('login');
  echo '<tr>';
  echo "<td class='center' colspan=2>";
  echo "<a target='_blank' href='" . POWERED_URL . "'><img width=440 height=64 src='/themes/$def_theme/images/logo-advaccounts.png' alt='ADVAccounts'  /></a>";
  if ($login_timeout) {
    echo "<span class='font5'>" . _('Authorization timeout') . "</span><br>You were idle for: " . (User::i()->last_action + User::i()->timeout - time());
  }
  echo "</td>\n";
  echo '</tr>';
  if (!$login_timeout) {
    Table::sectionTitle(_("Version ") . VERSION . " - " . _("Login"));
  }
  $value = $login_timeout ? User::i()->loginname : (Config::_get('demo_mode') ? "demouser" : "");
  echo '<tr>';
  Cell::label($demo_text, "colspan=2 class='center'");
  echo '</tr>';
  Forms::textRow(_("User name"), "user_name", $value, 'med', 30);
  $password = Config::_get('demo_mode') ? "password" : "";
  Forms::passwordRow(_("Password:"), 'password', $password);
  if ($login_timeout) {
    Forms::hidden('login_company', User::i()->company);
  } else {
    $coy       = User::i()->company;
    $companies = Config::_getAll('db');
    echo "<tr><td class='label'><label for='login_company'>" . _("Company") . "</label></td><td><select id='login_company'
    name='login_company' class='med'>\n";
    foreach ($companies as $name => $company) {
      if (!$company['company']) {
        continue;
      }
      echo "<option value='$name' " . ($name == $coy ? 'selected' : '') . ">" . $company['company'] . "</option>";
    }
    echo "</select>\n";
  }
  echo '<tr>';
  echo "<td colspan='2' class='center pad20'><button name='SubmitUser'>" . _("Login -->") . "</button></td>\n";
  echo '</tr>';
  Table::end(1);
  foreach ($_POST as $p => $val) {
    // add all request variables to be resend together with login data
    if (!in_array($p, array('user_name', 'password', 'SubmitUser', 'login_company'))) {
      echo "<input type='hidden' name='" . serialize($p) . "' value='$val'>";
    }
  }
  Forms::end(1);
  Ajax::_end_div();
  echo "<div class='center'>\n";
  if (User::i()) {
    echo     $date = Dates::_today() . " | " . Dates::_now();
  } else {
    echo   $date = date("m/d/Y") . " | " . date("h.i am");
  }
  echo "<br><a class='pad20' target='_blank' href='" . POWERED_URL . "' tabindex='-1'>" . APP_TITLE . ' ' . VERSION . " - " . _("Theme:") . "
	 " . $def_theme . "</a>\n";
  echo "<br><br><a target='_blank' href='" . POWERED_URL . "' tabindex='-1'>" . POWERED_BY . "</a></div>";
  echo "<script>";
  if ($login_timeout) {
    echo "(function(){document.forms[0].login_company.value = '" . User::i()->company . "';})();";
  }
  echo   "document.forms[0].user_name.select();document.forms[0].user_name.focus();</script>";
  echo "</body></html>\n";
