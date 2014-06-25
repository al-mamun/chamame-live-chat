<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

abstract class ChamameAjax {
  protected $chat;
  protected $session;
  protected $config;
  protected $userFactory;
  protected $messageFactory;

  public function __construct( $chat, $session, $config, $userFactory, $messageFactory ) {
    $this->chat = $chat;
    $this->session = $session;
    $this->config = $config;
    $this->userFactory = $userFactory;
    $this->messageFactory = $messageFactory;
  }

  protected function verifyToken() {
    check_ajax_referer( 'chamameLiveChat', 'token' );
  }

  abstract public function registerHooks();
}
