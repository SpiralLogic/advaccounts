<!DOCTYPE HTML>
<html lang='en' dir='{{$rtl}}'>
<meta charset='utf-8'>
<head><title>{{$title}}</title>
  <link rel='apple-touch-icon' href='/company/images/advanced-icon.png'/>
  <link href='/themes/{{$theme}}/default.css' rel='stylesheet'>
</head>
<body class='loginscreen'>
<div class="center">
  <div class="formbox">    {{$form._start}}

    <table class="login">
      <thead>
      <tr>
        <td class='center' colspan=2>
          <a target='_blank' href='{{POWERED_URL}}'><img width=440 height=64 src='/themes/{{$theme}}/images/logo-advaccounts.png' alt='ADVAccounts'/></a>
          {{#if $timeout}}
          <span class='font5'>Authorization timeout</span><br>You were idle for: {{$idletime}}
          {{/if}}
        </td>
      </tr>
      <tr class='tablehead'>
        <td colspan=2 class='tablehead'>Version {{VERSION}} - Login</td>
      </tr>
      <tr>
        <td colspan=2 class='center'>{{$login_text}}</td>
      </tr>
      </thead>
      <tbody>
      {{#$form}}
      <tr>
        <td>{{.}}</td>
      </tr>
      {{/$form}}
      </tbody>
    </table>
    {{#$form.hidden}}{{.}}{{/$form.hidden}} {{$form._end}}
  </div>
</div>

<div class='center'>{{$data}}
  <br><a class='pad20' target='_blank' href='{{POWERED_URL}}' tabindex='-1'>{{APP_TITLE}} {{VERSION}} - Theme: {{$theme}}</a>
  <br><br><a target='_blank' href='{{POWERED_URL}}' tabindex='-1'>{{POWERED_BY}}</a></div>
<script>
  {{#if $timeout}}
  (function () {document.forms[0].login_company.value = '{{$company}}';})();
  {{/if}}
  document.forms[0].user_name.select();
  document.forms[0].user_name.focus();</script>
</body>
</html>

