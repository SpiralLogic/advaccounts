<?php
  use ADV\App\Form\Form;
Page::start('test');
  $test = new Form();
  $test->checkbox('test', Dates::_today());
  $fields = $test->getFields();
  echo $fields['test'];
Page::end();
