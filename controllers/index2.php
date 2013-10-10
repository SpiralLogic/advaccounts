<?php

var_dump(Item::search("Bull"));
  $data = Item::search("Bull");
  array_walk_recursive($data,function(&$value) { if (!is_array($value));$value=utf8_encode($value);});
var_dump(json_encode($data,JSON_UNESCAPED_UNICODE),json_last_error());
