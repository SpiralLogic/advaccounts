<?php

  Extensions::add_access(User::i());
  // Turn these next two lines on for debugging
  //error_reporting(E_ALL);
  //ini_set("display_errors", "on");
  // Begin the UI
  Page::start("Advanced", SA_ADVANCED);
  Page::end();
