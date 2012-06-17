<?php

  $phil       = new User();
  $mike       = new User();
  $phil->name = 'Phil';
  $mike->name = "Mike";
  echo $phil->name . "<br>";
  echo $mike->name . "<br>";
  echo User::i()->name;

