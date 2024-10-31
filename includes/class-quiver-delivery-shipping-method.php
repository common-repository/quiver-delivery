<?php

// Exit if accessed directly or class already exists
if (!defined('ABSPATH') || class_exists('Quiver_Delivery_Shipping_Method', false)) {
    return;
}

class Quiver_Delivery_Shipping_Method extends WC_Shipping_Method
{

    private $METHOD_ID = 'quiver_delivery';
    private $CODE_DELIVERY_TYPE_MAP = [
        'QUI-IM' => 'IMMEDIATE',
        'QUIV_DEL_IM' => 'IMMEDIATE',
        'QUI-SD' => 'SAME_DAY',
        'QUIV_DEL_SD' => 'SAME_DAY',
        'QUI-ND' => 'NEXT_DAY',
        'QUIV_DEL_ND' => 'NEXT_DAY',
        'QUI-WN' => 'WINDOW',
        'QUIV_DEL_WN' => 'WINDOW'
    ];
    private $CREATE_DELIVERY_URL = 'https://api.quiver.london/task/deliveries';
    private $CREATE_QUOTE_URL = 'https://api.quiver.london/task/quotes';

    /**
     * Logger
     *
     * @var QuiverDeliveryLogger
     */
    protected $logger = null;


    /**
     * Init and hook in the integration.
     */
    public function __construct($instance_id = 0)
    {
        $this->id = $this->METHOD_ID;
        $this->instance_id = absint($instance_id);
        $this->method_title = __('Quiver Delivery', $this->METHOD_ID);
        $this->method_description = $this->METHOD_ID;

        $this->init();
    }
    /**
     * Initializes the instance.
     *
     * @since [*next-version*]
     */
    protected function init()
    {
        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
    }

    /**
     * Initialize integration settings form fields.
     *
     */
    public function init_form_fields()
    {

        $this->form_fields = array(
            'api_key' => array(
                'title'             => __('API Key', $this->METHOD_ID),
                'type'              => 'text',
                'description'       => __('This can be found by visiting app.quiver.co.uk and navigating to the Settings page.', $this->METHOD_ID),
                'desc_tip'          => true,
                'default'           => '',
                'placeholder'        => '',
            ),
        );
    }


    public function clear_shipping_rates_cache()
    {
        $packages = WC()->cart->get_shipping_packages();
        foreach ($packages as $key => $value) {
            $shipping_session = "shipping_for_package_$key";
            unset(WC()->session->$shipping_session);
        }
    }

    public function calculate_shipping($package = array())
    {
        if ($this->get_option('api_key', '') === '') {
            $this->log_msg('Please enter your Quiver API key to use the Quiver plugin. This can be found by visiting app.quiver.co.uk and navigating to the Settings page.');
        }
        
        if (is_checkout()) {

            $destination = $package['destination'];
            $address = [
                'line1' => array_key_exists('address_1', $destination) ? $destination['address_1'] : '',
                'line2' => array_key_exists('address_2', $destination) ? $destination['address_2'] : '',
                'city' => array_key_exists('city', $destination) ? $destination['city'] : '',
                'postcode' => array_key_exists('postcode', $destination) ? $destination['postcode'] : '',
            ];

            if (empty($address['postcode'])) {
                return;
            }

            $lineItems = [];
            foreach (WC()->cart->get_cart() as $cart_item) {
                $lineItem = ['merchantProductId' => $this->to_external_product_id($cart_item['product_id'], $cart_item['variation_id']), 'quantity' => $cart_item['quantity']];
                array_push($lineItems, $lineItem);
            }

            $order = [
                'lineItems' => $lineItems,
                'price' => [
                    'subtotal' => (float) WC()->cart->get_subtotal() + (float) WC()->cart->get_subtotal_tax()
                ],
            ];
            $delivery = [
                'address' => $address,
            ];
            $request = [
                'delivery' => $delivery,
                'order' => $order
            ];

            $response = $this->post_request($this->CREATE_QUOTE_URL, $request);
            if ($response) {
                $rates = $response->rates;
                if (!is_null($rates) && count($rates) > 0) {
                    usort($rates, array($this, "rate_cmp"));
                    foreach ($rates as $rate) {
                        if (is_null($rate->error)) {
                            $this->add_rate([
                                'id' => $rate->code,
                                'label' => $rate->title,
                                'cost' => $rate->price,
                                'calc_tax' => 'per_item',
                                'meta_data' => [
                                    'code' => $rate->code,
                                    'description' => $rate->description,
                                ]
                            ]);
                        }   
                    }
                } else {
                    $this->log_msg('Quiver unable to offer rates for order');
                    $this->log_msg(print_r($request, TRUE));
                }
            } else {
                $this->log_msg('Quiver unable to offer rates for order');
                $this->log_msg(print_r($request, TRUE));
            }
        }
    }



    public function create_delivery($order_id)
    {
        $this->log_msg(sprintf('New order with id %d created', $order_id));
        if (!$order_id)
            return;

        $order = wc_get_order($order_id);
        

        $shippingMethods = $order->get_shipping_methods();
        if (is_null($shippingMethods) || count($shippingMethods) == 0){
            $this->log_msg(sprintf('New order with id %d has no shipping methods', $order_id));
            $this->log_msg(print_r($order, TRUE));
            return;
        }

        $shippingMethod = array_values($shippingMethods)[0];
        if ($shippingMethod->get_method_id() !== $this->METHOD_ID) {
            $this->log_msg(sprintf('New order with id %d has wrong carrier %s', $order_id, $shippingMethod));
            $this->log_msg(print_r($order, TRUE));
            return;
        }

        $code = $shippingMethod->get_meta('code');
        $delivery_type = $this->CODE_DELIVERY_TYPE_MAP[$code];
        $quiver_order = $this->order_to_quiver_order($order);
        $quiver_line_items_and_collection_address = $this->order_to_line_items_and_collection_address($order);
        $quiver_order['lineItems'] = $quiver_line_items_and_collection_address['lineItems'];
        $quiver_customer = $this->order_to_quiver_customer($order);
        $quiver_purchaser = $this->order_to_quiver_purchaser($order);
        $quiver_address = $this->order_to_quiver_address($order);
        $delivery_date = $this->order_to_delivery_date($order);

        $request = [
            'deliveryType' => $delivery_type,
            'order' => $quiver_order,
            'customer' => $quiver_customer,
            'collection' => [
                'addressId' => $quiver_line_items_and_collection_address['collectionAddressId']
            ],
            'delivery' => [
                'address' => $quiver_address,
                'date' => $delivery_date,
            ]
        ];

        if (
            $quiver_purchaser['firstName'] != $quiver_customer['firstName']
            || $quiver_purchaser['lastName'] != $quiver_customer['lastName']
            || $quiver_purchaser['company'] != $quiver_customer['company']
        ) {
            $request['purchaser'] = $quiver_purchaser;
        }

        $response = $this->post_request($this->CREATE_DELIVERY_URL, $request);
        if (!$response) {
            $this->log_msg('Quiver unable to create delivery for order');
            $this->log_msg(print_r($request, TRUE));
        }

    }

    private $DELIVERY_DATE_META_FIELDS = [
        '_delivery_date_', 
        '_delivery_date',
        'delivery_date',
        'delivery-date',
        'delivery date',
        'Delivery Date', 
    ];

    private $BACK_ORDERED_META_FIELDS = [
        'Back-ordered',
        'custom_stock_value'
    ];
    

    /**
     * @param WC_Order $order
     * @return array
     */
    private function order_to_delivery_date($order)
    {
        // 1. Check Order Meta
        // 1.1 Direct access
        foreach ($this->DELIVERY_DATE_META_FIELDS as $metaKey) {
            $value = $order->get_meta($metaKey);
            if ($value !== "") {
                return $this->toDateStr($value);
            }
        }
        // 1.2 Loop access
        foreach ($order->get_meta_data() as $meta) {
            $key = strtolower($meta->key);
            if (str_contains($key, 'delivery') && str_contains($key, 'date')) {
                $value = $meta->value;
                if ($value !== "") {
                    return $this->toDateStr($value);
                }
            }
        }
        // 2. Check Order Line Items
        // 2.1 Direct access
        $deliveryDates = [];
        foreach ($order->get_items() as $lineItem) {
            foreach ($this->DELIVERY_DATE_META_FIELDS as $metaKey) {
                $value = $lineItem->get_meta($metaKey);
                if ($value !== "") {
                    array_push($deliveryDates, $this->toDateStr($value));
                }
            }
            foreach ($this->BACK_ORDERED_META_FIELDS as $metaKey) {
                $value = $lineItem->get_meta($metaKey);
                if ($value !== "") {
                    array_push($deliveryDates, $this->backOrderedDate($value));
                }
            }
        }
        if (count($deliveryDates) !== 0) {
            return min($deliveryDates);
        }
        // 2.2 Loop access
        foreach ($order->get_items() as $lineItem) {
            foreach ($lineItem->get_meta_data() as $meta) {
                $key = strtolower($meta->key);
                if (str_contains($key, 'delivery') && str_contains($key, 'date')) {
                    $value = $meta->value;
                    if ($value !== "") {
                        array_push($deliveryDates, $this->toDateStr($value));
                    }
                }
            }
        }
        if (count($deliveryDates) !== 0) {
            return min($deliveryDates);
        }
    }

    private $BACK_ORDERED_DISPATCH_REGEX = '/^.*[0-9]+-([0-9]+).*Days$/';

    /**
     * @return string
     */
    private function backOrderedDate($backOrderedStr) {
        $dateIncrement = ' + 3 days';
        $matches = [];
        $match = preg_match($this->BACK_ORDERED_DISPATCH_REGEX, $backOrderedStr, $matches);
        if ($match && count($matches) > 1) {
            $dateIncrement = sprintf(" + %d days",$matches[1]);
        }

        $today = date('Y-m-d');
        return date('Y-m-d', strtotime($today. $dateIncrement));
    }
    
    /**
     * @param string $value
     * @return string
     */
    private function toDateStr($value) {
        $preformattedDate = str_replace('/', '-', $value);
        $unixTimestamp = strtotime($preformattedDate);
        return date('Y-m-d', $unixTimestamp);
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    private function order_to_quiver_order($order)
    {
        return [
            'merchantOrderId' => strval($order->get_id()),
            'price' => [
                'total' => (float) $order->get_total(),
                'subtotal' => ((float) $order->get_total()) - ((float) $order->get_shipping_total())
            ],
            'note' => $order->get_customer_note()
        ];
    }


    private $COLLECTION_ADDRESS_SHIPPING_CLASS_OVERRIDE = 'floristry';
    private $COLLECTION_ADDRESS_OVERRIDE = 20851;

    /**
     * @param WC_Order $order
     * @return array
     */
    private function order_to_line_items_and_collection_address($order)
    {
        $lineItems = [];
        $collectionAddress = null;
        foreach ($order->get_items() as $item_id => $item) {
            $productId = $item['product_id'];
            $product = wc_get_product( $productId );
            if ($product->get_shipping_class() === $this->COLLECTION_ADDRESS_SHIPPING_CLASS_OVERRIDE) {
                $collectionAddress = $this->COLLECTION_ADDRESS_OVERRIDE;
            }
            $lineItem = ['merchantProductId' => $this->to_external_product_id($item['product_id'], $item['variation_id']), 'quantity' => $item->get_quantity()];
            array_push($lineItems, $lineItem);
        }
        return ['lineItems' => $lineItems, 'collectionAddressId' => $collectionAddress];
    }

    /**
     * @param int $product_id
     * @param int $variant_id
     * @return string
     */
    private function to_external_product_id($product_id, $variation_id)
    {
        $productId = strval($product_id);
        return $variation_id === 0 ? $productId : $productId . '.' . strval($variation_id);
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    private function order_to_quiver_address($order)
    {
        return [
            'line1' => $order->get_shipping_address_1(),
            'line2' => $order->get_shipping_address_2(),
            'city' => $order->get_shipping_city(),
            'postcode' => $order->get_shipping_postcode(),
        ];
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    private function order_to_quiver_customer($order)
    {
        return [
            'firstName' => $order->get_shipping_first_name(),
            'lastName' => $order->get_shipping_last_name(),
            'company' => $order->get_shipping_company(),
            'emailAddress' => $order->get_billing_email(),
            'phoneNumber' => $order->get_billing_phone()
        ];
    }

    /**
     * @param WC_Order $order
     * @return array
     */
    private function order_to_quiver_purchaser($order)
    {
        return [
            'firstName' => $order->get_billing_first_name(),
            'lastName' => $order->get_billing_last_name(),
            'company' => $order->get_billing_company(),
            'emailAddress' => $order->get_billing_email(),
            'phoneNumber' => $order->get_billing_phone()
        ];
    }

    public function process_admin_options()
    {
        return parent::process_admin_options();
    }

    private function rate_cmp($a, $b)
    {
        return ($a->price < $b->price) ? -1 : 1;
    }


    private function log_msg($msg)
    {
        if (!$this->logger) {
            $this->logger = new QuiverDeliveryLogger();
        }
        $this->logger->write($msg);
    }


    private function post_request($url, $body)
    {
        $args = [
            'headers' => [
                'x-api-key' => $this->get_option('api_key', ''),
            ],
            'body' => wp_json_encode($body)
        ];
        $response = wp_remote_post($url, $args);


        if (!is_wp_error($response)) {
            return json_decode(wp_remote_retrieve_body($response));
        } else {
            return false;
        }
    }
}
