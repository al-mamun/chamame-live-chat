<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

class ChamameConfiguration {
  const OPTION_KEY = 'chamameLiveChat';
  const VERSION_KEY = 'chamameLiveChatVersion';

  private $options;
  private $version;
  private $pluginFilePath;
  private $pluginDirPath;
  private $pluginUrl;
  private $textDomain;

  public function __construct( $pluginFilePath ) {
    $this->pluginFilePath = $pluginFilePath;
    $this->pluginDirPath = dirname( $this->pluginFilePath );
    $this->pluginUrl = plugins_url( '', $this->pluginFilePath ); 

    $meta = get_file_data(
      $this->pluginFilePath,
      array(
        'version' => 'Version',
        'textDomain' => 'Text Domain'
      )
    );
    $this->textDomain = $meta['textDomain'];
    $this->version = get_option( self::VERSION_KEY, null );
    if ( is_null( $this->version ) ) {
      $version = $meta['version'];
      $this->setVersion( $version );
    }

    $this->options = get_option( self::OPTION_KEY, null );
    $this->mergeWithDefaultOptions();
  }

  public function get( $key, $default = null ) {
    if ( isset( $this->options[$key] ) ) {
      return $this->options[$key];
    }
    return $default;
  }

  public function set( $key, $value ) {
    $this->options[$key] = $value;
    updated_option( self::OPTION_KEY, $this->options );
  }

  private function mergeWithDefaultOptions() {
    if ( is_null( $this->options ) ) {
      $this->options = array();
    }

    $defaultOptions = array(
    );

    $this->options = array_merge( $defaultOptions, $this->options );
  }

  public function getVersion() {
    return $this->version;
  }

  public function setVersion( $version ) {
    $this->version = $version;
    update_option( self::VERSION_KEY, $version );
  }

  public function getPluginFilePath() {
    return $this->pluginFilePath;
  }

  public function getPluginDirPath() {
    return $this->pluginDirPath;
  }

  public function getPluginUrl() {
    return $this->pluginUrl;
  }

  public function getTextDomain() {
    return $this->textDomain;
  }
}
