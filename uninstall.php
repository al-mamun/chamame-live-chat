<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
  exit;
}

$chamamePluginFile = dirname( __FILE__ ) . '/chamame-live-chat.php';
$chamameLibDir = dirname( $chamamePluginFile ) . '/libs';
require_once( $chamameLibDir . '/ChamameClassLoader.php' );

$chamameClassloader = new ChamameClassLoader();
$chamameClassloader->registerDirectory( $chamameLibDir );
$chamameClassloader->register();

$chamameConfig = new ChamameConfiguration( $chamamePluginFile );
$chamameInstaller = new ChamameInstaller( $chamameConfig );
$chamameInstaller->uninstall();
