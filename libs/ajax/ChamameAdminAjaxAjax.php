<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

class ChamameAdminAjaxAjax extends ChamameAjax {
  public function registerHooks() {
    // Login for visitor
    add_action( 'wp_ajax_nopriv_chamameLoginVisitor', array( $this, 'loginVisitor' ) );
    add_action( 'wp_ajax_chamameLoginVisitor', array( $this, 'loginVisitor' ) );

    // Login for operator
    add_action( 'wp_ajax_chamameLoginOperator', array( $this, 'loginOperator' ) );

    // Logout
    add_action( 'wp_ajax_nopriv_chamameLogout', array( $this, 'logout' ) );
    add_action( 'wp_ajax_chamameLogout', array( $this, 'logout' ) );

    // Send chat message
    add_action( 'wp_ajax_nopriv_chamameSendMessage', array( $this, 'sendMessage' ) );
    add_action( 'wp_ajax_chamameSendMessage', array( $this, 'sendMessage' ) );

    // Get unread chat message
    add_action( 'wp_ajax_nopriv_chamameGetUnreadMessage', array( $this, 'getUnreadMessage' ) );
    add_action( 'wp_ajax_chamameGetUnreadMessage', array( $this, 'getUnreadMessage' ) );

    // Get conversations
    add_action( 'wp_ajax_chamameGetConversations', array( $this, 'getConversations' ) );

    // Join conversations
    add_action( 'wp_ajax_chamameJoinConversation', array( $this, 'joinConversation' ) );

    // Leave conversations
    add_action( 'wp_ajax_chamameLeaveConversation', array( $this, 'leaveConversation' ) );
  }

  public function loginVisitor() {
    $this->verifyToken();

    if ( $this->session->isLoggedIn() ) {
      $this->session->destroy();
    }

    $params = new ChamameUserParams( $_POST, $_SERVER, ChamameUser::USER_TYPE_VISITOR, get_current_user_id() );
    $params->parse();
    if ( ! $params->isValid() ) {
      $this->fail( $params->getErrorMessage() );
      }
      $visitor = $this->userFactory->createNewUser( $params );

    try {
      $visitor->login( $this->chat );
    } catch ( Exception $e ) {
      $this->fail( $e->getMessage() );
    }

    $this->session->setOnlineUserId( $visitor->getId() );
    $this->session->setOnlineUserName( $visitor->getName() );
    $this->session->setOnlineUserType( $visitor->getType() );
    $this->session->setActiveConversationId( $visitor->getActiveConversationId() );

    $this->success( $visitor->toArray() );
  }

  public function loginOperator() {
    $this->verifyToken();

    if ( ! current_user_can( 'chamame_chat_with_visitor' ) ) {
      $this->fail( 'current user not allowed to chat with visitor' );
    }

    if ( $this->session->isLoggedIn() ) {
      $this->session->destroy();
    }

    try {
      $this->cleanup();
    } catch ( Exception $e ) {
      // Ignore
    }

    $wpUser = wp_get_current_user();
    $wpUserId = $wpUser->get( 'ID' );
    $params = new ChamameUserParams( $_POST, $_SERVER, ChamameUser::USER_TYPE_OPERATOR, $wpUserId );
    $operatorName = get_user_meta( $wpUserId, 'chamameOperatorName', true );
    $operatorName = $operatorName !== '' ? $operatorName : $wpUser->get( 'display_name' );
    $params->setName( $operatorName );
    $params->setEmail( $wpUser->get( 'user_email' ) );
    $params->parse();
    if ( ! $params->isValid() ) {
      $this->fail( $params->getErrorMessage() );
    }
    $operator = $this->userFactory->createNewUser( $params );

    try {
      $operator->login( $this->chat );
    } catch ( Exception $e ) {
      $this->fail( $e->getMessage() );
    }

    $this->session->setOnlineUserId( $operator->getId() );
    $this->session->setOnlineUserName( $operator->getName() );
    $this->session->setOnlineUserType( $operator->getType() );

    $this->success( $operator->toArray() );
  }

  public function logout() {
    $this->verifyToken();

    if ( ! $this->session->isLoggedIn() ) {
      $this->fail( 'not logged in' );
    }
    
    try {
      if ( ! $this->chat->existsOnlineUser( $this->session->getOnlineUserId() ) ) {
        $this->session->destroy();
        $this->fail( 'online user not exists' );
      }

      $onlineUserId = $this->session->getOnlineUserId();
      $user = $this->userFactory->getUser( $this->chat, $onlineUserId );
      $user->logout( $this->chat );
    } catch ( Exception $e ) {
      $this->fail( $e->getMessage() );
    }

    $this->session->destroy();
    $this->success();
  }

  public function sendMessage() {
    $this->verifyToken();

    if ( ! $this->session->isLoggedIn() ) {
      $this->fail( 'not logged in' );
    }

    try {
      if ( ! $this->chat->existsOnlineUser( $this->session->getOnlineUserId() ) ) {
        $this->session->destroy();
        $this->fail( 'online user not exists' );
      }
    } catch ( Exception $e ) {
      $this->fail( $e->getMessage() );
    }

    $params = new ChamameMessageParams( $_POST, $this->session );
    if ( ! $params->isValid() ) {
      $this->fail( $params->getErrorMessage() );
    }

    $message = $this->messageFactory->createNewMessage( $params );

    try {
      $this->chat->saveMessage( $message );
    } catch ( Exception $e ) {
      $this->fail( 'failed to save message' );
    }

    $this->success( $message->toArray() );
  }

  public function getUnreadMessage() {
    $this->verifyToken();

    if ( ! $this->session->isLoggedIn() ) {
      $this->fail( 'not logged in' );
    }

    $onlineUserId = $this->session->getOnlineUserId();

    try {
      if ( ! $this->chat->existsOnlineUser( $onlineUserId ) ) {
        $this->session->destroy();
        $this->fail( 'online user not exists' );
      }
    } catch ( Exception $e ) {
      $this->fail( $e->getMessage() );
    }

    try {
      $this->chat->updateLastActivityAt( $onlineUserId );
      if ( $this->session->isOperator() ) {
        $this->cleanup();
      }
    } catch ( Exception $e ) {
      // Ignore
    }
    
    $conversationId = $this->session->getActiveConversationid();
    if ( is_null( $conversationId ) ) {
      $this->fail( 'no conversation id' );
    }

    if ( ! isset( $_POST['lastReceivedMessageId'] ) ) {
      $this->fail( 'no last received message id' );
    }

    $lastReceivedMessageId = $_POST['lastReceivedMessageId'];

    if ( ! preg_match( '/^[0-9]+$/', $lastReceivedMessageId ) ) {
      $this->fail( 'invalid last received message id' );
    }

    try {
      $messages = $this->messageFactory->getUnreadMessageArray( $this->chat, $conversationId, $lastReceivedMessageId );
    } catch ( Exception $e ) {
      $this->fail( $e->getMessage() );
    }

    $this->success( $messages );
  }

  public function getConversations() {
    $this->verifyToken();
    if ( ! $this->session->isLoggedIn() ) {
      $this->fail( 'not logged in' );
    }

    try {
      if ( ! $this->chat->existsOnlineUser( $this->session->getOnlineUserId() ) ) {
        $this->session->destroy();
        $this->fail( 'online user not exists' );
      }
    } catch ( Exception $e ) {
      $this->fail( $e->getMessage() );
    }

    $onlineUserId = $this->session->getOnlineUserId();
    try {
      $this->chat->updateLastActivityAt( $onlineUserId );
      $this->cleanup();
    } catch ( Exception $e ) {
      // Ignore
    }

    try {
      $conversations = $this->chat->findConversations();
    } catch ( Exception $e ) {
      $this->fail( $e->getMessage() );
    }

    $conversationsArray = array();
    $timestampFormat = apply_filters( 'chamameConversationTimestampFormat', 'm月d日 H:i' );

    foreach ( $conversations as $c ) {
      $utime = strtotime( $c->created_at );
      $conversationsArray[] = array(
        'id' => $c->id,
        'timestamp' => date_i18n( $timestampFormat, $utime ),
        'senderName' => $c->sender_name,
        'message' => $c->message,
        'lastMessageId' => $c->last_message_id,
        'lastMessage' => $c->last_message,
        'lastMessageTruncated' => mb_strimwidth( $c->last_message, 0, 18 ),
        'lastMessageSenderName' => $c->last_message_sender_name,
        'lastMessageTimestamp' => $c->last_message_created_at
      );
    }

    $this->success( $conversationsArray );
  }

  public function joinConversation() {
    $this->verifyToken();
    if ( ! $this->session->isLoggedIn() ) {
      $this->fail( 'not logged in' );
    }
    
    try { 
      if ( ! $this->chat->existsOnlineUser( $this->session->getOnlineUserId() ) ) {
        $this->session->destroy();
        $this->fail( 'online user not exists' );
      }
    } catch ( Exception $e ) {
      $this->fail( $e->getMessage() );
    }

    if ( ! isset( $_POST['conversationId'] ) ) {
      $this->fail( 'no conversation id' );
    } else {
      $conversationId = $_POST['conversationId'];
    }

    if ( ! preg_match( '/^[0-9]+$/', $conversationId ) ) {
      $this->fail( 'invalid last received message id' );
    }

    $onlineUserId = $this->session->getOnlineUserId();
    $currentConversationId = $this->session->getActiveConversationId();

    try {
      if ( $currentConversationId ) {
        $this->chat->leaveConversation( $onlineUserId, $currentConversationId );
      }
      $this->chat->joinConversation( $onlineUserId, $conversationId );
      $this->chat->activateConversation( $onlineUserId, $conversationId );
      $this->session->setActiveConversationId( $conversationId );
    } catch ( Exception $e ) {
      $this->fail( $e->getMessage() );
    }

    $this->success();
  }

  public function leaveConversation() {
    $this->verifyToken();
    if ( ! $this->session->isLoggedIn() ) {
      $this->fail( 'not logged in' );
    }

    try {
      if ( ! $this->chat->existsOnlineUser( $this->session->getOnlineUserId() ) ) {
        $this->session->destroy();
        $this->fail( 'online user not exists' );
      }
    } catch ( Exception $e ) {
      $this->fail( $e->getMessage() );
    }

    $onlineUserId = $this->session->getOnlineUserId();
    $conversationId = $this->session->getActiveConversationId();
    if ( is_null( $conversationId ) ) {
      $this->fail( 'not join conversation' );
    }

    try {
      $this->chat->leaveConversation( $onlineUserId, $conversationId );
      $this->chat->deactivateConversation( $onlineUserId );
      $this->session->setActiveConversationId( null );
    } catch ( Exception $e ) {
      $this->fail( $e->getMessage() );
    }

    $this->success();
  }

  private function cleanup() {
    try {
      $this->chat->deleteInactiveUser();
      $this->chat->deleteMissingRelation();
      $this->chat->deleteMissingConversation();
      $this->chat->deleteMissingMessage();
    } catch ( Exception $e ) {
      throw new Exception( 'failed to cleanup', 0, $e );
    }
  }

  private function success( $data = null ) {
    $response = array();
    $response['status'] = 'success';
    $response['statusDetail'] = 'ok';
    if ( isset( $data ) ) {
      $response['data'] = $data;
    }
    $this->outputJSON( $response );
  }

  private function fail( $message ) {
    $response = array();
    $response['status'] = 'failure';
    $response['statusDetail'] = $message;
    $this->outputJSON( $response );
  }

  private function outputJSON( $values ) {
    header( 'Content-Type: application/json' );
    echo json_encode( $values );
    exit;
  }
}

