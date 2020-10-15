<?php

/**
 * Handles processing of background shipping CSV
 *
 * @author Werner C. Bessinger
 */
class SBBG_Shipping
{
    /**
     * Class init
     */
    public static function init()
    {

        /* admin page */
        SBBG_Shipping::admin_page();

        /* ajax */
        add_action('wp_ajax_sbbg_schedule_action_ajax', [__CLASS__, 'sbbg_schedule_action_ajax']);
        add_action('wp_ajax_nopriv_sbbg_schedule_action_ajax', [__CLASS__, 'sbbg_schedule_action_ajax']);
        add_action('wp_ajax_sbbg_save_shipping_cos', [__CLASS__, 'sbbg_save_shipping_cos']);
        add_action('wp_ajax_nopriv_sbbg_save_shipping_cos', [__CLASS__, 'sbbg_save_shipping_cos']);

        // custom shipping providers
        add_filter('wc_shipment_tracking_get_providers', 'sbbg_custom_providers');

        function sbbg_custom_providers($providers)
        {
            // get provider data
            $sbbg_ship_provider_data = maybe_unserialize(get_option('sbbg_shipping_cos'));
            if ($sbbg_ship_provider_data) {
                $new_providers['Custom'] = $sbbg_ship_provider_data;
            }

            $all_providers = array_merge($new_providers, $providers);
            return $all_providers;
        }
    }

    /**
     * Register and render admin page
     */
    public static function admin_page()
    {

        /**
         * Adds a submenu page under a custom post type parent.
         */
        function sbbg_register_settings_page()
        {
            add_submenu_page(
                'woocommerce',
                __('Process Background Shipping', 'woocommerce'),
                __('Background Shipping', 'woocommerce'),
                'manage_options',
                'sbbg-background-shipping',
                'sbbg_render_bg_shipping_admin_page'
            );
        }

        /**
         * Display callback for the submenu page.
         */
        function sbbg_render_bg_shipping_admin_page()
        {
?>
            <div id="sbbg_admin" class="wrap">

                <!-- upload -->
                <div id="bg_shipping_upload">
                    <h1><?php _e('Schedule Shipping Process', 'woocommerce'); ?></h1>

                    <?php
                    /* upload CSV to library */
                    if (isset($_POST['sbbg_upload_csv'])) :

                        $target_dir = SBBG_PATH . 'uploads/';
                        $target_file = $target_dir . basename($_FILES["sbbg_shipping_csv"]["name"]);

                        $moved = move_uploaded_file($_FILES["sbbg_shipping_csv"]["tmp_name"], $target_file);

                        if ($moved) { ?>
                            <div class="notice notice-success"><?php echo pll__("The file " . basename($_FILES["sbbg_shipping_csv"]["name"]) . " has been uploaded."); ?></div>
                        <?php
                            update_option('sbbg_file_url', $target_file);
                        } else { ?>
                            <div class="notice notice-error"><?php echo pll__("Sorry, there was an error uploading your file"); ?></div>;
                    <?php }

                    endif;
                    ?>

                    <!-- updload csv -->
                    <form id="sbbg_ul_csv" method="post" enctype="multipart/form-data">
                        <p>
                            <label for="sbbg_shipping_csv"><?php echo __('Upload CSV file', 'woocommerce'); ?></label>
                            <?php if (get_option('sbbg_file_url')) : ?>
                                <span id="sbbg_selected_csv"><?php echo __('Currently selected file: ' . get_option('sbbg_file_url'), 'woocommerce'); ?></span>
                            <?php else : ?>
                                <span id="sbbg_selected_csv"><?php echo __('No CSV currently selected'); ?></span>
                            <?php endif; ?>
                            <input id="sbbg_shipping_csv" name="sbbg_shipping_csv" type="file" current_file="<?php echo get_option('sbbg_file_url'); ?>">
                            <span class="help"><?php echo __('The CSV file containing order shipping information', 'woocommerce'); ?></span>
                        </p>
                        <p>
                            <!-- upload csv -->
                            <button id="sbbg_upload_csv" name="sbbg_upload_csv" type="submit" class="button button-primary"><?php echo __('Upload CSV', 'woocommerce'); ?></button>
                        </p>
                    </form>

                    <!-- schedule action -->
                    <p>
                        <button id="sbbg_schedule_action" class="button button-primary"><?php echo __('Schedule Action(s)', 'woocommerce'); ?></button>
                    </p>
                </div>

                <!-- setup shipping companies -->
                <div id="sbbg_shipping_companies">

                    <h3><?php _e('Shipping company data', 'woocommerce'); ?></h3>
                    <span><em><?php _e('IMPORTANT: Shipping company data is required in order for processing to work!', 'woocommerce'); ?></em></span>

                    <?php
                    $shipping_co_data = maybe_unserialize(get_option('sbbg_shipping_cos'));
                    ?>

                    <div id="sbbg_shipping_co_table_cont">
                        <table id="sbbg_shipping_co_data_table" style="width:100%">
                            <tr>
                                <th><?php _e('Company ref', 'woocommerce'); ?></th>
                                <th><?php _e('Company name', 'woocommerce'); ?></th>
                                <th><?php _e('Tracking URL', 'woocommerce'); ?></th>
                            </tr>

                            <?php
                            if (!empty($shipping_co_data) && is_array($shipping_co_data) || is_object($shipping_co_data)) :
                                $row_no = 0;
                                foreach ($shipping_co_data as $ref => $co_data) :
                                    if ($row_no == 0) : ?>
                                        <tr>
                                            <td class="sbbg_shipping_co_ref" contenteditable="true" title="<?php _e('click to edit shipping company reference', 'woocommerce'); ?>"><?php echo $ref; ?></td>
                                            <td class="sbbg_shipping_co_name" contenteditable="true" title="<?php _e('click to edit shipping company name', 'woocommerce'); ?>"><?php echo $co_data['name']; ?></td>
                                            <td class="sbbg_shipping_co_url" contenteditable="true" title="<?php _e('click to edit shipping company tracking url', 'woocommerce'); ?>"><?php echo $co_data['url']; ?></td>
                                            <td class="sbbg_add_shipping_co"><a href="javascript:void(0)" title="<?php _e('add shipping company', 'woocommerce'); ?>">+</a></td>
                                            <td class="sbbg_remove_shipping_co"><a href="javascript:void(0)" title="<?php _e('remove shipping company', 'woocommerce'); ?>">-</a></td>
                                        </tr>
                                    <?php elseif ($row_no >= 1) : ?>
                                        <tr>
                                            <td class="sbbg_shipping_co_ref" contenteditable="true" title="<?php _e('click to edit shipping company reference', 'woocommerce'); ?>"><?php echo $ref; ?></td>
                                            <td class="sbbg_shipping_co_name" contenteditable="true" title="<?php _e('click to edit shipping company name', 'woocommerce'); ?>"><?php echo $co_data['name']; ?></td>
                                            <td class="sbbg_shipping_co_url" contenteditable="true" title="<?php _e('click to edit shipping company tracking url', 'woocommerce'); ?>"><?php echo $co_data['url']; ?></td>
                                            <td class="sbbg_add_shipping_co"></td>
                                            <td class="sbbg_remove_shipping_co"></td>
                                        </tr>
                                <?php endif;
                                    $row_no++;
                                endforeach;
                            else :
                                ?>
                                <tr>
                                    <td class="sbbg_shipping_co_ref" contenteditable="true" title="<?php _e('click to edit shipping company reference', 'woocommerce'); ?>"><?php _e('company ref', 'woocommerce'); ?></td>
                                    <td class="sbbg_shipping_co_name" contenteditable="true" title="<?php _e('click to edit shipping company name', 'woocommerce'); ?>"><?php _e('company name', 'woocommerce'); ?></td>
                                    <td class="sbbg_shipping_co_url" contenteditable="true" title="<?php _e('click to edit shipping company tracking url', 'woocommerce'); ?>"><?php _e('tracking url', 'woocommerce'); ?></td>
                                    <td class="sbbg_add_shipping_co"><a href="javascript:void(0)" title="<?php _e('add shipping company', 'woocommerce'); ?>">+</a></td>
                                    <td class="sbbg_remove_shipping_co"><a href="javascript:void(0)" title="<?php _e('remove shipping company', 'woocommerce'); ?>">-</a></td>
                                </tr>
                            <?php
                            endif;
                            ?>
                        </table>
                    </div>
                    <a id="sbbg_save_companies" title="<?php _e('click to save', 'woocommerce'); ?>" href="javascript:void(0)"><?php _e('Save shipping company data', 'woocommerce'); ?></a>
                </div>
            </div>
<?php
            /* enqueue css and js */
            wp_enqueue_style('sbbg_css', SBBG_URL . 'assets/admin.css');
            wp_enqueue_script('sbbg_js', SBBG_URL . 'assets/admin.js', ['jquery'], '1.0.0', true);
        }

        add_action('admin_menu', 'sbbg_register_settings_page', 99);
    }

    /**
     * Schedule shipping processing action via action
     */
    public static function sbbg_schedule_action_ajax()
    {

        /* perform check to see if submitted file has already been processed */
        $submitted_file_url = $_POST['sbbg_file_url'];
        $last_processed     = get_option('sbbg_last_processed');

        if ($last_processed != $submitted_file_url) :
            update_option('sbbg_last_processed', $submitted_file_url);
            update_option('sbbg_allow_process', 'yes');
            echo __('Background shipping process has been scheduled.', 'woocommerce');
        else :
            echo __('This file has already been scheduled for processing. Please upload a different file.', 'woocommerce');
        endif;

        wp_die();
    }

    /**
     * Save shipping company data
     */
    public static function sbbg_save_shipping_cos()
    {
        if (isset($_POST['shipping_co_ref_data'])) :

            delete_option('sbbg_shipping_cos');

            $shipping_co_ref_data = $_POST['shipping_co_ref_data'];
            $shipping_co_name_data = $_POST['shipping_co_name_data'];
            $shipping_co_url_data = $_POST['shipping_co_url_data'];

            // combine submitted data into usable array
            $init_data = array_combine($shipping_co_ref_data, $shipping_co_name_data);
            $final_data = [];

            $counter = 0;

            // loop
            foreach ($init_data as $ref => $co_name) {
                $final_data[trim($ref)] = [
                    'name' => $co_name,
                    'url' => $shipping_co_url_data[$counter]
                ];
                $counter++;
            }

            // print_r($final_data);

            // serialize array
            $data_serialized = maybe_serialize($final_data);

            // insert into db
            $data_inserted = update_option('sbbg_shipping_cos', $data_serialized);

            if ($data_inserted) :
                _e('Shipping company data saved.', 'woocommerce');
            else :
                _e('Shipping company data could not be saved. Please reload the page and try again.', 'woocommerce');
            endif;
        endif;

        wp_die();
    }
}

SBBG_Shipping::init();
