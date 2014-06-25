<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class ChamameVisitor extends ChamameUser {
  public function getType() {
    return self::USER_TYPE_VISITOR;
  }

  public function login( $chat ) {
    try {
      $chat->registerOnlineUser( $this );
      $conversationId = $chat->createConversation();
      $chat->joinConversation( $this->getId(), $conversationId );
      $chat->activateConversation( $this->getId(), $conversationId );
      $this->setActiveConversationId( $conversationId );
    } catch ( Exception $e ) {
      throw new Exception( 'failed to login', 0, $e );
    }
  }

  public function logout( $chat ) {
    try {
      $chat->deleteOnlineUser( $this->getId() );
    } catch ( Exception $e ) {
      throw new Exception( 'failed to logout', 0, $e );
    }
  }
}
