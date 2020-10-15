<?php

/* Schedules background shipping processing via Action Scheduler */

/* check if we're due to schedule our action */

/* enqueue our action if true */
function sbbg_schedule_as_action()
{
    if (false === as_next_scheduled_action('sbbg_process_shipping') && get_option('sbbg_allow_process') == 'yes') :
        as_schedule_single_action(strtotime('now'), 'sbbg_process_shipping');
        update_option('sbbg_allow_process', 'no');
    endif;
}

add_action('admin_head', 'sbbg_schedule_as_action');

/* process uploaded csv and update associated orders */
function sbbg_shipping_data()
{
    /* get url to uploaded csv file */
    $csv_url = get_option('sbbg_file_url');

    /* combined data array */
    $combined_shipping_data_arr = [];

    /* open file for reading */
    if (($csv_file = fopen($csv_url, "r")) !== FALSE) :
        /* push file data to combined shipping data array */
        while ($read_csv_file = fgetcsv($csv_file)) :

            // skip empty lines in CSV to avoid issues
            if ($read_csv_file[0] == NULL) {
                continue;
            }

            // push valid data to array
            $combined_shipping_data_arr[] = $read_csv_file;
        endwhile;
        /* close file after reading */
        fclose($csv_file);
    endif;

    file_put_contents(SBBG_PATH . 'logs/csv_file_log.log', print_r($combined_shipping_data_arr, true));

    /* get shipping company data */
    $shipping_co_data = maybe_unserialize(get_option('sbbg_shipping_cos'));

    if (!empty($shipping_co_data) && is_array($shipping_co_data) || is_object($shipping_co_data)) :

        /* loop through combined shipping data arr */
        $counter = 0;

        foreach ($combined_shipping_data_arr as $data_arr) :
            if ($counter >= 1) :

                /* get shipping data */
                $order_number  = $data_arr[0];
                $order_status  = $data_arr[1];
                $company_id    = $data_arr[2];
                $tracking_info = $data_arr[3];

                /* get shipping company */
                $shipping_co_name = $shipping_co_data[$company_id]['name'];

                /* get order id via order data */
                // $order_data = wc_get_order(['_order_number_formatted' => $order_number]);
                // $order_id = $order_data->get_id();
                $order_id = wc_seq_order_number_pro()->find_order_by_order_number($order_number);

                // check if order shipping data exists before attempting to update
                $shipping_data = get_post_meta($order_id, '_wc_shipment_tracking_items', true);

                if (!$shipping_data) {
                    /* add shipping tracking number etc and update order accordingly */
                    wc_st_add_tracking_number($order_id, $tracking_info, $shipping_co_name);

                    /* update order status */
                    $order_data = new WC_Order($order_id);
                    $order_data->update_status($order_status, 'Order shipping info added - Tracking No ' . $tracking_info . '.');
                }

            endif;
            $counter++;
        endforeach;
    endif;
}

add_action('sbbg_process_shipping', 'sbbg_shipping_data');
