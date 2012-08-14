<!DOCTYPE HTML>
<html {{$class?}}class='{{$class}}'{{/$class?}} dir='{{$lang_dir}}'>
<head>
  <meta charset='utf-8'>
  <title>{{$title}}</title>
  <script>document.documentElement.className = document.documentElement.className + ' js'</script>
  <link rel='apple-touch-icon' href='/company/images/Advanced-Group-Logo.png'/>
  {{#foreach $stylesheets as $css}}

  <link href='{{$css}}' rel='stylesheet'/>
  {{/foreach}}
  {{#foreach $scripts as $script}}
  <script src='{{$script}}'></script>
  {{/foreach}}
</head>
<body {{$body_class?}}class='{{$body_class}}'{{/$body_class?}}>
<div id='content'>
