<?php

/**
 * Class uctoplusInvoice
 *
 * @author MimoGraphix <mimographix@gmail.com>
 * @copyright Epic Fail | Studio
 */
class UctoplusInvoice
    extends BaseUctoplusAPI
{
    private $orderID;
    private $order;
    public $response = [];
    private $invoiceData = [];

    public function __construct($orderID)
    {
        parent::__construct();

        $this->orderID = $orderID;
        $this->order = new WC_Order($orderID);
    }

    public function createInvoice($type)
    {
        //$this->test();
        if (!$this->order) {
            $this->response[ 'status' ] = false;
            $this->response[ 'message' ] = __('Could not instantiate Woocommerce order. Order Id : ').$this->orderID;

            return $this->response;
        }
        $this->prepareInvoiceData($type);
        $this->preparePaymentData();
        $this->checkDiscount();
        $this->prepareIssuerData();
        $this->prepareReceiverData();
        $this->addItems();
        $response = $this->sendInvoice();

        if ($response->success == true) {
            $this->order->add_order_note( sprintf( __('New %s generated with # %s ' ), $response->model->invoiceType, $response->model->invoiceNumber ) );
            switch ($type) {
                case 'proforma':
                    update_post_meta($this->orderID, 'uctoplusProformaInvoiceUrl', $response->model->file->url);
                    break;
                case 'invoice':
                    update_post_meta($this->orderID, 'uctoplusInvoiceUrl', $response->model->file->url);
                    break;
                case 'credit_note':
                    update_post_meta($this->orderID, 'uctoplusCreditNoteUrl', $response->model->file->url);
                    break;
            }

            $this->response = [
                'status' => true,
                'orderId' => $this->orderID,
                'invoiceId' => $response->model->id,
                'url' => $response->model->file->url,
            ];
        } else {
            $this->response = [
                'status' => false,
                'orderId' => $this->orderID,
                'message' => $response->message,
            ];
        }
    }

    private function prepareInvoiceData($type)
    {
        $today = date('Y-m-d');
        $this->invoiceData = [
            'dateIssue' => $today,
            'dateDelivery' => $today,
            'dateDue' => date('Y-m-d', strtotime('+14 days', strtotime($today))),
            'currency' => 'EUR',
            'language' => 'sk',
            'variableSymbol' => $this->orderID,

        ];

        if(!empty($this->options['template_id'])){
            $this->invoiceData['templateId'] = $this->options['template_id'];
        }

        switch ($type) {
            case 'proforma':
                $data = [
                    'invoiceNumber' => [
                        'id' => ( isset( $this->options[ 'processing_order_counter_id' ] ) ? $this->options[ 'processing_order_counter_id' ] : "" ),
                    ],
                    'invoiceType' => 'PROFORMA_INVOICE',
                ];
                break;
            case 'invoice':
                $data = [
                    'invoiceNumber' => [
                        'id' => ( isset( $this->options[ 'completed_order_counter_id' ] ) ? $this->options[ 'completed_order_counter_id' ] : "" ),
                    ],
                    'invoiceType' => 'INVOICE',
                ];
                break;
            case 'credit_note':
                $data = [
                    'invoiceNumber' => [
                        'id' => ( isset( $this->options[ 'credit_note_counter_id' ] ) ? $this->options[ 'credit_note_counter_id' ] : "" ),
                    ],
                    'invoiceType' => 'INVOICE',
                ];
                break;
        }

        $this->invoiceData = array_merge($data, $this->invoiceData);
    }

    private function preparePaymentData()
    {
        $this->invoiceData[ 'paymentType' ] = [
            'name' => $this->order->get_payment_method_title(),
        ];
    }

    private function checkDiscount()
    {
    }

    private function prepareIssuerData()
    {
        $fields = ['name', 'phone', 'web', 'email'];
        $data = [
            'issuer' => [],
        ];
        foreach ($fields as $field) {
            if (isset($this->options[ 'issuer_'.$field ]) && $this->options[ 'issuer_'.$field ]) {
                $data[ 'issuer' ][ $field ] = $this->options[ 'issuer_'.$field ];
            }
        }
        $this->invoiceData = array_merge($this->invoiceData, $data);
    }

    private function prepareReceiverData()
    {
        $data = [
            'reciever' => [
                'name' => $this->order->get_billing_first_name().' '.$this->order->get_billing_last_name(),
                'street' => $this->order->get_billing_address_1(),
                'city' => $this->order->get_billing_city(),
                'country' => UctoplusHelpers::getUserCountry($this->order->get_billing_country()),
            ],
        ];

        $this->invoiceData = array_merge($this->invoiceData, $data);
    }

    private function addItems()
    {
        $data = [
            'items' => [],
        ];

        foreach ($this->order->get_items() as $item) {
            $price = $item->get_subtotal();

            $item_subtotal = $this->order->get_item_subtotal($item, false, false);
            $item_tax = ($item_subtotal > 0) ? round(($item[ 'line_subtotal_tax' ] / max(1, $item[ 'qty' ])) / $item_subtotal * 100) : 0;

            $data[ 'items' ][] = [
                'name' => $item->get_name(),
                'quantity' => $item->get_quantity(),
                'priceWithoutTax' => $item->get_subtotal() / $item->get_quantity(),
                'taxPercentage' => $item_tax,
                'type' => 'ks',
                'discount' => 0,
            ];
        }

        if ($this->order->get_shipping_total()) {
            $shippingPrice = $this->order->get_shipping_total();


            $shipping_price = $this->order->get_shipping_total() + round($this->order->get_shipping_tax());
            $shippingTax = ($shipping_price > 0) ? round($this->order->get_shipping_tax() / $this->order->get_shipping_total() * 100) : 0;

            $data[ 'items' ][] = [
                'name' => 'Poštovné',
                'quantity' => 1,
                'priceWithoutTax' => $shippingPrice,
                'taxPercentage' => $shippingTax,
                'type' => 'ks',
                'discount' => 0,
            ];
        }

        $this->invoiceData = array_merge($this->invoiceData, $data);
    }

    private function sendInvoice()
    {
        return $this->request('POST', '/invoice/add', $this->invoiceData);
    }
}