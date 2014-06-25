<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class ChamameUserFactory {
  public function createNewUser( $args ) {
    $email = $args->getEmail();
    $name = $args->getName();
    $type = $args->getType();

    if ( isset( $type ) && $type === ChamameUser::USER_TYPE_VISITOR ) {
      $user = new ChamameVisitor( $name, $email );
    } else {
      $user = new ChamameOperator( $name, $email );
    }
    $user->setIpAddress( $args->getIpAddress() );
    $user->setUserAgent( $args->getUserAgent() );
    $user->setWpUserId( $args->getWpUserId() );

    return $user;
  }

  public function getUser( $chat, $onlineUserId ) {
    try {
      $userRecord = $chat->findOnlineUser( $onlineUserId );
      $name = $userRecord->name;
      $email = $userRecord->email;
      $type = $userRecord->type;
      if ( $type === ChamameUser::USER_TYPE_VISITOR ) {
        $user = new ChamameVisitor( $name, $email );
      } else {
        $user = new ChamameOperator( $name, $email );
      }
      $user->setId( $userRecord->id );
      $user->setIpAddress( $userRecord->ip_address );
      $user->setUserAgent( $userRecord->user_agent );
      $user->setWpUserId( $userRecord->wp_user_id );
      $user->setActiveConversationId( $userRecord->active_conversation_id );
      return $user;
    } catch ( Exception $e ) {
      throw new Exception( 'failed to get user', 0, $e );
    }
  }
}
