<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class ChamameChat {
  const ONLINE_TABLE = 'chamame_online_users';
  const MESSAGE_TABLE = 'chamame_messages';
  const CONVERSATION_TABLE = 'chamame_conversations';
  const CONVERSATION_ONLINE_TABLE = 'chamame_conversations_online_users';

  private $config;

  public function __construct( $config ) {
    $this->config = $config;
  }

  public function registerOnlineUser( $user ) {
    global $wpdb;
    $wpdb->hide_errors();
    $onlineTable = $wpdb->prefix . self::ONLINE_TABLE;

    $stmt = $wpdb->prepare(
      "INSERT INTO {$onlineTable} (
        `type`,
        `name`,
        `email`,
        `ip_address`,
        `user_agent`,
        `wp_user_id`,
        `last_activity_at`,
        `created_at`,
        `updated_at`
      ) VALUES ( %s, %s, %s, %s, %s, %d, NOW(), NOW(), NOW() )",
      $user->getType(),
      $user->getName(),
      $user->getEmail(),
      $user->getIpAddress(),
      $user->getUserAgent(),
      $user->getWpUserId()
    );

    if ( $wpdb->query( $stmt ) === false ) {
      throw new Exception( 'failed to insert online user' );
    }

    $user->setId( (int)$wpdb->insert_id );
  }

  public function findOnlineUser( $onlineUserId ) {
    global $wpdb;
    $wpdb->hide_errors();

    $onlineTable = $wpdb->prefix . self::ONLINE_TABLE;

    $stmt = $wpdb->prepare( "SELECT * FROM {$onlineTable} WHERE id = %d", $onlineUserId );
    $result = $wpdb->get_row( $stmt, OBJECT );
    if ( is_null( $result ) ) {
      throw new Exception( 'failed to select online user' );
    }
    return $result;
  }

  public function createConversation() {
    global $wpdb;
    $wpdb->hide_errors();
    $conversationTable = $wpdb->prefix . self::CONVERSATION_TABLE;

    $result = $wpdb->query( "INSERT INTO {$conversationTable} ( `created_at` ) VALUES ( NOW() )" );
    if ( $result === false ) {
      throw new Exception( 'failed to create conversation' );
    }

    $conversationId = (int) $wpdb->insert_id;
    return $conversationId;
  }
  
  public function joinConversation( $onlineUserId, $conversationId ) {
    global $wpdb;
    $wpdb->hide_errors();

    $conversationOnlineTable = $wpdb->prefix . self::CONVERSATION_ONLINE_TABLE;

    $conversationOnlineStmt = $wpdb->prepare(
      "INSERT INTO {$conversationOnlineTable} (
        `conversation_id`,
        `online_user_id`,
        `created_at`
      ) VALUES ( %d, %d, NOW() )",
      $conversationId,
      $onlineUserId
    );
    if ( $wpdb->query( $conversationOnlineStmt ) === false ) {
      throw new Exception( 'failed to insert ' . $conversationOnlineTable );
    }
  }

  public function leaveConversation( $onlineUserId, $conversationId ) {
    global $wpdb;
    $wpdb->hide_errors();

    $conversationOnlineTable = $wpdb->prefix . self::CONVERSATION_ONLINE_TABLE;
    $result = $wpdb->delete(
      $conversationOnlineTable,
      array(
        'online_user_id' => $onlineUserId,
        'conversation_id' => $conversationId
      ),
      array( '%d', '%d' )
    );
    if ( $result  === false ) {
      throw new Exception( 'failed to delete online user' );
    }
  }

  public function activateConversation( $onlineUserId, $conversationId ) {
    global $wpdb;
    $wpdb->hide_errors();
    $onlineTable = $wpdb->prefix . self::ONLINE_TABLE;
    $result = $wpdb->update(
      $onlineTable,
      array( 'active_conversation_id' => $conversationId ),
      array( 'id' => $onlineUserId ),
      array( '%d' ),
      array( '%d' )
    );
    if ( $result === false ) {
      throw new Exception( 'failed to update' . $onlineTable );
    }
  }

  public function deactivateConversation( $onlineUserId ) {
    global $wpdb;
    $wpdb->hide_errors();

    $onlineTable = $wpdb->prefix . self::ONLINE_TABLE;
    $result = $wpdb->update(
      $onlineTable,
      array( 'active_conversation_id' => null ),
      array( 'id' => $onlineUserId ),
      array( '%s' ),
      array( '%d' )
    );
    if ( $result === false ) {
      throw new Exception( 'failed to update' . $onlineTable );
    }
  }

  public function saveMessage( $message ) {
    global $wpdb;
    $wpdb->hide_errors();
    $messageTable = $wpdb->prefix . self::MESSAGE_TABLE;

    $currentDate = date_i18n( 'Y-m-d H:i:s' );
    $result = $wpdb->insert(
      $messageTable,
      array(
        'conversation_id' => $message->getConversationId(),
        'sender_name' => $message->getSenderName(),
        'sender_online_user_id' => $message->getSenderOnlineUserId(),
        'message' => $message->getMessage(),
        'created_at' => $currentDate
      ),
      array( '%d', '%s', '%d', '%s', '%s' )
    );
    if ( $result === false ) {
      throw new Exception( 'failed to insert' . $messageTable );
    }
    $message->setId( (int)$wpdb->insert_id );
    $message->setCreatedAt( $currentDate );
  }

  public function findUnreadMessages( $conversationId, $lastReceivedMessageId ) {
    global $wpdb;
    $wpdb->hide_errors();
    $messageTable = $wpdb->prefix . self::MESSAGE_TABLE;

    $stmt = $wpdb->prepare(
      "SELECT * FROM {$messageTable}
        WHERE
          conversation_id = %d
        AND
          id > %d
        ORDER BY
          id ASC",
      $conversationId,
      $lastReceivedMessageId
    );
    $result = $wpdb->get_results( $stmt, OBJECT );
    if ( is_null( $result ) ) {
      throw new Exception( 'failed to select unread messages' );
    }
    return $result;
  }

  public function findOnlineUsers() {
    global $wpdb;
    $wpdb->hide_errors();
    $onlineTable = $wpdb->prefix . self::ONLINE_TABLE;

    $result = $wpdb->get_results( "SELECT * FROM {$onlineTable}", OBJECT );
    if ( is_null( $result ) ) {
      throw new Exception( 'failed to select online users' );
    }
    return $result;
  }

  public function findConversations() {
    global $wpdb;
    $wpdb->hide_errors();
    $conversationTable = $wpdb->prefix . self::CONVERSATION_TABLE;
    $conversationOnlineTable = $wpdb->prefix . self::CONVERSATION_ONLINE_TABLE;
    $messageTable = $wpdb->prefix . self::MESSAGE_TABLE;

    $result = $wpdb->get_results(
      "SELECT
          C.id,
          C.name,
          C.created_at,
          CM.sender_name,
          CM.message,
          CM2.id AS last_message_id,
          CM2.message AS last_message,
          CM2.sender_name AS last_message_sender_name,
          CM2.created_at AS last_message_created_at
        FROM
          {$conversationTable} as C
        INNER JOIN
          (
            SELECT
              M.id,
              M.conversation_id,
              M.sender_name,
              M.message
            FROM
              (
                SELECT
                    *
                  FROM
                    {$messageTable}
                  ORDER BY
                    id ASC
              ) as M
            GROUP BY
              M.conversation_id
          ) as CM
        ON
          C.id = CM.conversation_id
        LEFT OUTER JOIN
          (
            SELECT
              M2.id,
              M2.conversation_id,
              M2.message,
              M2.sender_name,
              M2.created_at
            FROM
              (
                SELECT
                    *
                  FROM
                    {$messageTable}
                  ORDER BY
                    id DESC
              ) as M2
            GROUP BY
              M2.conversation_id
          ) as CM2
        ON
          C.id = CM2.conversation_id
        ORDER BY
          CM.id DESC",
      OBJECT
    );
    if ( is_null( $result ) ) {
      throw new Exception( 'failed to select online users' );
    }
    return $result;
  }

  public function deleteOnlineUser( $onlineUserId ) {
    global $wpdb;
    $wpdb->hide_errors();
    $onlineTable = $wpdb->prefix . self::ONLINE_TABLE;
    if ( $wpdb->delete( $onlineTable, array( 'id' => $onlineUserId ), array( '%d' ) ) === false ) {
      throw new Exception( 'failed to delete online user' );
    }

    // leave all conversations
    $conversationOnlineTable = $wpdb->prefix . self::CONVERSATION_ONLINE_TABLE;
    if ( $wpdb->delete( $conversationOnlineTable, array( 'online_user_id' => $onlineUserId ), array( '%d' ) ) === false ) {
      throw new Exception( 'failed to delete conversation-online' );
    }
  }

  public function updateLastActivityAt( $onlineUserId ) {
    global $wpdb;
    $wpdb->hide_errors();

    $onlineTable = $wpdb->prefix . self::ONLINE_TABLE;
    $stmt = $wpdb->prepare( "UPDATE {$onlineTable} SET `last_activity_at` = NOW() WHERE id = %d", $onlineUserId );
    if ( $wpdb->query( $stmt ) === false ) {
      throw new Exception( 'failed to update ' . $onlineTable );
    }
  }

  public function deleteInactiveUser() {
    global $wpdb;
    $wpdb->hide_errors();

    $onlineTable = $wpdb->prefix . self::ONLINE_TABLE;
    $result = $wpdb->query( "DELETE FROM {$onlineTable} WHERE last_activity_at < DATE_SUB( NOW(), INTERVAL 1 MINUTE)" );
    if ( $result === false ) {
      throw new Exception( 'failed to delete ' . $onlineTable );
    }
  }

  public function deleteMissingRelation() {
    global $wpdb;
    $wpdb->hide_errors();

    $conversationOnlineTable = $wpdb->prefix . self::CONVERSATION_ONLINE_TABLE;
    $onlineTable = $wpdb->prefix . self::ONLINE_TABLE;
    $result = $wpdb->query(
      "DELETE CO
        FROM
          {$conversationOnlineTable} AS CO
        LEFT OUTER JOIN
          {$onlineTable} AS OU
        ON
          CO.online_user_id = OU.id
        WHERE
          OU.id is null"
    );
    if ( $result === false ) {
      throw new Exception( 'failed to delete ' . $conversationOnlineTable );
    }
  }

  public function deleteMissingConversation() {
    global $wpdb;
    $wpdb->hide_errors();

    $conversationTable = $wpdb->prefix . self::CONVERSATION_TABLE;
    $conversationOnlineTable = $wpdb->prefix . self::CONVERSATION_ONLINE_TABLE;
    $result = $wpdb->query(
      "DELETE C
        FROM
          {$conversationTable} AS C
        LEFT OUTER JOIN
          {$conversationOnlineTable} AS CO
        ON
          C.id = CO.conversation_id
        WHERE
          CO.conversation_id is null"
    );
    if ( $result === false ) {
      throw new Exception( 'failed to delete ' . $conversationTable );
    }
  }

  public function deleteMissingMessage() {
    global $wpdb;
    $wpdb->hide_errors();

    $messageTable = $wpdb->prefix . self::MESSAGE_TABLE;
    $conversationTable = $wpdb->prefix . self::CONVERSATION_TABLE;

    $result = $wpdb->query(
      "DELETE M
        FROM
          {$messageTable} AS M
        LEFT OUTER JOIN
          {$conversationTable} AS C
        ON
          M.conversation_id = C.id
        WHERE
          C.id is null"
    );
    if ( $result === false ) {
      throw new Exception( 'failed to delete ' . $messageTable );
    }
  }

  public function existsOnlineUser( $id ) {
    global $wpdb;
    $wpdb->hide_errors();

    $onlineTable = $wpdb->prefix . self::ONLINE_TABLE;
    $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$onlineTable} WHERE id = %d", $id ) );

    if ( is_null( $count ) ) {
      throw new Exception( 'failed to count ' . $onlineTable );
    }
    return ( (int)$count > 0 );
  }
}
