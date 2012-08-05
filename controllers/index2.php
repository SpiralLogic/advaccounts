<?php
$class='ass';
$lang_dir='ass';
$stylesheets = ['teete','etetet'];
$scripts = ['teete','etetet'];
$title = 'teete';
?><html <?php if ($class): ?>class='<?php echo isset($class) ? $class : ""; ?>'<?php endif;?> dir='<?php echo isset($lang_dir) ? $lang_dir : ""; ?>'>
<head>
  <meta charset='utf-8'>
  <title><?php echo isset($title) ? $title : ""; ?></title>
  <script>document.documentElement.className = document.documentElement.className + ' js'</script>
  <link rel='apple-touch-icon' href='/company/images/Advanced-Group-Logo.png'/>
  <?php if (isset($stylesheets) && is_array($stylesheets) && isset($stylesheets[0])): foreach ($stylesheets as $_stylesheets): ?>
  <link href='<?php echo $_stylesheets; ?>' rel='stylesheet'/>
  <?php endforeach; endif;?>
  <?php if (isset($scripts) && is_array($scripts) && isset($scripts[0])): foreach ($scripts as $_scripts): ?>
  <script src='<?php echo $_scripts; ?>'></script>
  <?php endforeach; endif;?>
</head>
<body <?php if ($class): ?>class='<?php echo isset($class) ? $class : ""; ?>'<?php endif;?>>
<div id='content'>
