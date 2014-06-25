<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class ChamameBuilderFactory {
  public static function createBuilder( $pluginFile ) {
    $ajax = ( defined( 'DOING_AJAX' ) && DOING_AJAX );

    if ( $ajax ) {
      return new ChamameAjaxBuilder( $pluginFile );
    }

    if ( is_admin() ) {
      return new ChamameAdminBuilder( $pluginFile );
    }

    return new ChamameDefaultBuilder( $pluginFile );
  }
}
