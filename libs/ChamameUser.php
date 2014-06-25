<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

abstract class ChamameUser {
  const USER_TYPE_OPERATOR = 'operator';
  const USER_TYPE_VISITOR = 'visitor';
  
  private $id;
  private $name;
  private $email;
  private $ipAddress;
  private $userAgent;
  private $wpUserId;
  private $lastActivityAt;
  private $createdAt;
  private $updatedAt;
  private $activeConversationId;

  public function __construct( $name, $email ) {
    $this->name = $name;
    $this->email = $email;
  }

  public function setId( $id ) {
    $this->id = $id;
  }

  public function getId() {
    return $this->id;
  }

  public function setName( $name ) {
    $this->name = $name;
  }

  public function getName() {
    return $this->name;
  }

  public function setEmail( $email ) {
    $this->email = $email;
  }

  public function getEmail() {
    return $this->email;
  }

  public function setIpAddress( $ip ) {
    $this->ipAddress = $ip;
  }

  public function getIpAddress() {
    return $this->ipAddress;
  }

  public function setUserAgent( $ua ) {
    $this->userAgent = $ua;
  }

  public function getUserAgent() {
    return $this->userAgent;
  }

  public function setWpUserId( $id ) {
    $this->wpUserId = $id;
  }

  public function getWpUserId() {
    return $this->wpUserId;
  }

  public function setLastActivityAt( $lastActivityAt ) {
    $this->lastActivityAt = $lastActivityAt;
  }

  public function getLastActivityAt() {
    return $this->lastActivityAt;
  }

  public function setCreatedAt( $createdAt ) {
    $this->createdAt = $createdAt;
  }
  
  public function getCreatedAt() {
    return $this->createdAt;
  }

  public function setUpdatedAt( $updatedAt ) {
    $this->updatedAt = $updatedAt;
  }

  public function getUpdatedAt() {
    return $this->updatedAt;
  }

  public function setActiveConversationId( $id ) {
    $this->activeConversationId = $id;
  }

  public function getActiveConversationId() {
    return $this->activeConversationId;
  }

  public function toArray() {
    $user = array();
    $user['id'] = $this->getId();
    $user['name'] = $this->getName();
    $user['email'] = $this->getEmail();
    $user['ipAddress'] = $this->getIpAddress();
    $user['userAgent'] = $this->getUserAgent();
    $user['lastActivityAt'] = $this->getLastActivityAt();
    $user['type'] = $this->getType();
    $user['activeConversationId'] = $this->getActiveConversationid();
    return $user;
  }

  abstract function login( $chat );
  abstract function logout( $chat );
  abstract function getType();
}
