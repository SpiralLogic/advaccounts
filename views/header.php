<!DOCTYPE HTML>
<html " . (is_object($this->sel_app) ? "class='" . strtolower($this->sel_app->id) . "'" :
  '') . " dir='" . $this->lang_dir . "' >\n";
<head>
  <meta charset='{{ encoding }}'>
  <title>" . $this->title . "</title>";
  HTML::script(NULL, "document.documentElement.className = document.documentElement.className +' js'", FALSE);
  $this->renderCSS();
  <link rel='apple-touch-icon' href='/company/images/Advanced-Group-Logo.png'/>
  ";
  if (class_exists('JS', FALSE)) {
  JS::renderHeader();
  }
</head><body" . (!$this->menu ? ' class="lite">' : '>');
<div id='content'>\n";
