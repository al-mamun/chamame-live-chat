<?php
/**
 * Plugin Name: Chamame Live Chat
 * Description: Simple chat application for customer support.
 * Version: 0.2.1
 * Author: katanyan
 * Author URI: http://blog.katanyan.com
 * Requires at least: 3.9
 * Tested up to: 3.9
 * 
 * Text Domain: chamame-live-chat
 * Domain Path: /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

$chamamePluginFile = __FILE__;

$chamameLibDir = dirname( $chamamePluginFile ) . '/libs';
require_once( $chamameLibDir . '/ChamameClassLoader.php' );

$chamameClassloader = new ChamameClassLoader();
$chamameClassloader->registerDirectory( $chamameLibDir );
$chamameClassloader->register();

$chamameBuilder = ChamameBuilderFactory::createBuilder( $chamamePluginFile );
$chamameConstructor = new ChamameConstructor( $chamameBuilder );
$chamame = $chamameConstructor->construct();
$chamame->start();
