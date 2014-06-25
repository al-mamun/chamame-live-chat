<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class ChamameMessageFactory {
  public function createNewMessage( $args ) {
    $message = new ChamameMessage();
    $message->setSenderName( $args->getOnlineUserName() );
    $message->setSenderOnlineUserId( $args->getOnlineUserId() );
    $message->setMessage( $args->getMessage() );
    $message->setConversationId( $args->getConversationId() );
    return $message;
  }

  public function getUnreadMessageArray( $chat, $conversationId, $lastReceivedMessageId ) {
    try {
      $messages = $chat->findUnreadMessages( $conversationId, $lastReceivedMessageId );
    } catch ( Exception $e ) {
      throw new Exception( 'failed to get unread messages', 0, $e );
    }
    $messagesArray = array();
    $timestampFormat = 'H:i';
    foreach( $messages as $m ) {
      $utime = strtotime( $m->created_at );
      $messagesArray[] = array(
        'id' => $m->id,
        'conversationId' => $m->conversation_id,
        'senderName' => $m->sender_name,
        'senderOnlineUserId' => $m->sender_online_user_id,
        'message' => $m->message,
        'timestamp' => date_i18n( $timestampFormat, $utime )
      );
    }
    return $messagesArray;
  }
}
