<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

abstract class ChamameBuilder {
  protected $chamame;

  public function __construct( $pluginFile ) {
    $config = new ChamameConfiguration( $pluginFile );
    $chat = new ChamameChat( $config );
    $session = new ChamameSession( $config );
    $this->chamame = new Chamame( $chat, $session, $config );
  }

  public function buildInstaller() {
    $config = $this->chamame->getConfig();
    $this->chamame->setInstaller( new ChamameInstaller( $config ) );
  }

  abstract public function buildChatClient();
  abstract public function buildAdmin();
  abstract public function buildAjax();

  public function getResult() {
    return $this->chamame;
  }
}
