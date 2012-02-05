<?php
	include 'bootstrap.php';
Page::start('test');
Event::success('test');
Event::notice('test');
Event::warning('test');
Event::error('test');
Page::end();

