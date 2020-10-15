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

                        require_once(ABSPATH.'wp-admin/includes/file.php');

                        /* handle upload */
                        $uploaded = wp_handle_upload($_FILES['sbbg_shipping_csv'], ['test_form' => false]);

                        /* if uploaded successfully, add additional option meta, else bail with error */
                        if (is_array($uploaded) && isset($uploaded['url'])) :

                            /* if file url option exists, update it, else add it */
                            if (get_option('sbbg_file_url')) :
                                $url_meta_added = update_option('sbbg_file_url', $uploaded['url']);
                            else :
                                $url_meta_added = add_option('sbbg_file_url', $uploaded['url']);
                            endif;

                            /* if option added/updated successfully, display success message, else display error message */
                            if ($url_meta_added) : ?>
                                <div class="notice notice-success"><?php echo __('File successfully uploaded to library.', 'woocommerce'); ?></div>
                            <?php endif;
                        else : ?>
                            <div class="notice notice-error"><?php echo __('An error occurred while trying to upload your file. Please try again: ' . print_r($uploaded, true), 'woocommerce'); ?></div>
                            <div class="notice notice-error"><?php print_r($_POST, true); ?></div>
                    <?php endif;
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
}

SBBG_Shipping::init();
