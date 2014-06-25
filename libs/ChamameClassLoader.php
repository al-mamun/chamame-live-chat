<?php
if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

class ChamameClassLoader {
  private $directories = array();

  public function autoload( $clazz ) {
    foreach( $this->directories as $directory ) {
      $file = "{$directory}/{$clazz}.php";

      if ( is_readable( $file ) ) {
        require( $file );
      }
    }
  }

  public function registerDirectory( $directory, $recursive = true ) {
    $this->directories[] = $directory;

    if ( $recursive ) {
      foreach( new DirectoryIterator( $directory ) as $file ) {
        if ( ! $file->isDot() && $file->isDir() ) {
          $this->registerDirectory( $file->getPathname() );
        }
      }
    }
  }

  public function register() {
    spl_autoload_register( array( $this, 'autoload' ) );
  }
}
