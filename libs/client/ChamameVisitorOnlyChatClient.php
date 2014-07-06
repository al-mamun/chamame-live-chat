<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

class ChamameVisitorOnlyChatClient extends ChamameChatClient {
  public function registerHooks() {
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueueJavascripts' ) );
    add_action( 'wp_enqueue_scripts', array( $this, 'enqueueStylesheets' ) );
    add_action( 'wp_footer', array( $this, 'render' ) );
  }

  public function render() {
    $template = $this->config->getPluginDirPath() . '/templates/client.php';

    $textDomain = $this->config->getTextDomain();
    $isOperator = $this->session->isOperator();
    include( $template );
  }

  public function enqueueJavascripts() {
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
      'chamameClient',
      $jsDir . '/client' . $suffix . '.js',
      array( 'jquery', 'knockout', 'knockoutValidation', 'autosize' ),
      $version,
      false
    );
    wp_enqueue_script( 'chamameClient' );

    $ajaxUrl = str_replace( array( 'https:', 'http:' ), '', admin_url( 'admin-ajax.php' ) );
    $token = wp_create_nonce( 'chamameLiveChat' );
    $textDomain = $this->config->getTextDomain();
    wp_localize_script(
      'chamameClient',
      'chamameParams',
      array(
        'ajaxUrl' => $ajaxUrl,
        'loggedIn' => $this->session->isLoggedIn(),
        'token' => $token,
        'text' => array(
          'nameRequired' =>  __( 'An name is required', $textDomain ),
          'emailFormat' =>  __( 'Doesn\'t look like a valid email', $textDomain ),
          'emailRequired' => __( 'An email is required', $textDomain ),
          'error' => __( 'We\'re sorry, but something went wrong. Please try again', $textDomain )
        )
      )
    );
  }

  public function enqueueStylesheets() {
    $cssUrl = $this->config->getPluginUrl() . '/css/client.css';
    $version = $this->config->getVersion();

    wp_register_style(
      'chamameClient',
      $cssUrl,
      array(),
      $version,
      'all'
    );
    wp_enqueue_style( 'chamameClient' );
  }
}
