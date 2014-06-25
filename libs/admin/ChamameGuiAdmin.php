<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

class ChamameGuiAdmin extends ChamameAdmin {
  private $adminConsolePageHookname;

  public function registerHooks() {
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueueJavascripts' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueueStylesheets' ) );
    add_action( 'admin_menu', array( $this, 'buildMenu' ) );
  }

  public function render() {
    $template = $this->config->getPluginDirPath() . '/templates/adminConsole.php';
    $textDomain = $this->config->getTextDomain();
    $pluginUrl = $this->config->getPluginUrl();
    include( $template );
  }

  public function enqueueJavascripts( $pageHookname ) {
    if ( $pageHookname === $this->adminConsolePageHookname ) {
      $jsDir = $this->config->getPluginUrl() . '/js';
      $version = $this->config->getVersion();

      $knockoutSuffix = '';
      $suffix = '.min';

      if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $knockoutSuffix = '.debug';
        $suffix = '';
      }

      wp_register_script(
        'knockout',
        $jsDir . '/deps/knockout-3.1.0' . $knockoutSuffix . '.js',
        array(),
        $version,
        false
      );
      wp_enqueue_script( 'knockout' );

      wp_register_script(
        'knockoutValidation',
        $jsDir . '/deps/knockout.validation' . $suffix . '.js',
        array( 'knockout' ),
        $version,
        false
      );
      wp_enqueue_script( 'knockoutValidation' );

      wp_register_script(
        'autosize',
        $jsDir . '/deps/jquery.autosize' . $suffix . '.js',
        array( 'jquery' ),
        $version,
        false
      );
      wp_enqueue_script( 'autosize' );

      wp_register_script(
        'chamameAdmin',
        $jsDir . '/admin' . $suffix . '.js',
        array( 'jquery', 'knockout', 'knockoutValidation', 'autosize' ),
        $version,
        false
      );
      wp_enqueue_script( 'chamameAdmin' );

      $ajaxUrl = str_replace( array( 'https:', 'http:' ), '', admin_url( 'admin-ajax.php' ) );
      $token = wp_create_nonce( 'chamameLiveChat' );
      $textDomain = $this->config->getTextDomain();
      wp_localize_script(
        'chamameAdmin',
        'chamameParams',
        array(
          'ajaxUrl' => $ajaxUrl,
          'loggedIn' => $this->session->isLoggedIn(),
          'conversationId' => $this->session->getActiveConversationId(),
          'token' => $token,
          'text' => array(
            'error' => __( 'Something went wrong. Please try again', $textDomain )
          )
        )
      );
    }
  }

  public function enqueueStylesheets( $pageHookname ) {
    if ( $pageHookname === $this->adminConsolePageHookname ) {
      $cssUrl = $this->config->getPluginUrl() . '/css/admin.css';
      $version = $this->config->getVersion();

      wp_register_style(
        'chamameAdmin',
        $cssUrl,
        array(),
        $version,
        'all'
      );
      wp_enqueue_style( 'chamameAdmin' );
    }
  }

  public function buildMenu() {
    $textDomain = $this->config->getTextDomain();
    $this->adminConsolePageHookname = add_menu_page(
      _x( 'Chat', 'admin page title', $textDomain ),
      _x( 'Chat', 'admin menu label', $textDomain ),
      'chamame_chat_with_visitor',
      'chamameAdminConsole',
      array( $this, 'render' ),
      '',
      26
    );
  }
}
