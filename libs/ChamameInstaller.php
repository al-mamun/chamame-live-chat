<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

class ChamameInstaller {
  private $config;

  public function __construct( $config ) {
    $this->config = $config;
  }
  
  public function install() {
    $this->createTable();
    $this->setupUserRole();
  }

  private function createTable() {
    global $wpdb;

    $messageTable = $wpdb->prefix . ChamameChat::MESSAGE_TABLE;
    $wpdb->query( "DROP TABLE IF EXISTS `{$messageTable}`;" );
    $wpdb->query(
      "CREATE TABLE `{$messageTable}` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `conversation_id` bigint(20) UNSIGNED NOT NULL,
        `sender_name` varchar(40) NOT NULL,
        `sender_online_user_id` bigint(20) UNSIGNED,
        `message` varchar(500) NOT NULL,
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`id`)
      ) DEFAULT CHARSET=utf8;"
    );

    $onlineTable = $wpdb->prefix . ChamameChat::ONLINE_TABLE;
    $wpdb->query( "DROP TABLE IF EXISTS `{$onlineTable}`;" );
    $wpdb->query(
      "CREATE TABLE `{$onlineTable}` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `type` varchar(40) NOT NULL,
        `name` varchar(40) NOT NULL,
        `email` varchar(345) NOT NULL,
        `ip_address` varchar(46),
        `user_agent` varchar(255),
        `wp_user_id` bigint(20) UNSIGNED DEFAULT NULL,
        `active_conversation_id` bigint(20) UNSIGNED DEFAULT NULL,
        `last_activity_at` datetime NOT NULL,
        `created_at` datetime NOT NULL,
        `updated_at` datetime NOT NULL,
        PRIMARY KEY (`id`)
      ) DEFAULT CHARSET=utf8;"
    );

    $conversationTable = $wpdb->prefix . ChamameChat::CONVERSATION_TABLE;
    $wpdb->query( "DROP TABLE IF EXISTS `{$conversationTable}`;" );
    $wpdb->query(
      "CREATE TABLE `{$conversationTable}` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` varchar(40),
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`id`)
      ) DEFAULT CHARSET=utf8;"
    );

    $conversationOnlineTable = $wpdb->prefix . ChamameChat::CONVERSATION_ONLINE_TABLE;
    $wpdb->query( "DROP TABLE IF EXISTS `{$conversationOnlineTable}`;" );
    $wpdb->query(
      "CREATE TABLE `{$conversationOnlineTable}` (
        `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `conversation_id` bigint(20) UNSIGNED NOT NULL,
        `online_user_id` bigint(20) UNSIGNED NOT NULL,
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE (`conversation_id`, `online_user_id`)
      ) DEFAULT CHARSET=utf8;"
    );
  }

  private function setupUserRole() {
    $adminRole = get_role( 'administrator' );
    $adminRole->add_cap( 'chamame_chat_with_visitor' );
    $adminRole->add_cap( 'chamame_view_chat_log' );
    $adminRole->add_cap( 'chamame_edit_chat_options' );

    $textDomain = $this->config->getTextDomain();
    remove_role( 'chamameOperator' );
    $operatorRole = add_role( 'chamameOperator', __( 'Chat Operator', $textDomain ) );
    $operatorRole->add_cap( 'read' );
    $operatorRole->add_cap( 'chamame_chat_with_visitor' );
  }

  public function uninstall() {
    $this->dropTable();
    $this->cleanupUserRole();
    $this->deleteOption();
  }

  private function dropTable() {
    global $wpdb;

    $messageTable = $wpdb->prefix . ChamameChat::MESSAGE_TABLE;
    $wpdb->query( "DROP TABLE IF EXISTS `{$messageTable}`;" );

    $onlineTable = $wpdb->prefix . ChamameChat::ONLINE_TABLE;
    $wpdb->query( "DROP TABLE IF EXISTS `{$onlineTable}`;" );

    $conversationTable = $wpdb->prefix . ChamameChat::CONVERSATION_TABLE;
    $wpdb->query( "DROP TABLE IF EXISTS `{$conversationTable}`;" );

    $conversationOnlineTable = $wpdb->prefix . ChamameChat::CONVERSATION_ONLINE_TABLE;
    $wpdb->query( "DROP TABLE IF EXISTS `{$conversationOnlineTable}`;" );
  }

  private function cleanupUserRole() {
    $adminRole = get_role( 'administrator' );
    $adminRole->remove_cap( 'chamame_chat_with_visitor' );
    $adminRole->remove_cap( 'chamame_view_chat_log' );
    $adminRole->remove_cap( 'chamame_edit_chat_options' );

    remove_role( 'chamameOperator' );
  }

  private function deleteOption() {
    delete_option( ChamameConfiguration::VERSION_KEY );
    delete_option( ChamameConfiguration::OPTION_KEY );
  }
}
