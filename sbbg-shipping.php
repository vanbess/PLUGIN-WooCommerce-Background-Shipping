<?php

/*
   * Plugin Name: Silverback Background Shipping
   * Plugin URI: https://silverbackdev.co.za
   * Description: Improved/reworked background shipping module
   * Author: Werner C. Bessinger
   * Version: 1.0.2
   * Author URI: https://silverbackdev.co.za
   */

/* PREVENT DIRECT ACCESS */
if (!defined('ABSPATH')) :
  exit;
endif;

// define plugin path constant
define('SBBG_PATH', plugin_dir_path(__FILE__));
define('SBBG_URL', plugin_dir_url(__FILE__));

// init
add_action('init', 'sbbg_load');

function sbbg_load()
{
  // include core class
  require SBBG_PATH . 'classes/SBBG_Shipping.php';

  // include shipping process scheduler
  require SBBG_PATH . 'functions/sbbg_schedule_processing.php';
}