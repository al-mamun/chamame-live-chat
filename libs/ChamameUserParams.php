<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class ChamameUserParams {
  const NAME_KEY = 'userName';
  const EMAIL_KEY = 'userEmail';

  private $post;
  private $server;
  private $errorMessage;
  private $valid;

  private $name;
  private $email;
  private $type;
  private $ipAddress;
  private $userAgent;
  private $wpUserId;

  public function __construct( $post, $server, $type, $wpUserId ) {
    $this->post = $post;
    $this->server = $server;
    $this->valid = false;
    $this->type = $type;
    $this->wpUserId = $wpUserId;
  }

  public function parse() {
    if ( is_null( $this->name ) ) {
      if ( ! isset( $this->post[self::NAME_KEY] ) ) {
        $this->errorMessage = 'no name';
        return;
      }
      $this->name = trim( $this->post[self::NAME_KEY] );
    }

    if ( mb_strlen( $this->name ) > 40 ) {
      $this->errorMessage = 'too long name';
      return;
    }

    if ( is_null( $this->email ) ) {
      if ( ! isset( $this->post[self::EMAIL_KEY] ) ) {
        $this->errorMessage = 'no email';
        return;
      }
      $this->email = trim( $this->post[self::EMAIL_KEY] );
    }

    if ( mb_strlen( $this->email ) > 345 ) {
      $this->errorMessage = 'too long email';
      return;
    }

    if ( ! is_email( $this->email ) ) {
      $this->errorMessage = 'invalid email';
      return;
    }

    $ipAddress = '';
    /* placeholder for proxy request
     * use $_SERVER['HTTP_CLIENT_IP'] or $_SERVER['HTTP_X_FORWARDED_FOR']
     */
    if ( isset( $this->server['REMOTE_ADDR'] ) ) {
      $ipAddress = trim( $this->server['REMOTE_ADDR'] );
    }
    $this->ipAddress = $ipAddress;

    $userAgent = '';
    if ( isset( $this->server['HTTP_USER_AGENT'] ) ) {
      $userAgent = trim( $this->server['HTTP_USER_AGENT'] );
    }
    $this->userAgent = $userAgent;

    $this->valid = true;
  }

  public function getName() {
    return $this->name;
  }

  public function setName( $name ) {
    $this->name = $name;
  }

  public function getEmail() {
    return $this->email;
  }

  public function setEmail( $email ) {
    $this->email = $email;
  }

  public function getIpAddress() {
    return $this->ipAddress;
  }

  public function getUserAgent() {
    return $this->userAgent;
  }

  public function getType() {
    return $this->type;
  }

  public function getWpUserId() {
    return $this->wpUserId;
  }

  public function getErrorMessage() {
    return $this->errorMessage;
  }

  public function isValid() {
    return $this->valid;
  }
}
