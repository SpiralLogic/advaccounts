<?php
  var_dump(Input::_post('_action'));
  $_POST['_action'] = true;
  var_dump(Input::_post('_action'));
  $_POST['_action'] = false;
  var_dump(Input::_post('_action'));
