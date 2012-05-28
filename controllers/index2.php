<?php
  if (AJAX_REFERRER) {
    var_dump($_POST);exit;
  }
  JS::onload('$("#testform").on("click","button",function(){$.post("#",$("#testform").serialize()+"&"+$(this).attr("name")+"="+$(this).val(),function(data){console.log(data);});return false});');
  Page::start('test');

?>
<form id='testform' action='#' method='post'><input type='text' name='eses' value='test'>
<button name='test' value='test2'>test1</button>
<button name='test' value='test3'>test1</button>
<button name='test' value='test4'>test1</button>
</form><?php Page::end();

