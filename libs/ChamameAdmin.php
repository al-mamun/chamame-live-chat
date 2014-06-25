<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

abstract class ChamameAdmin {
  protected $chat;
  protected $session;
  protected $config;

  public function __construct( $chat, $session, $config ) {
    $this->chat = $chat;
    $this->session = $session;
    $this->config = $config;
  }

  abstract public function registerHooks();
}
