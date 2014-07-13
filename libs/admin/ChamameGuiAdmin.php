<?php
if ( ! defined( 'ABSPATH' ) ) {
 exit;
}

class ChamameGuiAdmin extends ChamameAdmin {
  const OPERATOR_NAME_MAX_LENGTH = 40;

  private $adminConsolePageHookname;

  public function registerHooks() {
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueueJavascripts' ) );
    add_action( 'admin_enqueue_scripts', array( $this, 'enqueueStylesheets' ) );
    add_action( 'admin_menu', array( $this, 'addMenu' ) );
    add_action( 'show_user_profile', array( $this, 'addOperatorNameField' ) );
    add_action( 'edit_user_profile', array( $this, 'addOperatorNameField' ) );
    add_action( 'personal_options_update', array( $this, 'saveOperatorName' ) );
    add_action( 'edit_user_profile_update', array( $this, 'saveOperatorName' ) );
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

  public function addMenu() {
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

  public function addOperatorNameField( $user ) { 
    if ( ! user_can( $user, 'chamame_chat_with_visitor' ) ) {
      return;
    }
    
    $name = esc_attr( get_user_meta( $user->ID, 'chamameOperatorName', true ) );

    $textDomain = $this->config->getTextDomain();
    $header = esc_html( __( 'Chat Settings', $textDomain ) ); 
    $label = esc_html( __( 'Operator Name', $textDomain ) );
    $description = esc_html( __( 'Input operator name. If this item is empty, "Display name publicly as" is displayed as operator name.', $textDomain ) );

    echo <<<EOM
<h3>{$header}</h3>
<table class="form-table">
  <tr>
    <th scope="row">
      <label for="chamameOperatorName">{$label}</label>
    </th>
    <td>
      <input type="text" id="chamameOperatorName" name="chamameOperatorName" value="{$name}" class="regular-text" />
      <br />
      <span class="description">{$description}</span>
    </td>
  </tr>
</table>
EOM;
  }

  public function saveOperatorName( $userId ) {
    if ( ! current_user_can( 'edit_user', $userId ) ) {
      return;
    }

    if ( ! user_can( $userId, 'chamame_chat_with_visitor' ) ) {
      return;
    }

    if ( ! isset( $_POST['chamameOperatorName'] ) ) {
      return;
    }

    $name = trim( $_POST['chamameOperatorName'] );
    $name = preg_replace('/[\x00-\x1F\x7F]/', '', $name);

    if ( mb_strlen( $name ) > self::OPERATOR_NAME_MAX_LENGTH ) {
      add_action( 'user_profile_update_errors', array( $this, 'triggerOperatorNameError' ), 10, 3 );
      return;
    }

    update_user_meta( $userId, 'chamameOperatorName', $name );
  }

  public function triggerOperatorNameError( $errors, $update, $user ) {
    $message = sprintf( __( '<strong>ERROR</strong>: Operator name must be at most %d characters.', $this->config->getTextDomain() ), self::OPERATOR_NAME_MAX_LENGTH );
    $errors->add( 'chamameOperatorName', $message );
  }
}
