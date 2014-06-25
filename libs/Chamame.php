<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

class Chamame {
  private $chat;
  private $session;
  private $config;
  private $installer;
  private $chatClient;
  private $admin;
  private $ajax;
  
  public function __construct( $chat, $session, $config ) {
    $this->chat = $chat;
    $this->session = $session;
    $this->config = $config;
  }

  public function start() {
    $this->verifyVersion();

    $pluginFile = $this->config->getPluginFilePath();
    register_activation_hook( $pluginFile, array( $this, 'activate' ) );
    register_deactivation_hook( $pluginFile, array( $this, 'deactivate' ) );

    add_action( 'plugins_loaded', array( $this, 'init' ) );
    add_action( 'wp_login', array( $this, 'clearSession' ) );
    add_action( 'wp_logout', array( $this, 'clearSession' ) );
  }

  private function verifyVersion() {
    $meta = get_file_data( $this->config->getPluginFilePath(), array( 'version' => 'Version' ) );
    $version = $meta['version'];
    $currentVersion = $this->config->getVersion();
    if ( version_compare( $currentVersion, $version, '=' ) ) {
      return;
    }

    // Placeholder for upgrade and downgrade

    $this->config->setVersion( $version );
  }

  public function activate() {
    if ( current_user_can( 'activate_plugins' ) ) {
      $langDir = dirname( plugin_basename( $this->config->getPluginFilePath() ) ). '/languages/';
      $textDomain = $this->config->getTextDomain();
      load_plugin_textdomain( $textDomain, false, $langDir );
      $this->installer->install();
    }
  }

  public function deactivate() {
    // Nothing to do
    return;
  }

  public function init() {
    $langDir = dirname( plugin_basename( $this->config->getPluginFilePath() ) ). '/languages/';
    $textDomain = $this->config->getTextDomain();
    load_plugin_textdomain( $textDomain, false, $langDir );

    $this->session->start();

    $this->chatClient->registerHooks();
    $this->admin->registerHooks();
    $this->ajax->registerHooks();
  }

  public function clearSession() {
    $this->session->destroy();
  }

  public function setChatClient( $chatClient ) {
    $this->chatClient = $chatClient;
  }

  public function setAdmin( $admin ) {
    $this->admin = $admin;
  }

  public function setAjax( $ajax ) {
    $this->ajax = $ajax;
  }

  public function setInstaller( $installer ) {
    $this->installer = $installer;
  }

  public function getConfig() {
    return $this->config;
  }

  public function getSession() {
    return $this->session;
  }

  public function getChat() {
    return $this->chat;
  }
}
