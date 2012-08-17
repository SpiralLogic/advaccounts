<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   ADVAccounts
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/

  if (AJAX_REFERRER && isset($_POST['user_id'])) {
    Messages::set($_POST['user_id'], $_POST['subject'], $_POST['message']);
    Event::success("Message sent!");
    JS::_renderJSON([]);
  }
  JS::_footerFile("/js/messages.js");
  Page::start(_($help_context = "Messages"), SA_OPEN, Input::_request('frame'));
  HTML::div(array('style' => 'margin:0 auto;text-align:center'));
  Users::row(_("User:"), 'user_id');
  HTML::br(false)->label(array(
                              'content' => "Subject: ", 'for'     => 'subject'
                         ))->br->input('subject', array('size' => 50))->label;
  HTML::br(false)->label(array(
                              'content' => "Message: ", 'for'     => 'message'
                         ))->br->textarea('message', array(
                                                          'cols'  => 35, 'rows'  => 5, 'title' => 'Message to send:'
                                                     ))->textarea->label->br;
  UI::button('btnSend', 'Send Message');
  HTML::_div();
  Page::end();
