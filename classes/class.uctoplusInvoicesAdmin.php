<?php

include_once 'UctoplusHelpers.php';
include_once 'BaseUctoplusAPI.php';
include_once 'UctoplusDials.php';
include_once 'UctoplusInvoice.php';

/**
 * Class uctoplusInvoicesAdmin
 *
 * @author MimoGraphix <mimographix@gmail.com>
 * @copyright Epic Fail | Studio
 */
class UctoplusInvoicesAdmin
{
    private $launcher;
    private $activationMesasge = null;
    private $options;

    /**
     * Adds menu items and page
     * Gets options from database
     */
    public function __construct(UctoplusInvoices $launcher)
    {
        $this->launcher = $launcher;
        if (is_admin()) {
            add_action('admin_menu', [$this, 'add_plugin_page']);
            add_action('admin_init', [$this, 'page_init']);
            add_action('admin_init', [$this, 'options_init']);
            add_action('admin_enqueue_scripts', [$this, 'loadMainAdminAssets']);
            add_action('add_meta_boxes', [$this, 'addMetaBoxes'], 10, 2);
        }
        $this->options = get_option('uctoplus_options');
    }

    public function addMetaBoxes($post_type, $post=null)
    {
        if (!empty(get_post_meta($post->ID, 'uctoplusInvoiceUrl', true))) {
            add_meta_box(
                $this->launcher->getPluginSlug().'-invoice-download-meta-box',
                __('Download invoice PDF', $this->launcher->getPluginSlug()),
                [$this, 'uctoplus_invoices_invoice_download_meta_box_callback'],
                $post_type,
                'side',
                'default',
                compact('post')
            );
        }

        if (!empty(get_post_meta($post->ID, 'uctoplusProformaInvoiceUrl', true))) {
            add_meta_box(
                $this->launcher->getPluginSlug().'-proforma-invoice-download-meta-box',
                __('Download proforma invoice PDF', $this->launcher->getPluginSlug()),
                [$this, 'uctoplus_proforma_invoices_invoice_download_meta_box_callback'],
                $post_type,
                'side',
                'default',
                compact('post')
            );
        }
    }

    public function uctoplus_invoices_invoice_download_meta_box_callback( $post)
    {
        $invoiceUrl = get_post_meta($post->ID, 'uctoplusInvoiceUrl', true);
        if(empty($invoiceUrl)){
            return ;
        }
        ?>
        <a href="<?= $invoiceUrl ?>" target="_blank">
            <span class="dashicons dashicons-media-text"></span>
        </a>
        <?php
    }

    public function uctoplus_proforma_invoices_invoice_download_meta_box_callback( $post)
    {
        $invoiceUrl = get_post_meta($post->ID, 'uctoplusProformaInvoiceUrl', true);
        if(empty($invoiceUrl)){
            return ;
        }
        ?>
        <a href="<?= $invoiceUrl ?>" target="_blank">
            <span class="dashicons dashicons-media-text"></span>
        </a>
        <?php
    }


    public function loadMainAdminAssets()
    {
        wp_register_style('uctoplus-invoices-admin-css', $this->launcher->getPluginUrl().'assets/css/uctoplus-invoices-admin.css', [], '1.0.0');
        wp_enqueue_style('uctoplus-invoices-admin-css');
        wp_register_script('uctoplus-invoices-admin-js', $this->launcher->getPluginUrl().'assets/js/uctoplus-invoices-admin.js', ['jquery'], '1.0.0', true);
        wp_enqueue_script('uctoplus-invoices-admin-js');
    }

    public function options_init()
    {
        if (!isset($this->options[ 'invoice_numbering_type' ]) || !$this->options[ 'invoice_numbering_type' ]) {
            $this->options[ 'invoice_numbering_type' ] = 'uctoplus';
            update_option('uctoplus_options', $this->options);
        }
        if (!isset($this->options[ 'invoice_item_description' ]) || !$this->options[ 'invoice_item_description' ]) {
            $this->options[ 'invoice_item_description' ] = 'variation_info';
            update_option('uctoplus_options', $this->options);
        }
    }


    /**
     * Add options page
     */
    public function add_plugin_page()
    {
        add_menu_page(
            __('Účto+ invoices', $this->launcher->getPluginSlug()),
            __('Účto+ invoices', $this->launcher->getPluginSlug()),
            'manage_options',
            'uctoplus-invoices',
            [$this, 'create_admin_page'],
            plugins_url('ucto-invoices/assets/images/')."logo-white-uctoplus.svg",
            66
        );
    }

    /**
     * Creates contend of the option page
     */
    public function create_admin_page()
    {
        // Set class property
        $this->options = get_option('uctoplus_options');
        ?>
        <div class="wrap uctoplus-plugins-namespace">
            <h2><?= $this->launcher->getPluginName() ?></h2>

            <form method="post" action="<?= admin_url() ?>options.php">
                <div>
                    <?php
                    settings_fields('uctoplus_options_group');
                    do_settings_sections('uctoplus-setting-admin');
                    submit_button();
                    ?>
                </div>
            </form>
        </div>
        <?php
    }


    /**
     * Register individual setting options and option sections
     */
    public function page_init()
    {
        register_setting(
            'uctoplus_options_group', // Option group
            'uctoplus_options', // Option name
            [$this, 'sanitize'] // Sanitize
        );

        add_settings_section(
            'setting_section_1', // ID
            __('Basic settings', 'uctoplus-invoices'), // Title
            [$this, 'print_section_info'], // Callback
            'uctoplus-setting-admin' // Page
        );

        add_settings_field(
            'uctoplus_api_key',
            __('Účto+ API Key', 'uctoplus-invoices'),
            [$this, 'uctoplus_api_key_callback'],
            'uctoplus-setting-admin',
            'setting_section_1'
        );

        add_settings_field(
            'sandbox_enviroment',
            __('Sandbox enviroment', 'uctoplus-invoices'),
            [$this, 'sandbox_enviroment_callback'],
            'uctoplus-setting-admin',
            'setting_section_1'
        );

        if (!empty($this->options[ 'uctoplus_api_key' ])) {
            add_settings_section(
                'setting_section_2', // ID
                __('Issuer settings', 'uctoplus-invoices'), // Title
                null,
                'uctoplus-setting-admin' // Page
            );

            add_settings_section(
                'setting_section_3', // ID
                __('Automatization settings', 'uctoplus-invoices'), // Title
                null,
                'uctoplus-setting-admin' // Page
            );

            add_settings_section(
                'setting_section_4', // ID
                __('Invoice settings', 'uctoplus-invoices'), // Title
                null,
                'uctoplus-setting-admin' // Page
            );

            add_settings_field(
                'issuer_name',
                __('Issuer name', 'uctoplus-invoices'),
                [$this, 'issuer_name_callback'],
                'uctoplus-setting-admin',
                'setting_section_2'
            );

            add_settings_field(
                'issuer_phone',
                __('Issuer phone', 'uctoplus-invoices'),
                [$this, 'issuer_phone_callback'],
                'uctoplus-setting-admin',
                'setting_section_2'
            );

            add_settings_field(
                'issuer_web',
                __('Issuer web', 'uctoplus-invoices'),
                [$this, 'issuer_web_callback'],
                'uctoplus-setting-admin',
                'setting_section_2'
            );

            add_settings_field(
                'issuer_email',
                __('Issuer email', 'uctoplus-invoices'),
                [$this, 'issuer_email_callback'],
                'uctoplus-setting-admin',
                'setting_section_2'
            );






            add_settings_field(
                'auto_generation_invoice',
                __('Automatic generation Invoice', 'uctoplus-invoices'),
                [$this, 'auto_generation_invoice_callback'],
                'uctoplus-setting-admin',
                'setting_section_3'
            );

            add_settings_field(
                'due_action_invoice',
                __('Due action Invoice', 'uctoplus-invoices'),
                [$this, 'due_action_invoice_callback'],
                'uctoplus-setting-admin',
                'setting_section_3'
            );

            add_settings_field(
                'auto_generation_proforma_invoice',
                __('Automatic generation Proforma Invoice', 'uctoplus-invoices'),
                [$this, 'auto_generation_proforma_invoice_callback'],
                'uctoplus-setting-admin',
                'setting_section_3'
            );

            add_settings_field(
                'due_action_proforma_invoice',
                __('Due action Proforma Invoice', 'uctoplus-invoices'),
                [$this, 'due_action_proforma_invoice_callback'],
                'uctoplus-setting-admin',
                'setting_section_3'
            );

            add_settings_field(
                'auto_attach_invoice',
                __('Attach PDFs to emails', 'uctoplus-invoices'),
                [$this, 'auto_attach_invoice_callback'],
                'uctoplus-setting-admin',
                'setting_section_3'
            );







            add_settings_field(
                'template_id',
                __('Invoice Template', 'uctoplus-invoices'),
                [$this, 'template_id_callback'],
                'uctoplus-setting-admin',
                'setting_section_4'
            );

            add_settings_field(
                'invoice_numbering_type',
                __('Invoice numbering ype', 'uctoplus-invoices'),
                [$this, 'invoice_numbering_type_callback'],
                'uctoplus-setting-admin',
                'setting_section_4'
            );

            add_settings_field(
                'invoice_number_format',
                __('Invoice numbering format', 'uctoplus-invoices'),
                [$this, 'invoice_number_format_callback'],
                'uctoplus-setting-admin',
                'setting_section_4',
                [
                    'class' => 'invoice_number_format-wrapper '.(isset($this->options[ 'invoice_numbering_type' ]) && $this->options[ 'invoice_numbering_type' ] != 'plugin' ? 'hidden' : ''),
                ]
            );

            add_settings_field(
                'completed_order_counter_id',
                __('Invoice Counter ID', 'uctoplus-invoices'),
                [$this, 'completed_order_counter_id_callback'],
                'uctoplus-setting-admin',
                'setting_section_4'
            );

            add_settings_field(
                'processing_order_counter_id',
                __('Proforma Invoice Counter ID', 'uctoplus-invoices'),
                [$this, 'processing_order_counter_id_callback'],
                'uctoplus-setting-admin',
                'setting_section_4'
            );
            /*
            add_settings_field(
                'credit_note_counter_id',
                __('Credit Note Counter ID', 'uctoplus-invoices'),
                [$this, 'credit_note_counter_id_callback'],
                'uctoplus-setting-admin',
                'setting_section_4'
            );
            */
        }
    }


    /**
     * Sanitize each setting field as needed
     *
     * @param  array  $input  Contains all settings fields as array keys
     */
    public function sanitize($input)
    {
        $new_input = [];

        if (isset($input[ 'uctoplus_api_key' ])) {
            $testKey = new UctoplusDials();
            $testKey->setApiKey($input[ 'uctoplus_api_key' ]);
            $_response = $testKey->getCountries();
            if (is_array($_response)) {
                $new_input[ 'uctoplus_api_key' ] = sanitize_text_field($input[ 'uctoplus_api_key' ]);
            } else {
                add_settings_error('uctoplus_options', 'uctoplus_api_key', $_response, 'error');
            }
        }
        if (isset($input[ 'sandbox_enviroment' ])) {
            $new_input[ 'sandbox_enviroment' ] = sanitize_text_field($input[ 'sandbox_enviroment' ]);
        }

        if (isset($input[ 'issuer_name' ])) {
            $new_input[ 'issuer_name' ] = sanitize_text_field($input[ 'issuer_name' ]);
        }
        if (isset($input[ 'issuer_phone' ])) {
            $new_input[ 'issuer_phone' ] = sanitize_text_field($input[ 'issuer_phone' ]);
        }
        if (isset($input[ 'issuer_web' ])) {
            $new_input[ 'issuer_web' ] = sanitize_text_field($input[ 'issuer_web' ]);
        }
        if (isset($input[ 'issuer_email' ])) {
            $new_input[ 'issuer_email' ] = sanitize_text_field($input[ 'issuer_email' ]);
        }
        if (isset($input[ 'plus_due_date' ])) {
            $new_input[ 'plus_due_date' ] = sanitize_text_field($input[ 'plus_due_date' ]);
        }


        if (isset($input[ 'due_action_invoice' ])) {
            $new_input[ 'due_action_invoice' ] = sanitize_text_field($input[ 'due_action_invoice' ]);
        }
        if (isset($input[ 'due_action_proforma_invoice' ])) {
            $new_input[ 'due_action_proforma_invoice' ] = sanitize_text_field($input[ 'due_action_proforma_invoice' ]);
        }
        if (isset($input[ 'auto_attach_invoice' ])) {
            $new_input[ 'auto_attach_invoice' ] = sanitize_text_field($input[ 'auto_attach_invoice' ]);
        }
        if (isset($input[ 'auto_generation_invoice' ])) {
            $new_input[ 'auto_generation_invoice' ] = sanitize_text_field($input[ 'auto_generation_invoice' ]);
        }
        if (isset($input[ 'auto_generation_proforma_invoice' ])) {
            $new_input[ 'auto_generation_proforma_invoice' ] = sanitize_text_field($input[ 'auto_generation_proforma_invoice' ]);
        }


        if (isset($input[ 'invoice_numbering_type' ])) {
            $new_input[ 'invoice_numbering_type' ] = sanitize_text_field($input[ 'invoice_numbering_type' ]);
        }
        if (isset($input[ 'invoice_number_format' ])) {
            $new_input[ 'invoice_number_format' ] = sanitize_text_field($input[ 'invoice_number_format' ]);
        }
        if (isset($input[ 'next_invoice_number' ])) {
            $new_input[ 'next_invoice_number' ] = sanitize_text_field($input[ 'next_invoice_number' ]);
        }
        if (isset($input[ 'uctoplus_invoice_numbering_list' ])) {
            $new_input[ 'uctoplus_invoice_numbering_list' ] = sanitize_text_field($input[ 'uctoplus_invoice_numbering_list' ]);
        }
        if (isset($input[ 'invoice_number_format_pre' ])) {
            $new_input[ 'invoice_number_format_pre' ] = sanitize_text_field($input[ 'invoice_number_format_pre' ]);
        }
        if (isset($input[ 'invoice_item_description' ])) {
            $new_input[ 'invoice_item_description' ] = sanitize_text_field($input[ 'invoice_item_description' ]);
        }


        if (isset($input[ 'template_id' ])) {
            $new_input[ 'template_id' ] = sanitize_text_field($input[ 'template_id' ]);
        }

        if (isset($input[ 'uctoplus_invoice_numbering_list' ]) && $input[ 'uctoplus_invoice_numbering_list' ]) {
            $new_input[ 'uctoplus_invoice_numbering_list' ] = [];
            foreach ($input[ 'uctoplus_invoice_numbering_list' ] as $countryCode => $val) {
                $new_input[ 'uctoplus_invoice_numbering_list' ][ $countryCode ] = sanitize_text_field($val);
            }
        }
        if (isset($input[ 'completed_order_counter_id' ])) {
            $new_input[ 'completed_order_counter_id' ] = sanitize_text_field($input[ 'completed_order_counter_id' ]);
        }
        if (isset($input[ 'processing_order_counter_id' ])) {
            $new_input[ 'processing_order_counter_id' ] = sanitize_text_field($input[ 'processing_order_counter_id' ]);
        }
        if (isset($input[ 'credit_note_counter_id' ])) {
            $new_input[ 'credit_note_counter_id' ] = sanitize_text_field($input[ 'credit_note_counter_id' ]);
        }

        return $new_input;
    }

    /**
     * Print the Section text
     */
    public function print_section_info()
    {
        if (empty($this->options[ 'uctoplus_api_key' ])) {
            add_settings_error('uctoplus_options', 'uctoplus_api_key', __('Please fill "Účto+ API Key" first. You can get yours at https://moje.uctoplus.sk. Go to Settings -> Api Key.', 'uctoplus-invoices'), 'error');
        } else {
            if (empty($this->options[ 'issuer_name' ])) {
                add_settings_error('uctoplus_options', 'issuer_name', __('Issuer Name is required field'), 'error');
            }

            if (isset($this->options[ 'invoice_numbering_type' ]) && $this->options[ 'invoice_numbering_type' ] != 'plugin') {
                if (empty($this->options[ 'completed_order_counter_id' ])) {
                    add_settings_error('uctoplus_options', 'completed_order_counter_id', __('Invoice Counter ID is required field', 'uctoplus-invoices'), 'error');
                }

                if (empty($this->options[ 'processing_order_counter_id' ])) {
                    add_settings_error('uctoplus_options', 'processing_order_counter_id', __('Proforma Invoice Counter ID is required field', 'uctoplus-invoices'), 'error');
                }
            }
        }

        if (!empty($this->options[ 'sandbox_enviroment' ])) {
            add_settings_error('uctoplus_options', 'sandbox_enviroment', __('Your setting is now only in TESTING mode, please do not forder to durn of SANDBOX enviroment before using in production', 'uctoplus-invoices'), 'warning');
        }

        settings_errors('uctoplus_options');

        print __('Enter your settings below:', 'uctoplus-invoices');
    }

    public function uctoplus_api_key_callback()
    {
        printf(
            '<input type="text" id="uctoplus_api_key" name="uctoplus_options[uctoplus_api_key]" value="%s" />',
            isset($this->options[ 'uctoplus_api_key' ]) ? esc_attr($this->options[ 'uctoplus_api_key' ]) : ''
        );
        ?>
        <p class="info">
            <?= __('Your uctoplus API key from <a href="https://moje.uctoplus.sk" title="" target="_blank">https://moje.uctoplus.sk</a>', 'uctoplus-invoices') ?>
        </p>
        <?php
    }

    public function issuer_name_callback()
    {
        printf(
            '<input type="text" id="issuer_name" name="uctoplus_options[issuer_name]" value="%s" />',
            isset($this->options[ 'issuer_name' ]) ? esc_attr($this->options[ 'issuer_name' ]) : ''
        );
    }

    public function issuer_phone_callback()
    {
        printf(
            '<input type="text" id="issuer_phone" name="uctoplus_options[issuer_phone]" value="%s" />',
            isset($this->options[ 'issuer_phone' ]) ? esc_attr($this->options[ 'issuer_phone' ]) : ''
        );
    }

    public function issuer_web_callback()
    {
        printf(
            '<input type="text" id="issuer_web" name="uctoplus_options[issuer_web]" value="%s" />',
            isset($this->options[ 'issuer_web' ]) ? esc_attr($this->options[ 'issuer_web' ]) : ''
        );
    }

    public function issuer_email_callback()
    {
        printf(
            '<input type="text" id="issuer_email" name="uctoplus_options[issuer_email]" value="%s" />',
            isset($this->options[ 'issuer_email' ]) ? esc_attr($this->options[ 'issuer_email' ]) : ''
        );
    }

    public function template_id_callback()
    {
        $dials = new UctoplusDials();
        $options = $dials->getTemplates();

        if (is_array($options)) {
            echo '<select name="uctoplus_options[template_id]">
                <option value="">'.__('None').'</option>';

            foreach ($options as $key => $option) {
                echo '<option value="'.$option->id.'" '.selected( isset( $this->options[ 'template_id' ] ) ? $this->options[ 'template_id' ] : null, $option->id, false).'  >'.$option->name.'</option>';
            }
            echo '</select>';
        } else {
            echo '<p class="info">'.__('No Templates Found!', 'uctoplus-invoices').'</p>';
        }
    }

    public function processing_order_counter_id_callback()
    {
        $dials = new UctoplusDials();
        $options = $dials->getCountersByType("PROFORMA_INVOICE");

        if ( !isset( $options["status"] ) ) {
            echo '<select name="uctoplus_options[processing_order_counter_id]">
                <option value="">'.__('None').'</option>';

            foreach ($options as $key => $option) {
                echo '<option value="'.$option->id.'" '.selected( isset( $this->options[ 'processing_order_counter_id' ] ) ? $this->options[ 'processing_order_counter_id' ] : null, $option->id, false).'  >'.$option->name.' - ('.$option->format.')</option>';
            }
            echo '</select>';
        } else {
            echo '<p class="error">'.__('No Counters Found! Error:'. $options->message, 'uctoplus-invoices').'</p>';
        }
    }

    public function completed_order_counter_id_callback()
    {
        $dials = new UctoplusDials();
        $options = $dials->getCountersByType("INVOICE");

        if ( !isset( $options["status"] ) ) {
            echo '<select name="uctoplus_options[completed_order_counter_id]">
                <option value="">'.__('None').'</option>';

            foreach ($options as $option) {
                echo '<option value="'.$option->id.'" '.selected( isset( $this->options[ 'completed_order_counter_id' ] ) ? $this->options[ 'completed_order_counter_id' ] : null, $option->id, false).'  >'.$option->name.' - ('.$option->format.')</option>';
            }
            echo '</select>';
        } else {
            echo '<p class="error">'.__('No Counters Found! Error:'. $options->message, 'uctoplus-invoices').'</p>';
        }
    }

    public function credit_note_counter_id_callback()
    {
        $dials = new UctoplusDials();
        $options = $dials->getCountersByType("CREDIT_NOTE");

        if ( !isset( $options["status"] ) ) {
            echo '<select name="uctoplus_options[credit_note_counter_id]">
                <option value="">'.__('None').'</option>';

            foreach ($options as $key => $option) {
                echo '<option value="'.$option->id.'" '.selected($this->options[ 'credit_note_counter_id' ], $option->id, false).'  >'.$option->name.' - ('.$option->format.')</option>';
            }
            echo '</select>';
        } else {
            echo '<p class="error">'.__('No Counters Found! Error:'. $options->message, 'uctoplus-invoices').'</p>';
        }
    }

    public function plus_due_date_callback()
    {
        printf(
            '<input type="number" id="plus_due_date" name="uctoplus_options[plus_due_date]" value="%d" min="0" step="1" />',
            isset($this->options[ 'plus_due_date' ]) ? esc_attr($this->options[ 'plus_due_date' ]) : 1
        );
        ?>
        <p class="info">
            <?= __('Number of days to be added as due date', 'uctoplus-invoices') ?>
        </p>
        <?php
    }


    public function due_action_invoice_callback()
    {
        echo '<select name="uctoplus_options[due_action_invoice]">';

        $order_statuses = $this->get_order_statuses();

        foreach ($order_statuses as $key => $order_status) {
            if (isset($this->options[ 'due_action_invoice' ])) {
                printf(
                    '<option id="due_action" value="%s" %s >%s</option>',
                    isset($this->options[ 'due_action_invoice' ]) && $this->options[ 'due_action_invoice' ] == $key ? esc_attr($this->options[ 'due_action_invoice' ]) : $key,
                    selected(($key), $this->options[ 'due_action_invoice' ], false),
                    isset($this->options[ 'due_action_invoice' ]) && $this->options[ 'due_action_invoice' ] == $key ? $order_status : $order_status
                );
            } else {
                echo '<option id="due_action_invoice" value="'.$order_status.'"  >'.$order_status.'</option>';
            }
        }

        echo '</select>';

        ?>
        <p class="info">
            <?= __('When should be invoice automatically generated', 'uctoplus-invoices') ?>
        </p>
        <?php
    }

    public function due_action_proforma_invoice_callback()
    {
        echo '<select name="uctoplus_options[due_action_proforma_invoice]">';

        $order_statuses = $this->get_order_statuses();

        foreach ($order_statuses as $key => $order_status) {
            if (isset($this->options[ 'due_action_proforma_invoice' ])) {
                printf(
                    '<option id="due_action" value="%s" %s >%s</option>',
                    isset($this->options[ 'due_action_proforma_invoice' ]) && $this->options[ 'due_action_proforma_invoice' ] == $key ? esc_attr($this->options[ 'due_action_proforma_invoice' ]) : $key,
                    selected(($key), $this->options[ 'due_action_proforma_invoice' ], false),
                    isset($this->options[ 'due_action_proforma_invoice' ]) && $this->options[ 'due_action_proforma_invoice' ] == $key ? $order_status : $order_status
                );
            } else {
                echo '<option id="due_action_proforma_invoice" value="'.$order_status.'"  >'.$order_status.'</option>';
            }
        }

        echo '</select>';

        ?>
        <p class="info">
            <?= __('When should be invoice automatically generated', 'uctoplus-invoices') ?>
        </p>
        <?php
    }

    public function auto_generation_invoice_callback()
    {
        ?>
        <input type="checkbox" id="auto_generation_invoice" name="uctoplus_options[auto_generation_invoice]" value="checked" <?= isset($this->options[ 'auto_generation_invoice' ]) && $this->options[ 'auto_generation_invoice' ] == 'checked' ? 'checked' : '' ?>>
        <label for="auto_generation_invoice"><?= __('Generate invoice after status change', 'uctoplus-invoices') ?></label>
        <br>
        <?php
    }

    public function auto_attach_invoice_callback()
    {
        ?>
        <input type="checkbox" id="auto_attach_invoice" name="uctoplus_options[auto_attach_invoice]" value="checked" <?= isset($this->options[ 'auto_attach_invoice' ]) && $this->options[ 'auto_attach_invoice' ] == 'checked' ? 'checked' : '' ?>>
        <label for="auto_attach_invoice"><?= __('Attach PDFs into emails', 'uctoplus-invoices') ?></label>
        <br>
        <?php
    }

    public function sandbox_enviroment_callback()
    {
        ?>
        <input type="checkbox" id="sandbox_enviroment" name="uctoplus_options[sandbox_enviroment]" value="checked" <?= isset($this->options[ 'sandbox_enviroment' ]) && $this->options[ 'sandbox_enviroment' ] == 'checked' ? 'checked' : '' ?>>
        <label for="sandbox_enviroment"><?= __('USE ONLY FOR TESTING!!!', 'uctoplus-invoices') ?></label>
        <br>
        <?php
    }

    public function auto_generation_proforma_invoice_callback()
    {
        ?>
        <input type="checkbox" id="auto_generation_proforma_invoice" name="uctoplus_options[auto_generation_proforma_invoice]" value="checked" <?= isset( $this->options[ 'auto_generation_proforma_invoice' ] ) && $this->options[ 'auto_generation_proforma_invoice' ] == 'checked' ? 'checked' : '' ?>>
        <label for="auto_generation_proforma_invoice"><?= __('Generate proforma invoice after status change', 'uctoplus-invoices') ?></label>
        <br>
        <?php
    }

    public function invoice_numbering_type_callback()
    {
        ?>

        <input type="radio" name="uctoplus_options[invoice_numbering_type]" value="uctoplus" <?= $this->options[ 'invoice_numbering_type' ] == 'uctoplus' ? 'checked' : '' ?>>
        <label><?= __('Účto+ numbering (preferred)', 'uctoplus-invoices') ?></label>
        <br>

        <input type="radio" name="uctoplus_options[invoice_numbering_type]" value="plugin" <?= $this->options[ 'invoice_numbering_type' ] == 'plugin' ? 'checked' : '' ?>>
        <label><?= __('Custom plugin numbering', 'uctoplus-invoices') ?></label>
        <br>

        <?php
    }

    public function invoice_number_format_callback()
    {
        ?>

        <?php
        printf(
            '<input type="text" id="invoice_number_format_pre" name="uctoplus_options[invoice_number_format_pre]" value="%s" />',
            isset($this->options[ 'invoice_number_format_pre' ]) ? esc_attr($this->options[ 'invoice_number_format_pre' ]) : ''
        );

        echo '<select id="invoice_number_format" name="uctoplus_options[invoice_number_format]">';
        echo '<option value="RRRRXXXX" '.(isset($this->options[ "invoice_number_format" ]) && $this->options[ "invoice_number_format" ] == "RRRRXXXX" ? "selected" : "").'>RRRRXXXX</option>';
        echo '<option value="RRRRMMXXXX" '.(isset($this->options[ "invoice_number_format" ]) && $this->options[ "invoice_number_format" ] == "RRRRMMXXXX" ? "selected" : "").'>RRRRMMXXXX</option>';
        echo '<option value="RRMMDDXXXX" '.(isset($this->options[ "invoice_number_format" ]) && $this->options[ "invoice_number_format" ] == "RRMMDDXXXX" ? "selected" : "").'>RRMMDDXXXX</option>';
        echo '<option value="XXXXRRRRMM" '.(isset($this->options[ "invoice_number_format" ]) && $this->options[ "invoice_number_format" ] == "XXXXRRRRMM" ? "selected" : "").'>XXXXRRRRMM</option>';
        echo '</select>';
        ?>
        <p class="info">
            <?= __('Format of invoices numbering, first field is prefix (leave blank for no prefix), second field is format of the invoice number', 'uctoplus-invoices') ?>
        </p>

        <?php
    }

    public function invoice_item_description_callback()
    {
        ?>
        <input type="radio" name="uctoplus_options[invoice_item_description]"
               value="variation_info" <?= $this->options[ "invoice_item_description" ] === 'variation_info' ? 'checked="checked"' : '' ?>>
        <label><?= __('Variation parameters', 'uctoplus-invoices') ?></label>
        <br>

        <input type="radio" name="uctoplus_options[invoice_item_description]"
               value="description" <?= $this->options[ "invoice_item_description" ] === 'description' ? 'checked="checked"' : '' ?>>
        <label><?= __('Product description', 'uctoplus-invoices') ?></label>
        <br>


        <input type="radio" name="uctoplus_options[invoice_item_description]" value="empty" <?= $this->options[ "invoice_item_description" ] === 'empty' ? 'checked="checked"' : '' ?>>
        <label><?= __('Empty', 'uctoplus-invoices') ?></label>
        <p class="info">
            <?= __('Item description displayed in the invoice', 'uctoplus-invoices') ?>
        </p>
        <?php
    }

    public function next_invoice_number_callback()
    {
        printf(
            '<input type="number" id="next_invoice_number" name="uctoplus_options[next_invoice_number]" value="%s" />',
            isset($this->options[ 'next_invoice_number' ]) ? esc_attr($this->options[ 'next_invoice_number' ]) : ''
        );
        ?>
        <p class="info">
            <?= __('Number of next invoice to be generated (use only number, not format. If next invoice should be in format RRRRXXXX 2020025 type in just "25")',
                'uctoplus-invoices') ?>
        </p>
        <p class="info">
            <span class="warning"><?= __('If there is existing invoice with specified number in Uctoplus allready, it will be overwritten!', 'uctoplus-invoices') ?></span>
        </p>
        <?php
    }

    public function uctoplus_invoice_numbering_list_callback()
    {
        $countries = WC()->countries->get_allowed_countries();
        if ($countries) {
            foreach ($countries as $countryCode => $countryName) {
                ?>
                <div>
                    <label for="uctoplus_invoice_numbering_list_<?= $countryCode ?>"><?= $countryName ?> : </label>
                    <?php
                    printf(
                        '<input type="text" id="uctoplus_invoice_numbering_list_'.$countryCode.'" name="uctoplus_options[uctoplus_invoice_numbering_list]['.$countryCode.']" value="%s" />',
                        isset($this->options[ 'uctoplus_invoice_numbering_list' ][ $countryCode ]) ? esc_attr($this->options[ 'uctoplus_invoice_numbering_list' ][ $countryCode ]) : 'OF'
                    );
                    ?>
                </div>
                <?php
            }
        }

        ?>
        <p class="info">
            <?= __('Assign number list to each country, leave default value to use main invoicing list', 'uctoplus-invoices') ?>
        </p>
        <?php
    }

    public function get_order_statuses()
    {
        if (function_exists('wc_order_status_manager_get_order_status_posts')) // plugin WooCommerce Order Status Manager
        {
            $wc_order_statuses = array_reduce(
                wc_order_status_manager_get_order_status_posts(),
                function ($result, $item)
                {
                    $result[ $item->post_name ] = $item->post_title;
                    return $result;
                },
                []
            );

            return $wc_order_statuses;
        }

        if (function_exists('wc_get_order_statuses')) {
            $wc_get_order_statuses = wc_get_order_statuses();

            return $this->alter_wc_statuses($wc_get_order_statuses);
        }

        $order_status_terms = get_terms('shop_order_status', 'hide_empty=0');

        $shop_order_statuses = [];
        if (!is_wp_error($order_status_terms)) {
            foreach ($order_status_terms as $term) {
                $shop_order_statuses[ $term->slug ] = $term->name;
            }
        }

        return $shop_order_statuses;
    }

    function alter_wc_statuses($array)
    {
        $new_array = [];
        foreach ($array as $key => $value) {
            $new_array[ substr($key, 3) ] = $value;
        }

        return $new_array;
    }

}


?>