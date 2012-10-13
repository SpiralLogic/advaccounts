<?php
  use ADV\App\Form\DropDown;
  use ADV\App\Page;

  Page::start('test');
  $dd = new DropDown();
  $dd->setTitle('test')->setAuto(true)->setSplit(true);
  $dd->addItem('test', '#', ['weww'=> 'wawa'], ['weww'=> 'wawa']);
  $dd->addItem('test2', '#', ['ssweww'=> 'wawa'], ['wewwd'=> 'wawa']);
  $dd->render();
  Page::end();
