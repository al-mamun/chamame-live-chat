<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class ChamameMessage {
  private $id;
  private $conversationId;
  private $senderName;
  private $senderOnlineUserId;
  private $message;
  private $createdAt;

  public function __construct() {
  }

  public function setId( $id ) {
    $this->id = $id;
  }
  public function getId() {
    return $this->id;
  }

  public function setConversationId( $id ) {
    $this->conversationId = $id;
  }

  public function getConversationId() {
    return $this->conversationId;
  }

  public function setSenderName( $name ) {
    $this->senderName = $name;
  }

  public function getSenderName() {
    return $this->senderName;
  }

  public function setSenderOnlineUserId( $id ) {
    $this->senderOnlineUserId = $id;
  }

  public function getSenderOnlineUserId() {
    return $this->senderOnlineUserId;
  }

  public function setMessage( $message ) {
    $this->message = $message;
  }

  public function getMessage() {
    return $this->message;
  }

  public function setCreatedAt( $at ) {
    $this->createdAt = $at;
  }

  public function getCreatedAt() {
    return $this->createdAt;
  }

  public function getTimestamp() {
    $utime = strtotime( $this->getCreatedAt() );
    $timestampFormat = 'H:i';
    return date_i18n( $timestampFormat, $utime );
  }

  public function toArray() {
    $r = array();
    $r['id'] = $this->getId();
    $r['conversationId'] = $this->getConversationId();
    $r['senderName'] = $this->getSenderName();
    $r['senderOnlineUserId'] = $this->getSenderOnlineUserId();
    $r['message'] = $this->getMessage();
    $r['timestamp'] = $this->getTimestamp();
    return $r;
  }
}
