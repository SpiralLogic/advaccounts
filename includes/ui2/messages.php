<?php
/**
 * Created by JetBrains PhpStorm.
 * User: advanced
 * Date: 6/12/10
 * Time: 5:36 PM
 * To change this template use File | Settings | File Templates.
 */

class Messages
{


    protected static $messages = '';
    protected static $count = 0;

    function __construct()
    {
    }

    static function  getNewUserMessages($userid = false)
    {
        if (!$userid) {
            return false;
        }
        $sql = "SELECT * FROM user_messages WHERE user =$userid AND unread > 0";
        $result = db_query($sql, "could not retrieve user messages");
        static::$count = db_num_rows($result);
        while ($row = db_fetch_assoc($result)) {
            if (!empty($row['subject']))
                static::$messages .= '<div style="margin-top:5px; margin-bottom:5px; font-weight:bold; text-decoration:underline;">' .
                                     $row['subject'] . ':</div><hr style="margin-top:1px;"/> <div style="padding-top:5px;" >'
                                     . trim($row['message']) . '</div>';
            else
                static::$messages .= '<hr style="margin-top:1px;"/> <div style="margin-top:5px; padding-bottom:10px;" >'
                                     . trim($row['message']) . '</div>';
            $unread = $row['unread'] - 1;
            $id = $row['id'];
            $sql2 = "UPDATE user_messages SET unread={$unread} WHERE  id={$id} AND user=" . $userid;

            $result2 = db_query($sql2, 'Could not mark messages as unread');

        }
        return static::$count;
    }

    static function setNewMessage($userid, $subject, $message)
    {
        $sql = "INSERT INTO user_messages (user, subject,message,unread) VALUES (" . db_escape($userid) . ", " . db_escape($subject) . ", " . db_escape($message) . ", 1)";
        $result = db_query($sql, "Couldn't add message for $userid");
        return $result;
    }

    static function showNewMessages($user = false)
    {
        if (!$user) $user = $_SESSION['wa_current_user']->user;
        if (static::getNewUserMessages($user) > 0) {
            static::makeDialog();
        }
    }

    static function makeDialog()
    {

        $dialog = new Dialog(static::$count . ' New Messages', 'messagesbox', static::$messages);
        $dialog->addButtons(array('Close' => '$(this).dialog("close");'));
        $dialog->setOptions(array('autoopen' => true, 'modal' => true, 'width' => '500', 'resizeable' => false));
        $dialog->show();
    }
}
