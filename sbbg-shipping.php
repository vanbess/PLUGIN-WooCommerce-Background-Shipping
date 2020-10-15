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

  // TESTING CSV FILES
 /* get url to uploaded csv file */
 $csv_url = get_option('sbbg_file_url');

 /* combined data array */
 $combined_shipping_data_arr = [];

 /* open file for reading */
 if (($csv_file = fopen($csv_url, "r")) !== FALSE) :
     /* push file data to combined shipping data array */
     while ($read_csv_file = fgetcsv($csv_file)) :
      if($read_csv_file[0] == NULL){
        continue;
      }
         $combined_shipping_data_arr[] = $read_csv_file;
     endwhile;
     /* close file after reading */
     fclose($csv_file);
 endif;

 file_put_contents(SBBG_PATH . 'logs/csv_file_log.log', print_r($combined_shipping_data_arr, true));

}