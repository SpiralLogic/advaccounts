<!DOCTYPE HTML>
<html {{#class?}}class='{{class}}'{{/class?}} dir='{{lang_dir}}'>
<head>
  <meta charset='utf-8'>
  <title>{{title}}</title>
  <script>document.documentElement.className = document.documentElement.className + ' js'</script>
  <link rel='apple-touch-icon' href='/company/images/Advanced-Group-Logo.png'/>
  {{#stylesheets}}
  <link href='{{.}}' rel='stylesheet'/>
  {{/stylesheets}}
  {{#scripts}}
  <script src='{{.}}'></script>
  {{/scripts}}
</head>
<body {{#class?}}class='{{class}}'{{/class?}}>
<div id='content'>
