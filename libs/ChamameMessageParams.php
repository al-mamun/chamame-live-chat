<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class ChamameMessageParams {
  const MESSAGE_KEY = 'chatMessage';

  private $post;
  private $session;
  private $errorMessage;
  private $valid;

  private $onlineUserName;
  private $onlineUserId;
  private $conversationId;
  private $message;

  public function __construct( $post, $session ) {
    $this->post = $post;
    $this->session = $session;
    $this->valid = false;

    $this->parse();
  }

  private function parse() {
    $this->onlineUserName = $this->session->getOnlineUserName();
    if ( is_null( $this->onlineUserName ) ) {
      $this->errorMessage = 'no online user name';
      return;
    }

    $this->onlineUserId = $this->session->getOnlineUserId();
    if ( is_null( $this->onlineUserId ) ) {
      $this->errorMessage = 'no online user id';
      return;
    }

    $this->conversationId = $this->session->getActiveConversationId();
    if ( is_null( $this->conversationId ) ) {
      $this->errorMessage = 'no conversation id';
      return;
    }

    if ( ! isset( $this->post[self::MESSAGE_KEY] ) ) {
      $this->errorMessage = 'no message';
      return;
    }
    $this->message = trim( $this->post[self::MESSAGE_KEY] );
    if ( $this->message === '' ) {
      $this->errorMessage = 'empty message';
      return;
    }

    if ( mb_strlen( $this->message ) > 400 ) {
      $this->message = 'too long message';
      return;
    }

    $this->valid = true;
  }

  public function getOnlineUserName() {
    return $this->onlineUserName;
  }

  public function getOnlineUserId() {
    return $this->onlineUserId;
  }

  public function getConversationId() {
    return $this->conversationId;
  }

  public function getMessage() {
    return $this->message;
  }

  public function getErrorMessage() {
    return $this->errorMessage;
  }

  public function isValid() {
    return $this->valid;
  }
}
