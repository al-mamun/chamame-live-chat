<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

class ChamameSession {
  const ONLINE_USER_ID_KEY = 'chamameOnlineUserId';
  const ONLINE_USER_NAME_KEY = 'chamameOnlineUserName';
  const ONLINE_USER_TYPE_KEY = 'chamameOnlineUserType';
  const ACTIVE_CONVERSATION_ID_KEY = 'activeConversationId';

  public function start() {
    if ( ! session_id() ) {
      session_start();
    }
  }

  public function setOnlineUserId( $id ) {
    $_SESSION[self::ONLINE_USER_ID_KEY] = $id;
  }

  public function getOnlineUserId() {
    if ( isset( $_SESSION[self::ONLINE_USER_ID_KEY] ) ) {
      return $_SESSION[self::ONLINE_USER_ID_KEY];
    }
    return null;
  }

  public function setOnlineUserName( $name ) {
    $_SESSION[self::ONLINE_USER_NAME_KEY] = $name;
  }

  public function getOnlineUserName() {
    if ( isset( $_SESSION[self::ONLINE_USER_NAME_KEY] ) ) {
      return $_SESSION[self::ONLINE_USER_NAME_KEY];
    }
    return null;
  }

  public function setOnlineUserType( $type ) {
    $_SESSION[self::ONLINE_USER_TYPE_KEY] = $type;
  }

  public function isOperator() {
    if ( isset( $_SESSION[self::ONLINE_USER_TYPE_KEY] ) ) {
      return ( $_SESSION[self::ONLINE_USER_TYPE_KEY] === 'operator' );
    }
    return false;
  }

  public function setActiveConversationId( $id ) {
    $_SESSION[self::ACTIVE_CONVERSATION_ID_KEY] = $id;
  }

  public function getActiveConversationId() {
    if ( isset( $_SESSION[self::ACTIVE_CONVERSATION_ID_KEY] ) ) {
      return $_SESSION[self::ACTIVE_CONVERSATION_ID_KEY];
    }
    return null;
  }

  public function isLoggedIn() {
    if ( isset( $_SESSION[self::ONLINE_USER_ID_KEY] ) ) {
      return true;
    }
    return false;
  }

  public function destroy() {
    unset( $_SESSION[self::ONLINE_USER_ID_KEY] );
    unset( $_SESSION[self::ONLINE_USER_NAME_KEY] );
    unset( $_SESSION[self::ONLINE_USER_TYPE_KEY] );
    unset( $_SESSION[self::ACTIVE_CONVERSATION_ID_KEY] );
  }
}
