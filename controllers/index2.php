<?php
  echo Input::get('test');
  echo Input::get('test', null, 'wawa');
  echo Input::get('test');
  echo Input::get('test', null, 'wawa');
  var_dump(Input::get('wawa'));
  Input::$get['wawa'] = 'eee';
  var_dump(Input::$get['wawa']);
