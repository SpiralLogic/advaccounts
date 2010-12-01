<head>
	<script src='js/jquery-1.4.3.min.js'></script>

	<script> function gomango() {
		alert("Phil's advice for the day " + $("#InputSomething").val())
	}</script>
</head>
		<?php
require_once('FirePHPCore/fb.php');
require_once("includes/firephp.php");
include_once('includes/ui2/ui.inc');
UI::body('page', array('style' => 'background-color: royalblue'),true);
UI::div('HiPhil', array('style' => 'margin:20px auto; text-align:center; font-weight:bold;font-size:40px'), true);
UI::table('table1', array('style' => 'width:80%; text-align:center; margin:0 auto;'), true)->tr(true)->td('left', array('style' => 'width:50%'), true);

UI::img('pic1', array('src' => 'http://images.apple.com/iphone/features/images/homescreen-hero-20101116.png', 'alt' => 'Wawa?', 'title' => 'Wawa'));

UI::td(false)->td('right', array('style' => 'width:50%'),true);
UI::button('button1', 'i am a button')->td(false)->tr(false);
UI::div(false)->body(false);

