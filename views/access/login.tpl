<!DOCTYPE HTML>
<html lang='en' dir='{{$rtl}}'>
<meta charset='utf-8'>
<head><title>{{$title}}</title>
  <link rel='apple-touch-icon' href='/company/images/advanced-icon.png'/>
  <link href='/themes/{{$def_theme}}/default.css' rel='stylesheet'>
</head>
<body class='loginscreen'>
<table class='titletext'>
  <tr>
    <td>{{$title}}</td>
  </tr>

</table>
<br><br>

<div class="center">
  <table class="login">
    <tr>
      <td class='center' colspan=2>
        <a target='_blank' href='{{POWERED_URL}}'><img width=440 height=64 src='/themes/{{$def_theme}}/images/logo-advaccounts.png' alt='ADVAccounts'/></a>
        {{#if $timeout}}
        <span class='font5'>Authorization timeout</span><br>You were idle for: {{$idletime}}
        {{/if}}
      </td>
    </tr>
    <tr class='tablehead'>
      <td colspan=2 class='tablehead'>Version {{VERSION}} - Login</td>
    </tr>
    <tr>
      <td colspan=2 class='center'>{{$demo_text}}</td>
    </tr>

  </table>
  {{#$form}}
  {{.}}
  {{/$form}}
</div>
<div class='center'>{{$data}}
  <br><a class='pad20' target='_blank' href='{{POWERED_URL}}' tabindex='-1'>{{APP_TITLE}} {{VERSION}} - Theme: {{$def_theme}}</a>
  <br><br><a target='_blank' href='{{POWERED_URL}}' tabindex='-1'>{{POWERED_BY}}</a></div>
<script>
  {{#if $timeout}}
  (function () {document.forms[0].login_company.value = '{{$company}}';})();
  {{/if}}
  document.forms[0].user_name.select();
  document.forms[0].user_name.focus();</script>
</body>
</html>

