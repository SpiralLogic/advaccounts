<?php
$test = new \ADV\App\Form\Form();
$test->text('test','ed',['class'=>'wawa'])->label('wawa');


  $feilds = $test->getFields();
echo $feilds['test'];
