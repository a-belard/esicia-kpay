<?php

/**
 * @packaage EsiciaKpay
 */
/*
Plugin Name: Kpay
Plugin URI: https://esicia.com
Description: Esicia Kpay plugin for WordPress
Version: 2.0.0
Author: Esicia
Author URI: https://esicia.com
License: GPLv2 or later
Text Domain: esicia-kpay
*/
/*
This file is part of the Esicia Kpay plugin for WordPress

(c) Esicia

For the full copyright and license information,
please view the LICENSE file that was distributed with this source code.
*/

defined('ABSPATH') or die('Hey, you can\t access this file!');

if (!function_exists('add_action')) {
  echo 'Hey, you can\t access this file!';
  exit;
}

class KpayPlugin
{
  public $plugin;
  public   $fields = array(
    'environment' => 'Environment',
    'username' => 'Username',
    'password' => 'Password',
    'details' => 'Details',
    'returl' => 'Return URL',
    'redirecturl' => 'Redirect URL',
    'retailerid' => 'Retailer ID',
  );
  function __construct()
  {

    $this->plugin = plugin_basename(__FILE__);

    // load assets (js, css)
    add_action('admin_enqueue_scripts', array($this, 'load_assets'));

    // add menu
    add_action('admin_menu', array($this, 'set_settings_menu'));

    // add shortcode
    add_shortcode('kpay-form', array($this, 'load_form'));

    // add settings link
    add_filter("plugin_action_links_$this->plugin", array($this, 'settings_link'));

    // register settings
    add_action('admin_init', array($this, 'register_settings_fields'));
  }

  function settings_link($links)
  {
    $settings_link = '<a href="admin.php?page=kpay-plugin">Settings</a>';
    array_push($links, $settings_link);
    return $links;
  }

  function set_settings_menu()
  {
    add_menu_page(
      'Kpay Plugin',
      'Kpay',
      'manage_options',
      'kpay-plugin',
      array($this, 'add_settings_page'),
      plugin_dir_url(__FILE__) . 'favicon.ico',
      110
    );
  }

  function register_settings_fields()
  {
    register_setting('kpay-plugin', 'kpay_plugin_options', array($this, 'sanitize'));
    add_settings_section('kpay_plugin_section', 'Kpay Settings', array($this, 'kpay_plugin_section_cb'), 'kpay-plugin');

    foreach ($this->fields as $field => $label) {
      add_settings_field($field, $label, array($this, 'kpay_plugin_field_cb'), 'kpay-plugin', 'kpay_plugin_section', array('field' => $field));
    }
  }

  function sanitize($input)
  {
    $sanitized_input = array();
    foreach ($input as $key => $value) {
      $sanitized_input[$key] = sanitize_text_field($value);
    }
    return $sanitized_input;
  }

  function kpay_plugin_section_cb()
  {
?>
    <p>
      username and password are required for the Kpay API Basic Authentication <br>
      Details refers to the details of the transaction e.g order, donation, etc.
      <br>
      Return URL and Redirect URL are required for the Kpay API Visa/Mastercard payment <br>
    </p>
<?php
  }

  function kpay_plugin_field_cb($args)
  {
    $options = get_option('kpay_plugin_options');
    $field = $args['field'];
    $value = isset($options[$field]) ? $options[$field] : '';
    if ($field != 'environment') {
      echo "<input type='text' name='kpay_plugin_options[$field]' value='$value' />";
    } else {
      echo "<select name='kpay_plugin_options[$field]'>";
      echo "<option value=''>Select</option>";
      echo "<option value='Test' " . ($value == 'Test' ? 'selected' : '') . ">Sandbox</option>";
      echo "<option value='Live' " . ($value == 'Live' ? 'selected' : '') . ">Live</option>";
      echo "</select>";
    }
  }

  function add_settings_page()
  {
    require_once('templates/admin/admin-page.php');
  }


  function activate()
  {
    // generate a CPT
    // flush rewrite rules
  }

  function deactivate()
  {
    // flush rewrite rules

  }

  function uninstall()
  {
    // delete CPT
    // delete all the plugin data from the DB

  }


  function load_assets()
  {
    wp_enqueue_style(
      'kpay-plugin',
      plugin_dir_url(__FILE__) . 'css/kpay.css',
      array(),
      '1',
      'all'
    );

    wp_enqueue_script(
      'kpay-plugin',
      plugin_dir_url(__FILE__) . 'js/kpay.js',
      array('jquery'),
      '1',
      true
    );
  }

  function load_form()
  {
    ob_start();
    echo '<link rel="stylesheet" href="' . plugin_dir_url(__FILE__) . 'css/kpay.css">';
    require_once('templates/form/kpay-form.php');
    echo '<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>';
    return ob_get_clean();
  }
}

if (class_exists("KpayPlugin")) {
  $kpayPlugin = new KpayPlugin();
}

// Activation
register_activation_hook(__FILE__, array($kpayPlugin, 'activate'));

// Deactivation
register_deactivation_hook(__FILE__, array($kpayPlugin, 'deactivate'));

// Uninstall
// register_uninstall_hook(__FILE__, array($kpayPlugin, 'uninstall'));
