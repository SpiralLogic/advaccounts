<?php
  /**
   * PHP version 5.4
   * @category  PHP
   * @package   adv.accounts.app
   * @author    Advanced Group PTY LTD <admin@advancedgroup.com.au>
   * @copyright 2010 - 2012
   * @link      http://www.advancedgroup.com.au
   **/
  class Messages {

    /**
     * @var string
     */
    static protected $messages = '';
    /**
     * @var int
     */
    static protected $count = 0;
    /**

     */
    public function __construct() {
    }
    /**
     * @static
     *
     * @param bool $userid
     *
     * @return bool|int
     */
    static public function  get($userid = FALSE) {
      if (!$userid) {
        return FALSE;
      }
      $result        = DB::select('um.*,u.real_name as `from`')->from('user_messages um, users u')->where('um.user=', $userid)
        ->and_where('um.from=u.id')->and_where('unread>', 0)->fetch()->all();
      static::$count = count($result);
      foreach ($result as $row) {
        if (!empty($row['subject'])) {
          static::$messages .= '<div class="subject"><span>From:</span>' . e($row['from']) . "<br><span>Subject:</span>" . e
          ($row['subject'])
            .
            '</div>';
          static::$messages .= '<hr/><div class="message">' . e(trim($row['message'])) . '</div>';
        }
        else {
          static::$messages .= '<hr/> <div class="message">'
            . trim($row['message']) . '</div>';
        }
        $unread = $row['unread'] - 1;
        $id     = $row['id'];
        $sql2   = "UPDATE user_messages SET unread={$unread} WHERE id={$id} AND user=" . $userid;
        DB::query($sql2, 'Could not mark messages as unread');
      }
      return static::$count;
    }
    /**
     * @static
     *
     * @param $userid
     * @param $subject
     * @param $message
     *
     * @return null|PDOStatement
     */
    static public function set($userid, $subject, $message) {
      $sql    = "INSERT INTO user_messages (user, subject,message,unread,`from`) VALUES (" . DB::escape($userid) . ", " . DB::escape($subject) . ", " . DB::escape($message) . ", 1, " . DB::escape(User::i()->user) . ")";
      $result = DB::query($sql, "Couldn't add message for $userid");
      return $result;
    }
    /**
     * @static
     *
     * @param User $user
     */
    static public function show($user = NULL) {
      $user = $user ? : User::i();
      if (!$user || !$user->logged) {
        return;
      }
      static::get($user->user);
      if (static::$count > 0) {
        static::makeDialog();
      }
    }
    static public function makeDialog() {
      $dialog = new Dialog(static::$count . ' New Messages', 'messagesbox', static::$messages);
      $dialog->addButtons(array('Close' => '$(this).dialog("close");'));
      $dialog->setOptions(array(
        'autoOpen'   => TRUE,
        'modal'      => TRUE,
        'width'      => '500',
        'resizeable' => FALSE
      ));
      $dialog->show();
    }
  }

