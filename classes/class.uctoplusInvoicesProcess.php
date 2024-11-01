<?php

include_once 'UctoplusHelpers.php';
include_once 'BaseUctoplusAPI.php';
include_once 'UctoplusDials.php';
include_once 'UctoplusInvoice.php';

require_once(ABSPATH.'/wp-admin/includes/file.php');

/**
 * Class UctoplusInvoicesProcess
 *
 * @author MimoGraphix <mimographix@gmail.com>
 * @copyright Epic Fail | Studio
 */
class UctoplusInvoicesProcess
{
    private $launcher;
    private $options;
    private $sucesfull = [];
    private $unsucesfull = [];

    /**
     * Loads plugin textdomain and sets the options attribute from database
     */
    public function __construct(UctoplusInvoices $launcher)
    {
        $this->launcher = $launcher;
        load_plugin_textdomain($this->launcher->getPluginSlug(), false, dirname(plugin_basename(__FILE__)).'/lang/');
        $this->options = get_option('uctoplus_options');
        $this->initializeOrderTracking();

        if (isset(get_option('uctoplus_options')[ 'auto_attach_invoice' ]) && get_option('uctoplus_options')[ 'auto_attach_invoice' ] == 'checked') {
            add_filter('woocommerce_email_attachments', [$this, 'uctoplus_attach_to_wc_emails'], 10, 3);
        }

        if (isset(get_option('uctoplus_options')[ 'auto_generation_invoice' ]) && get_option('uctoplus_options')[ 'auto_generation_invoice' ] == 'checked') {
            if (isset(get_option('uctoplus_options')[ 'due_action_invoice' ])) {
                add_action('woocommerce_order_status_'.get_option('uctoplus_options')[ 'due_action_invoice' ], [$this, 'autoGenerateInvoice'], 5);
            }
        }

        if (isset(get_option('uctoplus_options')[ 'auto_generation_proforma_invoice' ]) && get_option('uctoplus_options')[ 'auto_generation_proforma_invoice' ] == 'checked') {
            if (isset(get_option('uctoplus_options')[ 'due_action_proforma_invoice' ])) {
                add_action('woocommerce_order_status_'.get_option('uctoplus_options')[ 'due_action_proforma_invoice' ], [$this, 'autoGenerateProformaInvoice'], 5);
            }
        }
    }

    public function useAttachment($url)
    {
        if ($url == "") {
            return null;
        }

        try {
            $file = download_url($url);

            if ( ! is_a($file, WP_Error::class)) {
                return $file;
            }
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * @param $attachments
     * @param $email_id
     * @param  WC_Order  $order
     *
     * @return mixed
     */
    function uctoplus_attach_to_wc_emails($attachments, $email_id, $order)
    {
        // Avoiding errors and problems
        if ( ! is_a($order, 'WC_Order') || ! isset($email_id)) {
            return $attachments;
        }

        switch ($email_id) {
            case "new_order": // Processing
                $url        = get_post_meta($order->get_id(), 'uctoplusProformaInvoiceUrl', true);
                $attachment = $this->useAttachment($url);
                break;
            case "customer_completed_order": // Completed
                $url        = get_post_meta($order->get_id(), 'uctoplusInvoiceUrl', true);
                $attachment = $this->useAttachment($url);
                break;
        }

        if (!empty($attachment)) {
            $attachments[] = $attachment;
        }

        return $attachments;
    }

    public function autoGenerateInvoice($order_id)
    {
        $invoiceId = get_post_meta($order_id, 'invoice_id', true);
        if ($invoiceId) {
            return;
        }
        $uctoplusInvoice = new UctoplusInvoice($order_id);
        $uctoplusInvoice->createInvoice('invoice');
        if ($uctoplusInvoice->response[ 'status' ] == true) {
            $this->sucesfull[] = $uctoplusInvoice->response;
            update_post_meta($order_id, 'uctoplus_invoice_id', $uctoplusInvoice->response[ 'invoiceId' ]);
        } else {
            $this->unsucesfull = $uctoplusInvoice->response;
        }
    }

    public function autoGenerateProformaInvoice($order_id)
    {
        $invoiceId = get_post_meta($order_id, 'invoice_id', true);
        if ($invoiceId) {
            return;
        }
        $uctoplusInvoice = new UctoplusInvoice($order_id);
        $uctoplusInvoice->createInvoice('proforma');
        if ($uctoplusInvoice->response[ 'status' ] == true) {
            $this->sucesfull[] = $uctoplusInvoice->response;
            update_post_meta($order_id, 'uctoplus_proforma_invoice_id', $uctoplusInvoice->response[ 'invoiceId' ]);
        } else {
            $this->unsucesfull = $uctoplusInvoice->response;
        }
    }

    /**
     * Sets up actions for hooks
     */
    function initializeOrderTracking()
    {
//        add_action('woocommerce_checkout_order_processed', array($this, 'custom_process_order'));
        add_action('admin_footer-edit.php', [&$this, 'custom_bulk_admin_footer']);
        add_action('load-edit.php', [&$this, 'custom_bulk_action']);
        add_action('admin_notices', [&$this, 'custom_bulk_admin_notices']);
    }

    /**
     * @param $order_id
     *
     * @deprecated
     * Method is used if invoice is created at time of order creation
     * Not used in this version, we are using manual invoice generating
     *
     */
    function custom_process_order($order_id)
    {
        $order     = new WC_Order($order_id);
        $orderJson = $this->prepareOrderJson($order);
        $this->sendData($orderJson);
    }

    /**
     * Adds option to export invoices to orders page bulk select
     */
    function custom_bulk_admin_footer()
    {
        global $post_type;

        if ($post_type == 'shop_order') {
            ?>
            <script type="text/javascript">
                jQuery(document).ready(function () {
                    jQuery('<option>').val('uctoplus_invoice_export_invoice').text('<?php _e('Účto+ - generate invoice', $this->launcher->getPluginSlug()) ?>').appendTo("select[name='action']");
                    jQuery('<option>').val('uctoplus_invoice_export_proforma_invoice').text('<?php _e('Účto+ - generate proforma invoice', $this->launcher->getPluginSlug()) ?>').appendTo("select[name='action']");
                    jQuery('<option>').val('uctoplus_invoice_export_invoice').text('<?php _e('Účto+ - generate invoice', $this->launcher->getPluginSlug()) ?>').appendTo("select[name='action2']");
                    jQuery('<option>').val('uctoplus_invoice_export_proforma_invoice').text('<?php _e('Účto+ - generate proforma invoice', $this->launcher->getPluginSlug()) ?>').appendTo("select[name='action2']");
                });
            </script>
            <?php
        }
    }

    /**
     * Sets up action to be taken after export option is selected
     * If export is selected, provides export and refreshes page
     * After refresh, notices are shown
     */
    function custom_bulk_action()
    {
        global $typenow;
        $post_type = $typenow;

        if ($post_type == 'shop_order') {
            $wp_list_table   = _get_list_table('WP_Posts_List_Table');  // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc
            $action          = $wp_list_table->current_action();
            $allowed_actions = ['uctoplus_invoice_export_invoice', 'uctoplus_invoice_export_proforma_invoice'];
            if ( ! in_array($action, $allowed_actions)) {
                return;
            }
            check_admin_referer('bulk-posts');
            if (isset($_REQUEST[ 'post' ])) {
                $post_ids = array_map('intval', $_REQUEST[ 'post' ]);
            }
            if (empty($post_ids)) {
                return;
            }
            $sendback = remove_query_arg(['exported', 'untrashed', 'deleted', 'ids'], wp_get_referer());
            if ( ! $sendback) {
                $sendback = admin_url("edit.php?post_type=$post_type");
            }
            $pagenum  = $wp_list_table->get_pagenum();
            $sendback = add_query_arg('paged', $pagenum, $sendback);

            switch ($action) {
                case 'uctoplus_invoice_export_invoice':
                    foreach ($post_ids as $post_id) {
                        $invoiceId = get_post_meta($post_id, 'uctoplus_invoice_id', true);
                        if ($invoiceId) {
                            $this->unsucesfull[] = [
                                'orderId' => $post_id,
                                'message' => 'Invoice was already generated',
                            ];
                            continue;
                        }
                        $uctoplusInvoice = new UctoplusInvoice($post_id);
                        $uctoplusInvoice->createInvoice('invoice');
                        if ($uctoplusInvoice->response[ 'status' ] == true) {
                            $this->sucesfull[] = $uctoplusInvoice->response;
                            update_post_meta($post_id, 'uctoplus_invoice_id', $uctoplusInvoice->response[ 'invoiceId' ]);
                        } else {
                            $this->unsucesfull[] = $uctoplusInvoice->response;
                        }
                    }
                    break;
                case 'uctoplus_invoice_export_proforma_invoice':
                    foreach ($post_ids as $post_id) {
                        $invoiceId = get_post_meta($post_id, 'uctoplus_proforma_invoice_id', true);
                        if ($invoiceId) {
                            $this->unsucesfull[] = [
                                'orderId' => $post_id,
                                'message' => 'Invoice was already generated',
                            ];
                            continue;
                        }
                        $uctoplusInvoice = new UctoplusInvoice($post_id);
                        $uctoplusInvoice->createInvoice('proforma');
                        if ($uctoplusInvoice->response[ 'status' ] == true) {
                            $this->sucesfull[] = $uctoplusInvoice->response;
                            update_post_meta($post_id, 'uctoplus_proforma_invoice_id', $uctoplusInvoice->response[ 'invoiceId' ]);
                        } else {
                            $this->unsucesfull[] = $uctoplusInvoice->response;
                        }
                    }
                    break;
                case 'uctoplus_invoice_export_credit_note':
                    foreach ($post_ids as $post_id) {
                        $invoiceId = get_post_meta($post_id, 'uctoplus_credit_note_id', true);
                        if ($invoiceId) {
                            $this->unsucesfull[] = [
                                'orderId' => $post_id,
                                'message' => 'Invoice was already generated',
                            ];
                            continue;
                        }
                        $uctoplusInvoice = new UctoplusInvoice($post_id);
                        $uctoplusInvoice->createInvoice('credit_note');
                        if ($uctoplusInvoice->response[ 'status' ] == true) {
                            $this->sucesfull[] = $uctoplusInvoice->response;
                            update_post_meta($post_id, 'uctoplus_credit_note_id', $uctoplusInvoice->response[ 'invoiceId' ]);
                        } else {
                            $this->unsucesfull[] = $uctoplusInvoice->response;
                        }
                    }
                    break;
            }

            $sucessfull   = json_encode($this->sucesfull);
            $unsucessfull = json_encode($this->unsucesfull);
            $sendback     = add_query_arg(['uctoplus-sucessfull' => $sucessfull, 'uctoplus-unsucessfull' => $unsucessfull], $sendback);
            wp_redirect($sendback);
            exit();
        }
    }

    /**
     * Displays the notice
     */
    function custom_bulk_admin_notices()
    {
        global $post_type, $pagenow;

        if ($pagenow == 'edit.php' && $post_type == 'shop_order' && (isset($_REQUEST[ 'uctoplus-sucessfull' ]) || isset($_REQUEST[ 'uctoplus-unsucessfull' ]))) {
            $sucessfull   = json_decode(sanitize_text_field($_REQUEST[ 'uctoplus-sucessfull' ]), true);
            $unsucessfull = json_decode(sanitize_text_field($_REQUEST[ 'uctoplus-unsucessfull' ]), true);
            ?>
            <style>
                .woocommerce-layout__notice-list-hide {
                    display: block;
                }
            </style>
            <?php
            if ($sucessfull != false && count($sucessfull) > 0) {
                echo "<div class=\"updated\">";
                foreach ($sucessfull as $message) {
                    $messageContent = sprintf(__('Order no. %s Sucessfully generated', $this->launcher->getPluginSlug()), $message[ 'orderId' ]);
                    echo "<p>".esc_attr($messageContent)."</p>";
                }
                echo "</div>";
            }
            if ($unsucessfull != false && count($unsucessfull) > 0) {
                echo "<div class=\"error\">";
                foreach ($unsucessfull as $message) {
                    $messageContent = sprintf(__('Order no. %s Was not generated. Error : %s', $this->launcher->getPluginSlug()), $message[ 'orderId' ], $message[ 'message' ]);
                    echo "<p>".esc_attr($messageContent)."</p>";
                }
                echo "</div>";
            }
        }
    }

    private function prepareOrderJson($order)
    {
        $uctoplusOrder            = array_merge(
            $this->prepareClientData($order),
            $this->prepareSenderData($order),
            $this->preparePriceData($order),
            $this->prepareAdditionalData($order)
        );
        $uctoplusOrder[ 'items' ] = $this->prepareItemData($order);


        $uctoplusOrderJson = json_encode($uctoplusOrder, JSON_UNESCAPED_UNICODE);

        return $uctoplusOrderJson;
    }


}
