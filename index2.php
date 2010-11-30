<head>
	<script src='js/jquery-1.4.3.min.js'></script>
	<script> function gomango() {
		alert("Phil's advice for the day " + $("#InputSomething").val())
	}</script>
</head>
		<?php

include_once('includes/ui2/ui.inc');




UI::body('body', array('style' => 'background-color: royalblue'));

UI::divStart('HiPhil', array('style' => 'margin:20px auto; text-align:center; font-weight:bold;font-size:40px'));
echo "Hi Phil";
UI::table('start', array('style' => 'width:80%; text-align:center; margin:0 auto;'));
UI::tr('start')->tdStart('left', array('style' => 'width:50%'));


UI::img('pic1', array('src' => 'http://images.apple.com/iphone/features/images/homescreen-hero-20101116.png', 'alt' => 'Wawa?', 'title' => 'Wawa'));


UI::tdEnd()->tdStart('right', array('style' => 'width:50%'));

UI::select('1', array('The first fuckingthing' => 0, 'wrong choice' => 1))->br()->input('InputSomething', array('name' => 'input1', 'length' => 15))->br()->button('button', 'i am a button',
	'button1', 'whatever', 'boring', 'gobacktoschool', array('onclick' => 'javascript:gomango()'))->br();
UI::tdEnd()->tr('end')->table('end')->divEnd();


