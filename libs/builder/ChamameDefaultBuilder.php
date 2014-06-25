<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

class ChamameDefaultBuilder extends ChamameBuilder {
  public function buildChatClient() {
    $chat = $this->chamame->getChat();
    $session = $this->chamame->getSession();
    $config = $this->chamame->getConfig();
    $this->chamame->setChatClient( new ChamameVisitorOnlyChatClient( $chat, $session, $config ) );
  }

  public function buildAdmin() {
    $chat = $this->chamame->getChat();
    $session = $this->chamame->getSession();
    $config = $this->chamame->getConfig();
    $this->chamame->setAdmin( new ChamameNoopAdmin( $chat, $session, $config ) );
  }

  public function buildAjax() {
    $chat = $this->chamame->getChat();
    $session = $this->chamame->getSession();
    $config = $this->chamame->getConfig();
    $userFactory = new ChamameUserFactory();
    $messageFactory = new ChamameMessageFactory();
    $this->chamame->setAjax( new ChamameNoopAjax( $chat, $session, $config, $userFactory, $messageFactory ) );
  }
}

