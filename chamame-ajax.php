<?php
define( 'DOING_AJAX', true );

require_once( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php' );

send_origin_headers();

if ( empty( $_REQUEST['action'] ) ) {
  die( '0' );
}

@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
@header( 'X-Robots-Tag: noindex' );

send_nosniff_header();
nocache_headers();

if ( is_user_logged_in() ) {
  do_action( 'wp_ajax_' .        $_REQUEST['action'] );
} else {
  do_action( 'wp_ajax_nopriv_' . $_REQUEST['action'] );
}

die( '0' );
