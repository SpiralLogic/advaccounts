<?php
  /**
   * Created by JetBrains PhpStorm.
   * User: advanced
   * Date: 6/12/10
   * Time: 5:36 PM
   * To change this template use File | Settings | File Templates.
   */
  class Messages {

    static protected $messages = '';
    static protected $count = 0;

    public function __construct() {
    }

    static public function  get($userid = false) {
      if (!$userid) {
        return false;
      }
      $result = DB::select('um.*,u.real_name as `from`')->from('user_messages um, users u')->where('um.user=', $userid)->and_where('um.from=u.id')->and_where('unread>', 0)->fetch()->all();
      static::$count = count($result);
      foreach ($result as $row) {
        if (!empty($row['subject'])) {
          static::$messages .= '<div class="subject">From: ' . $row['from'] . "<br>" . $row['subject'] . ':</div>
					<hr/> <div class="message" >'
            . trim($row['message']) . '</div>';
        }
        else {
          static::$messages .= '<hr/> <div class="message" >'
            . trim($row['message']) . '</div>';
        }
        $unread = $row['unread'] - 1;
        $id = $row['id'];
        $sql2 = "UPDATE user_messages SET unread={$unread} WHERE id={$id} AND user=" . $userid;
        DB::query($sql2, 'Could not mark messages as unread');
      }
      return static::$count;
    }

    static public function set($userid, $subject, $message) {
      $sql = "INSERT INTO user_messages (user, subject,message,unread,`from`) VALUES (" . DB::escape($userid) . ", " . DB::escape($subject) . ", " . DB::escape($message) . ", 1, " . DB::escape(User::i()->user) . ")";
      $result = DB::query($sql, "Couldn't add message for $userid");
      return $result;
    }

    /**
     * @static
     *
     * @param bool $user
     */
    static public function show($user = false) {
      if (!$user && User::i()) {
        $user = User::i()->user;
      }
      static::get($user);
      if (static::$count > 0) {
        static::makeDialog();
      }
    }

    static public function makeDialog() {
      $dialog = new Dialog(static::$count . ' New Messages', 'messagesbox', static::$messages);
      $dialog->addButtons(array('Close' => '$(this).dialog("close");'));
      $dialog->setOptions(array(
        'autoOpen' => true,
        'modal' => true,
        'width' => '500',
        'resizeable' => false
      ));
      $dialog->show();
    }
  }
