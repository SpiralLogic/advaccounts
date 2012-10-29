<!DOCTYPE HTML>
<html lang='en' dir='{{$rtl}}'>
<meta charset='utf-8'>
<head><title>{{$title}}</title>
  <link rel='apple-touch-icon' href='/company/images/advanced-icon.png'/>
  <link href='/themes/{{$theme}}/default.css' rel='stylesheet'>
</head>
<body class='loginscreen'>
<div class="center">
  <div class='login'>
    <a target='_blank' href='{{POWERED_URL}}'><img width=440 height=64 src='/themes/{{$theme}}/images/logo-advaccounts.png' alt='ADVAccounts'/></a>
    {{#if $timeout}}
    <span class='font5'>Authorization timeout</span><br>You were idle for: {{$idletime}}
    {{/if}}
    <div class='tablehead'>
      Version {{VERSION}} - Login
    </div>
    <div class='formdiv'>
      <p class='center pad5'>{{$login_text}}</p>
      {{$form._start}}
      <hr>

      {{#$form}}
      {{.}}
      <hr>

      {{/$form}}
      <div class='center'>
        {{#$form.buttons}}
        {{.}}
        {{/$form.buttons}}
        {{#$form.hidden}}
        {{.}}
        {{/$form.hidden}}
      </div>
      {{$form._end}}
    </div>
  </div>
  <div class='pad20'><a target='_blank' href='{{POWERED_URL}}' tabindex='-1'>{{APP_TITLE}} {{VERSION}} - Theme: {{$theme}} </a>| {{$date}}
    <p><a target='_blank' href='{{POWERED_URL}}' tabindex='-1'>{{POWERED_BY}}</a></p></div>
  <script src="/js/libs/aes.js"></script>
  <script src="/js/libs/jquery.js"></script>
  <script>
    {{#if $timeout}}
    (function () {document.forms[0].login_company.value = '{{$company}}';})();
    {{/if}}
    document.forms[0].user_name.select();
    document.forms[0].user_name.focus();
    var password_iv = document.getElementById('password_iv'), iv = password_iv.value, password_el = document.getElementById('password');
    password_iv.parentNode.removeChild(password_iv);
    $(document.getElementsByName('_action')[0]).on('click', function () {
      password_el.value = Base64.encode(Aes.Ctr.encrypt(password_el.value, iv, 256));
      $('form').submit();
      return false;
    });
  </script>
</body>
</html>

