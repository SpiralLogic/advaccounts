<head>
	<script src='js/jquery-1.4.3.min.js'></script>
	<script> function gomango() {alert("Phil's advice for the day " + $("#InputSomething").val())};
		$(function() {		$("#stuff").change(function() {
		$.get('index.php',function(data) {
			$("#HiPhil").prepend($(data))
		}); })});
</script>
</head>
<?php
include_once('includes/ui2/ui.inc');
UI::body('page', array('style' => 'background-color: royalblue'));
UI::div('HiPhil', array('style' => 'margin:20px auto; text-align:center; font-weight:bold;font-size:40px'));
echo 'HI Sam you are not phil';
UI::table('table1', array('style' => 'width:80%; text-align:center; margin:0 auto;'))->tr()->td('left', array('style' => 'width:50%'));
UI::img('pic1', array('src' => 'http://images.apple.com/iphone/features/images/homescreen-hero-20101116.png', 'alt' => 'Wawa?', 'title' => 'Wawa'))->img->td->td('right',
	array('style' => 'width:50%'));
UI::input("address",array('type'=>'text','size'+.20))->input->br;
UI::button('button1', 'i am a button', array('onclick'=>'javascript:gomango()'))->td->tr->tr()->td('samsshit',array('colspan'=>2));
echo 'col storey';
UI::br()->br->select('stuff',array('Do stuff'=>1,'Do something other shit'=>2),array('class'=>'select'))->select->br;

