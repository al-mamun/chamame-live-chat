<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class ChamameOperator extends ChamameUser {
  public function getType() {
    return self::USER_TYPE_OPERATOR;
  }

  public function login( $chat ) {
    try {
      $chat->registerOnlineUser( $this );
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
