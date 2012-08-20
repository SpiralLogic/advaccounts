<!DOCTYPE HTML>
<html dir='$rtl'>
<head><title>$title</title>
  <link rel='apple-touch-icon' href='/company/images/advanced-icon.png'/>
  <link href='/themes/{$def_theme}/default.css' rel='stylesheet'>
</head>
<body class='loginscreen'>
<table class='titletext'>
  <tr>
    <td>$title</td>
  </tr>
</table>
<input type='hidden' id='ui_mode' name='ui_mode' value='" . User::i()->ui_mode . "'/>
<td class='center' colspan=2>
  <a target='_blank' href='" . POWERED_URL . "'><img width=440 height=64 src='/themes/$def_theme/images/logo_advaccounts.png' alt='ADVAccounts'/></a>
  <span class='font5'>" . _('Authorization timeout') . "</span><br>You were idle for: " . (User::i()->last_act +
  User::i()->timeout - time());
</td>
<tr>
  <td class='label'><label for='login_company'>" . _("Company") . "</label></td>
  <td><select id='login_company'
              name='login_company' class='med'>
    <option value='$name'
    " . ($name == $coy ? 'selected' : '') . ">" . $company['company'] . "</option>
  </select>
  <td colspan='2' class='center pad20'>
    <button name='SubmitUser'>" . _("Login -->") . "</button>
  </td>
  <input type='hidden' name='" . serialize($p) . "' value='$val'>
<div class='center'>
  echo $date = Dates::_today() . " | " . Dates::_now();
  echo $date = date("m/d/Y") . " | " . date("h.i am");
  <br><a class='pad20' target='_blank' href='" . POWERED_URL . "' tabindex='-1'>" . APP_TITLE . ' ' . VERSION . " - " .
  _("Theme:") . "
  " . $def_theme . "</a>
  <br><br><a target='_blank' href='" . POWERED_URL . "' tabindex='-1'>" . POWERED_BY . "</a></div>
<script>
  (function () {document.forms[0].login_company.value = '" . User::i()->company . "';})();
  document.forms[0].user_name.select();
  document.forms[0].user_name.focus();</script>
</body>
</html>
