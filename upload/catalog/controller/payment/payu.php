<?php
/*
* ver. 0.1.9
* PayU Payment Modules
*
* @copyright  Copyright 2012 by PayU
* @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
* http://www.payu.com
* http://twitter.com/openpayu
*/
class ControllerPaymentPayU extends Controller
{
    //loading PayU SDK
    protected function loadLibConfig()
    {
        require_once(DIR_SYSTEM . 'library/openpayu.php');
        //loading saved configuration
        OpenPayU_Configuration::setMerchantPosId($this->config->get('payu_merchantposid'));
        OpenPayU_Configuration::setPosAuthKey($this->config->get('payu_posauthkey'));
        OpenPayU_Configuration::setClientId($this->config->get('payu_clientid'));
        OpenPayU_Configuration::setClientSecret($this->config->get('payu_clientsecret'));
        OpenPayU_Configuration::setSignatureKey($this->config->get('payu_signaturekey'));
        if ($this->config->get('payu_test')) {
            OpenPayU_Configuration::setEnvironment();
        } else {
            OpenPayU_Configuration::setEnvironment('secure');
        }

        $this->logger = new Log('payu.txt');
    }

    protected function index()
    {
        $this->language->load('payment/payu');
        $this->load->model('payment/payu');

        $this->loadLibConfig();

        $this->data['text_testmode'] = $this->language->get('text_testmode');
        $this->data['button_confirm'] = $this->language->get('button_confirm');
        $this->data['testmode'] = $this->config->get('payu_test');
        $this->data['payu_button'] = $this->config->get('payu_button');
        $this->data['error'] = false;

        $order = $this->buildorder();

        //building Order Create Request
        $result = OpenPayU_Order::create($order);

        if ($result->getSuccess()) {

            $this->model_payment_payu->addOrder($this->session->data['order_id'], $this->session->data['sessionId']);

            $result = OpenPayU_OAuth::accessTokenByClientCredentials();

            if ($result->getSuccess()) {
                $this->data['actionUrl'] = OpenPayU_Configuration::getSummaryUrl();
                $this->data['sessionId'] = $this->session->data['sessionId'];
                $this->data['accessToken'] = $result->getAccessToken();
                $this->data['lang'] = strtolower($this->session->data['language']);
            }
        } else {
            $this->data['error'] = true;
            $this->data['text_error'] = $this->language->get('text_error_message');
            $this->logger->write(
                $result->getError() . ' [request: ' . serialize($result->getRequest()) . ', response: ' . serialize(
                    $result->getResponse()
                ) . ']'
            );
        }

        //Setting template
        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/payu.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/payment/payu.tpl';
        } else {
            $this->template = 'default/template/payment/payu.tpl';
        }
        $this->render();
    }


    public function callback()
    {
    }

    //Before summary redirection
    public function beforesummary()
    {
        $this->loadLibConfig();
        $result = OpenPayU_OAuth::accessTokenByCode(
            $this->request->get['code'],
            $this->url->link('payment/payu/beforesummary', '', 'SSL')
        );

        echo "Redirecting..";
        echo '<form method="GET" action="' . OpenPayu_Configuration::getSummaryUrl(
            ) . '" id="payu_checkout" lang="' . $this->session->data['language'] . '">';
        echo '<input type="hidden" name="lang" value="' . $this->session->data['language'] . '">';
        echo '<input type="hidden" name="sessionId" value="' . $_SESSION['sessionId'] . '">';
        echo '<input type="hidden" name="oauth_token" value="' . $result->getAccessToken() . '">';
        echo '</form>';
        echo '<script type="text/javascript">document.getElementById("payu_checkout").submit();</script>';
    }

    //Shipping update
    public function shipping()
    {
        $this->loadLibConfig();
        if ($this->request->post['DOCUMENT']) {
            $xml = htmlspecialchars_decode($this->request->post['DOCUMENT']);
            $result = OpenPayU_Order::consumeMessage($xml);

            $countrycode = $result->getCountryCode();
            $reqId = $result->getReqId();
            $sessionId = $result->getSessionId();
            $order_id = $this->model_payment_payu->getOrderIdBySessionId(substr($sessionId, 0, 32));

            $this->load->model('localisation/country');
            $country_list = $this->model_localisation_country->getCountries();
            $country_id = 0;
            foreach ($country_list as $country) {
                if ($country['iso_code_2'] == $countrycode) {
                    $country_id = $country['country_id'];
                }
            }

            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);
            $shippingCostList = array();

            $this->tax->setShippingAddress($country_id, 0);
            $this->tax->setPaymentAddress($country_id, 0);
            $this->tax->setStoreAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));

            foreach ($this->getShippings($order_id, $country_id) as $onemethod) {
                $shipmethod = array(
                    'Type' => $onemethod['title'],
                    'CountryCode' => $countrycode,
                    'Price' => array(
                        'Gross' => str_ireplace(
                            '.',
                            '',
                            $this->currency->format(
                                $this->tax->calculate($onemethod['cost'], $onemethod['tax_class_id']),
                                $order_info['currency_code'],
                                false,
                                false
                            )
                        ),
                        'Net' => str_ireplace(
                            '.',
                            '',
                            $this->currency->format($onemethod['cost'], $order_info['currency_code'], false, false)
                        ),
                        'Tax' => str_ireplace(
                            '.',
                            '',
                            $this->currency->format(
                                $this->tax->calculate(
                                    $onemethod['cost'],
                                    $onemethod['tax_class_id']
                                ) - $onemethod['cost'],
                                $order_info['currency_code'],
                                false,
                                false
                            )
                        ),
                        'CurrencyCode' => $order_info['currency_code']
                    )
                );
                if (false) {
                    $shipmethod[]['State'] = $order_info['shipping_zone'];
                    $shipmethod[]['City'] = $order_info['shipping_city'];
                }
                $shippingCostList[]['ShippingCost'] = $shipmethod;
            }

            $shippingCost = array(
                'CountryCode' => $countrycode,
                'ShipToOtherCountry' => 'true',
                'ShippingCostList' => $shippingCostList
            );
            $xml = OpenPayU::buildShippingCostRetrieveResponse($shippingCost, $reqId, $countrycode);

            if (!$result->getSuccess()) {
                $this->logger->write(
                    $result->getError() . ' [request: ' . serialize($result->getRequest()) . ', response: ' . serialize(
                        OpenPayU::parseOpenPayUDocument($xml)
                    ) . ']'
                );
            }

            header("Content-type: text/xml");
            echo $xml;
        }
    }

    //Success page
    public function paymentsuccess()
    {
        $this->load->model('payment/payu');

        if (!empty($this->request->get['error'])) {
            $this->redirect($this->url->link('checkout/cart'));
        }

        $this->loadLibConfig();
        $result = OpenPayU_Order::retrieve($this->session->data['sessionId']);

        if (!$result->getSuccess()) {
            $this->logger->write(
                $result->getError() . ' [request: ' . serialize($result->getRequest()) . ', response: ' . serialize(
                    $result->getResponse()
                ) . ']'
            );
        } else {
            $response = $result->getResponse();
            $session_id = substr(
                $response['OpenPayU']['OrderDomainResponse']['OrderRetrieveResponse']['SessionId'],
                0,
                32
            );;
            $order_id = $this->model_payment_payu->getOrderIdBySessionId($session_id);

            if (!empty($order_id)) {
                $this->updatecustomerdata(
                    $order_id,
                    $response['OpenPayU']['OrderDomainResponse']['OrderRetrieveResponse']
                );
            }
        }

        $this->redirect($this->url->link('checkout/success'));
    }

    //Cancel Page
    public function paymentcancel()
    {
        $this->redirect($this->url->link('checkout/cart'));
    }

    //Notification
    public function ordernotify()
    {
        $this->loadLibConfig();
        $this->load->model('payment/payu');

        if ($this->request->post['DOCUMENT']) {
            $doc = htmlspecialchars_decode($this->request->post['DOCUMENT']);

            if (empty($doc)) {
                return "error";
            }

            try {
                $result = OpenPayU_Order::consumeMessage($doc, false);
                $notification_response = $result->getResponse();
                $session_id = $result->getSessionId();

                if ($result->getMessage() == 'OrderNotifyRequest') {
                    $order_id = $this->model_payment_payu->getOrderIdBySessionId(substr($session_id, 0, 32));
                    $result = OpenPayU_Order::retrieve($session_id);
                    $response = $result->getResponse();

                    if (!$result->getSuccess()) {
                        $this->logger->write(
                            $result->getError() . ' [request: ' . serialize(
                                $result->getRequest()
                            ) . ', response: ' . serialize($result->getResponse()) . ']'
                        );
                    } else {
                        $orderStatus = $response['OpenPayU']['OrderDomainResponse']['OrderRetrieveResponse']['OrderStatus'];
                        $paymentStatus = (isset($response['OpenPayU']['OrderDomainResponse']['OrderRetrieveResponse']['PaymentStatus'])) ? $response['OpenPayU']['OrderDomainResponse']['OrderRetrieveResponse']['PaymentStatus'] : false;
                        $newstatus = $this->getpaymentstatusid($paymentStatus, $orderStatus);

                        $this->updatecustomerdata(
                            $order_id,
                            $response['OpenPayU']['OrderDomainResponse']['OrderRetrieveResponse']
                        );

                        $this->updatestatus($order_id, $newstatus);

                        header("Content-Type:text/xml");
                        echo $notification_response;
                    }
                }
            } catch (Exception $e) {
                $this->logger->write($e->getMessage());

                return null;
            }
        }
    }

    //Getting system status
    protected function getpaymentstatusid($paymentStatus, $orderStatus)
    {
        $this->load->model('payment/payu');
        if (!empty($paymentStatus)) {
            switch ($paymentStatus) {
                case "PAYMENT_STATUS_NEW" :
                    return $this->config->get('payu_new_status');
                case "PAYMENT_STATUS_CANCEL" :
                    return $this->config->get('payu_cancelled_status');
                case "PAYMENT_STATUS_REJECT" :
                    return $this->config->get('payu_reject_status');
                case "PAYMENT_STATUS_INIT" :
                    return $this->config->get('payu_pending_status');
                case "PAYMENT_STATUS_SENT" :
                    return $this->config->get('payu_sent_status');
                case "PAYMENT_STATUS_NOAUTH" :
                    return $this->config->get('payu_failed_status');
                case "PAYMENT_STATUS_REJECT_DONE" :
                    return $this->config->get('payu_returned_status');
                case "PAYMENT_STATUS_END" :
                    return $this->config->get('payu_complete_status');
                case "PAYMENT_STATUS_ERROR" :
                    return $this->config->get('payu_failed_status');
                default:
                    break;
            }
        }
        switch ($orderStatus) {
            case "ORDER_STATUS_CANCEL" :
                return $this->config->get('payu_cancelled_status');
            case "ORDER_STATUS_PENDING" :
                return $this->config->get('payu_pending_status');
            case "ORDER_STATUS_COMPLETE" :
                return $this->config->get('payu_complete_status');
            case "ORDER_STATUS_NEW" :
                return $this->config->get('payu_new_status');
            case "ORDER_STATUS_REJECT" :
                return $this->config->get('payu_reject_status');
            case "ORDER_STATUS_SENT" :
                return $this->config->get('payu_sent_status');
            default:
                break;
        }

        return 0;
    }

    //Status update
    protected function updatestatus($order_id, $order_status_id)
    {
        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($order_id);

        if (!$order_info['order_status_id']) {
            $this->model_checkout_order->confirm($order_id, $order_status_id, '', true);
        } elseif ($order_info['order_status_id'] != $order_status_id) {
            $this->model_checkout_order->update($order_id, $order_status_id);
        }

        return 0;
    }

    //Customer data update from the data sent by PayU
    public function updatecustomerdata($order_id, $customerdata)
    {
        $this->load->model('payment/payu');
        $this->load->model('checkout/order');

        $update_data = $this->model_checkout_order->getOrder($order_id);
        $this->load->model('localisation/country');
        $country_list = $this->model_localisation_country->getCountries();
        $country_id = 0;
        if (!empty($update_data)) {
            if (!empty($customerdata['Invoice'])) {
                $update_data['payment_iso_code_2'] = $customerdata['Invoice']['Billing']['CountryCode'];
                $update_data['payment_zone'] = $customerdata['Invoice']['Billing']['State'];
                if ($customerdata['Invoice']['Billing']['RecipientName']) {
                    $firstlastcompany = explode(" ", $customerdata['Invoice']['Billing']['RecipientName'], 3);
                    $update_data['payment_firstname'] = $firstlastcompany[0];
                    $update_data['payment_lastname'] = $firstlastcompany[1];
                    $update_data['payment_company'] = ' ';
                }
                $addressstring = $customerdata['Invoice']['Billing']['Street'] . " " . $customerdata['Invoice']['Billing']['HouseNumber'] . "/" . $customerdata['Invoice']['Billing']['ApartmentNumber'];
                $update_data['payment_address_1'] = substr($addressstring, 0, 128);
                $update_data['payment_address_2'] = substr($addressstring, 128);
                $update_data['payment_city'] = $customerdata['Invoice']['Billing']['City'];
                $update_data['payment_postcode'] = $customerdata['Invoice']['Billing']['PostalCode'];
            }

            if (!empty($customerdata['CustomerRecord'])) {
                $update_data['firstname'] = $customerdata['CustomerRecord']['FirstName'];
                $update_data['lastname'] = $customerdata['CustomerRecord']['LastName'];
                $update_data['email'] = $customerdata['CustomerRecord']['Email'];
                $update_data['telephone'] = $customerdata['CustomerRecord']['Phone'];
            }

            if (!empty($customerdata['Shipping'])) {
                $update_data['shipping_method'] = $customerdata['Shipping']['ShippingType'];
                $update_data['shipping_iso_code_2'] = $customerdata['Shipping']['Address']['CountryCode'];
                foreach ($country_list as $country) {
                    if ($country['iso_code_2'] == $update_data['shipping_iso_code_2']) {
                        $country_id = $country['country_id'];
                    }
                }
                $update_data['shipping_country_id'] = $country_id;
                $this->tax->setShippingAddress($update_data['shipping_country_id'], $update_data['shipping_zone_id']);
                $this->tax->setPaymentAddress($update_data['payment_country_id'], $update_data['payment_zone_id']);
                $this->tax->setStoreAddress(
                    $this->config->get('config_country_id'),
                    $this->config->get('config_zone_id')
                );
                $allShippings = $this->getShippings($order_id, $country_id);
                $update_data['shipping_code'] = "Unknown";
                foreach ($allShippings as $oneShipping) {
                    if ($update_data['shipping_method'] == $oneShipping['title']) {
                        $choosenOne = $oneShipping;
                    }
                }

                $update_data['shipping_code'] = $choosenOne['code'];

                if (isset($customerdata['Shipping']['Address']) && !empty($customerdata['Shipping']['Address'])) {
                    if (isset($customerdata['Shipping']['Address']['State'])) {
                        $update_data['shipping_zone'] = $customerdata['Shipping']['Address']['State'];
                    }

                    list($update_data['shipping_firstname'], $update_data['shipping_lastname']) = explode(
                        " ",
                        $customerdata['Shipping']['Address']['RecipientName'],
                        2
                    );
                    $addressstring = $customerdata['Shipping']['Address']['Street'] . " " . $customerdata['Shipping']['Address']['HouseNumber'] . (isset($customerdata['Shipping']['Address']['ApartmentNumber']) ? "/" . $customerdata['Shipping']['Address']['ApartmentNumber'] : '') . ' ' . $customerdata['Shipping']['Address']['PostalCode'];
                    $update_data['shipping_address_1'] = substr($addressstring, 0, 128);
                    $update_data['shipping_address_2'] = substr($addressstring, 128);
                    $update_data['shipping_city'] = $customerdata['Shipping']['Address']['City'];
                    $update_data['shipping_postcode'] = $customerdata['Shipping']['Address']['PostalCode'];
                    $newTotal = $customerdata['Shipping']['ShippingCost']['Net'];
                }

                if (isset($update_data['products'])) {
                    foreach ($update_data['products'] as $oneProduct) {
                        $newTotal += $oneProduct['price'] * $oneProduct['quantity'];
                    }
                }
                $update_data['total'] = $newTotal;
            }
            $this->model_payment_payu->customerupdate($order_id, $update_data);
        }
    }

    //express checkout - OK
    public function expresscheckout()
    {
        ob_start();
        $this->load->model('checkout/order');
        $this->loadLibConfig();

        $cart = $this->cart->getProducts();

        if (empty($cart)) {
            $this->redirect($this->url->link('checkout/cart'));
        }

        $this->session->data['order_id'] = $this->collectData();
        $order = $this->buildorder();

        $result = OpenPayU_Order::create($order);
        ob_end_flush();

        if ($result->getSuccess()) {
            $this->model_payment_payu->addOrder($this->session->data['order_id'], $this->session->data['sessionId']);

            $result = OpenPayU_OAuth::accessTokenByClientCredentials();

            if ($result->getSuccess()) {
                $values = array(
                    'sessionId' => $this->session->data['sessionId'],
                    'oauth_token' => $result->getAccessToken(),
                    'lang' => strtolower($this->session->data['language'])
                );

                $this->redirect(OpenPayU_Configuration::getSummaryUrl() . '?' . http_build_query($values, '&'));
            } else {
                $this->logger->write($result->getError() . ' [' . serialize($result->getResponse()) . ']');
            }
        } else {
            $this->logger->write($result->getError() . ' [' . serialize($result->getResponse()) . ']');
            $this->redirect($this->url->link('checkout/cart'));
        }
    }

    //building order for express checkout
    public function buildorder()
    {
        $this->language->load('payment/payu');
        $this->load->model('payment/payu');
        $this->loadLibConfig();

        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $this->load->model('localisation/country');
        $this->tax->setShippingAddress($order_info['shipping_country_id'], $order_info['shipping_zone_id']);
        $this->tax->setPaymentAddress($order_info['payment_country_id'], $order_info['payment_zone_id']);
        $this->tax->setStoreAddress($this->config->get('config_country_id'), $this->config->get('config_zone_id'));
        $grandTotal = 0;
        $cartItems = array();
        $orderType = 'VIRTUAL';

        $decimalPlace = $this->currency->getDecimalPlace();
        foreach ($this->cart->getProducts() as $item) {
            if(empty($decimalPlace)) {
                $item['price'] *= 100;
            }

            $gross = $this->tax->calculate($item['price'], $item['tax_class_id']);
            if ($item['shipping'] == 1) {
                $orderType = 'MATERIAL';
            }
            $cartItem = array(
                "Quantity" => $item['quantity'],
                "Product" => array(
                    "Name" => $item['name'],
                    //"ExtraInfo" => $item['description'], //not needed, can't be sure if not empty
                    "UnitPrice" => array(
                        "Gross" => str_ireplace(
                            '.',
                            '',
                            $this->currency->format($gross, $order_info['currency_code'], false, false)
                        ),
                        "Net" => str_ireplace(
                            '.',
                            '',
                            $this->currency->format($item['price'], $order_info['currency_code'], false, false)
                        ),
                        "Tax" => str_ireplace(
                            '.',
                            '',
                            $this->currency->format($gross - $item['price'], $order_info['currency_code'], false, false)
                        ),
                        "CurrencyCode" => $order_info['currency_code']
                    )
                )
            );

            if (true === 1) {
                $cartItem['Product']["Weight"] = array(
                    'Amount' => $item['weight'],
                    'Unit' => $this->cart->weight->getUnit($item['weight_class_id'])
                );
            }
            $cartItems[]['ShoppingCartItem'] = $cartItem;
            $grandTotal += $cartItem['Product']['UnitPrice']['Gross'] * $cartItem['Quantity'];
        }

        $shoppingCart = array(
            'GrandTotal' => $grandTotal,
            'CurrencyCode' => $order_info['currency_code'],
            'ShoppingCartItems' => $cartItems
        );

        $this->session->data['sessionId'] = md5(rand() . rand() . rand() . rand()) . $this->session->data['order_id'];


        $order = array(
            'MerchantPosId' => OpenPayU_Configuration::getMerchantPosId(),
            'SessionId' => $_SESSION['sessionId'],
            'OrderUrl' => $this->url->link('payment/payu/callback') . '?order=' . $this->session->data['order_id'],
            'OrderCreateDate' => date("c"),
            'OrderDescription' => 'Order ' . $this->session->data['order_id'],
            'MerchantAuthorizationKey' => OpenPayU_Configuration::getPosAuthKey(),
            'OrderType' => $orderType, // keyword: MATERIAL or VIRTUAL
            'ShoppingCart' => $shoppingCart
        );

        $OCReq = array(
            'ReqId' => md5(rand()),
            'CustomerIp' => (($order_info['ip'] == "::1" || $order_info['ip'] == "::" || !preg_match(
                    "/^((?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9]).){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/m",
                    $order_info['ip']
                )) ? '127.0.0.1' : $order_info['ip']),
            // note, this should be real ip of customer retrieved from $_SERVER['REMOTE_ADDR']
            'NotifyUrl' => $this->url->link('payment/payu/ordernotify'),
            // url where payu service will send notification with order processing status changes
            'OrderCancelUrl' => $this->url->link('payment/payu/paymentcancel'),
            'OrderCompleteUrl' => $this->url->link('payment/payu/paymentsuccess'),
            'Order' => $order
        );

        $customer = array();


        if (!empty($order_info['email'])) {
            $customer = array(
                'Email' => $order_info['email'],
                'FirstName' => $order_info['firstname'],
                'LastName' => $order_info['lastname'],
                'Phone' => $order_info['telephone']
            );
        } elseif (!empty($this->session->data['customer_id'])) {
            $this->load->model('account\customer');
            $custdata = $this->model_account_customer->getCustomer($this->session['customer_id']);

            if (!empty($custdata['email'])) {
                $customer = array(
                    'Email' => $custdata['email'],
                    'FirstName' => $custdata['firstname'],
                    'LastName' => $custdata['lastname'],
                    'Phone' => $custdata['telephone']
                );
            }
        }

        if ($orderType == 'MATERIAL') {
            if (!empty($customer) && !empty($order_info['shipping_city']) && !empty($order_info['shipping_postcode']) && !empty($order_info['payment_iso_code_2'])) {
                $customer['Shipping'] = array(
                    'Street' => $order_info['shipping_address_1'] . " " . $order_info['shipping_address_2'],
                    'PostalCode' => $order_info['shipping_postcode'],
                    'City' => $order_info['shipping_city'],
                    'State' => $order_info['shipping_zone'],
                    'CountryCode' => $order_info['payment_iso_code_2'],
                    'AddressType' => "SHIPPING",
                    'RecipientName' => $order_info['shipping_firstname'] . " " . $order_info['shipping_lastname'],
                    'RecipientPhone' => $order_info['telephone'],
                    'RecipientEmail' => $order_info['email']
                );
            }

            if (!empty($order_info['shipping_method'])) {

                $shippingCost = $this->session->data['shipping_method']['cost'];
                if(empty($decimalPlace))
                {
                    $shippingCost *= 100;
                }

                $shippingCostArray = array(
                    'CountryCode' => $order_info['payment_iso_code_2'],
                    'ShipToOtherCountry' => 'true',
                    'City' => $order_info['shipping_city'],
                    'State' => $order_info['shipping_zone'],
                    'ShippingCostList' => array(
                        'ShippingCost' => array(
                            'Type' => $order_info['shipping_method'],
                            'CountryCode' => $order_info['payment_iso_code_2'],
                            'Price' => array(
                                'Gross' => str_ireplace(
                                    '.',
                                    '',
                                    $this->currency->format(
                                        $this->tax->calculate(
                                            $shippingCost,
                                            $this->session->data['shipping_method']['tax_class_id']
                                        ),
                                        $this->session->data['currency'],
                                        false,
                                        false
                                    )
                                ),
                                'Net' => str_ireplace(
                                    '.',
                                    '',
                                    $this->currency->format(
                                        $shippingCost,
                                        $this->session->data['currency'],
                                        false,
                                        false
                                    )
                                ),
                                'Tax' => str_ireplace(
                                    '.',
                                    '',
                                    $this->currency->format(
                                        $this->tax->calculate(
                                            $shippingCost,
                                            $this->session->data['shipping_method']['tax_class_id']
                                        ) - $shippingCost,
                                        $this->session->data['currency'],
                                        false,
                                        false
                                    )
                                ),
                                'CurrencyCode' => $this->session->data['currency']
                            ),
                            'State' => $order_info['shipping_zone'],
                            'City' => $order_info['shipping_city']

                        )
                    )
                );
            } else {
                $shippingCostList = array();
                $shipping_methods = $this->getShippings(
                    $this->session->data['order_id'],
                    $order_info['shipping_country_id']
                );
                $country = $this->model_localisation_country->getCountry($order_info['shipping_country_id']);

                foreach ($shipping_methods as $onemethod) {
                    if(empty($decimalPlace))
                    {
                        $onemethod['cost'] *= 100;
                    }

                    $shipmethod = array(
                        'Type' => $onemethod['title'],
                        'CountryCode' => $country['iso_code_2'],
                        'Price' => array(
                            'Gross' => str_ireplace(
                                '.',
                                '',
                                $this->currency->format(
                                    $this->tax->calculate($onemethod['cost'], $onemethod['tax_class_id']),
                                    $order_info['currency_code'],
                                    false,
                                    false
                                )
                            ),
                            'Net' => str_ireplace(
                                '.',
                                '',
                                $this->currency->format($onemethod['cost'], $order_info['currency_code'], false, false)
                            ),
                            'Tax' => str_ireplace(
                                '.',
                                '',
                                $this->currency->format(
                                    $this->tax->calculate(
                                        $onemethod['cost'],
                                        $onemethod['tax_class_id']
                                    ) - $onemethod['cost'],
                                    $order_info['currency_code'],
                                    false,
                                    false
                                )
                            ),
                            'CurrencyCode' => $order_info['currency_code']
                        )
                    );
                    if (false) {
                        $shipmethod[]['State'] = $order_info['shipping_zone'];
                        $shipmethod[]['City'] = $order_info['shipping_city'];
                    }
                    $shippingCostList[]['ShippingCost'] = $shipmethod;
                }
                $shippingCostArray = array(
                    'CountryCode' => $country['iso_code_2'],
                    'ShipToOtherCountry' => 'true',
                    'ShippingCostList' => $shippingCostList
                );
            }

            $OCReq['ShippingCost'] = array(
                'AvailableShippingCost' => $shippingCostArray,
                'ShippingCostsUpdateUrl' => $this->url->link('payment/payu/shipping')
            );
        }

        if (!empty($customer)) {
            $OCReq['Customer'] = $customer;
        }

        return $OCReq;
    }

    //Returns possible shipping for order & country
    protected function getShippings($order_id, $country_id, $zone_id = 0)
    {
        $shipping_address = array(
            "country_id" => $country_id,
            "zone_id" => $zone_id
        );
        $this->load->model('checkout/order');
        $order_info = $this->model_checkout_order->getOrder($order_id);
        if (isset($order_info['products'])) {
            $products = $order_info['products'];
            if (!empty($products)) {
                foreach ($products as $product) {
                    $this->cart->add($product['product_id'], $product['quantity']);
                }
            }
        }
        $this->load->model('setting/extension');
        $results = $this->model_setting_extension->getExtensions('shipping');
        $quote_data = array();
        foreach ($results as $result) {
            if ($this->config->get($result['code'] . '_status')) {
                $this->load->model('shipping/' . $result['code']);
                $quote = $this->{'model_shipping_' . $result['code']}->getQuote($shipping_address);
                if ($quote) {
                    $quote_data[$result['code']] = $quote['quote'][$result['code']];
                }
            }
        }

        //error connected with cart
        //$this->cart->clear();
        return $quote_data;
    }

    //test functions
    public function addscript()
    {
        $script = '/catalog/view/javascript/payu.js';
        $this->document->addScript($script);
        foreach ($this->document->getScripts() as $script) {
            echo "<script type=\"text/javascript\" src=\"$script\"</script>";
        }
    }

    public function outscript()
    {
        foreach ($this->document->getScripts() as $script) {
            echo "<script type=\"text/javascript\" src=\"$script\"</script>";
        }
    }

    //collecting data for expresscheckout
    public function collectData()
    {
        $allData = array();
        $total_data = array();
        $total = 0;
        $taxes = $this->cart->getTaxes();

        $this->load->model('setting/extension');
        $this->load->model('account/address');

        $results = $this->model_setting_extension->getExtensions('total');
        foreach ($results as $result) {
            if ($this->config->get($result['code'] . '_status')) {
                $this->load->model('total/' . $result['code']);
                $this->{'model_total_' . $result['code']}->getTotal($total_data, $total, $taxes);
            }
        }

        $product_data = array();

        foreach ($this->cart->getProducts() as $product) {
            $option_data = array();

            foreach ($product['option'] as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['option_value'];
                } else {
                    $value = $this->encryption->decrypt($option['option_value']);
                }

                $option_data[] = array(
                    'product_option_id'       => $option['product_option_id'],
                    'product_option_value_id' => $option['product_option_value_id'],
                    'option_id'               => $option['option_id'],
                    'option_value_id'         => $option['option_value_id'],
                    'name'                    => $option['name'],
                    'value'                   => $value,
                    'type'                    => $option['type']
                );
            }

            $product_data[] = array(
                'product_id' => $product['product_id'],
                'name'       => $product['name'],
                'model'      => $product['model'],
                'option'     => $option_data,
                'download'   => $product['download'],
                'quantity'   => $product['quantity'],
                'subtract'   => $product['subtract'],
                'price'      => $product['price'],
                'total'      => $product['total'],
                'tax'        => $this->tax->getTax($product['price'], $product['tax_class_id']),
                'reward'     => $product['reward']
            );
        }

        // Gift Voucher
        $voucher_data = array();

        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $voucher) {
                $voucher_data[] = array(
                    'description'      => $voucher['description'],
                    'code'             => substr(md5(mt_rand()), 0, 10),
                    'to_name'          => $voucher['to_name'],
                    'to_email'         => $voucher['to_email'],
                    'from_name'        => $voucher['from_name'],
                    'from_email'       => $voucher['from_email'],
                    'voucher_theme_id' => $voucher['voucher_theme_id'],
                    'message'          => $voucher['message'],
                    'amount'           => $voucher['amount']
                );
            }
        }

        $voucher_data = array();

        if (!empty($this->session->data['vouchers'])) {
            foreach ($this->session->data['vouchers'] as $voucher) {
                $voucher_data[] = array(
                    'description' => $voucher['description'],
                    'amount'      => $this->currency->format($voucher['amount'])
                );
            }
        }

        $allData['products'] = $product_data;
        $data['vouchers'] = $voucher_data;

        $allData['totals'] = $total_data;

        $allData['invoice_prefix'] = $this->config->get('config_invoice_prefix');

        $allData['store_id'] = $this->config->get('config_store_id');
        $allData['store_name'] = $this->config->get('config_name');
        if ($allData['store_id']) {
            $allData['store_url'] = $this->config->get('config_url');
        } else {
            $allData['store_url'] = HTTP_SERVER;
        }

        if (isset($this->session->data['customer_id'])) {
            $allData['customer_id'] = $this->session->data['customer_id'];
        }

        if ($this->customer->isLogged()) {
            $allData['customer_id'] = $this->customer->getId();
            $allData['customer_group_id'] = $this->customer->getCustomerGroupId();
            $allData['firstname'] = $this->customer->getFirstName();
            $allData['lastname'] = $this->customer->getLastName();
            $allData['email'] = $this->customer->getEmail();
            $allData['telephone'] = $this->customer->getTelephone();
            $allData['fax'] = $this->customer->getFax();

            $this->load->model('account/address');

            $payment_address = $this->model_account_address->getAddress($this->session->data['payment_address_id']);
        } elseif (isset($this->session->data['guest'])) {
            $allData['customer_id'] = 0;
            $allData['customer_group_id'] = $this->session->data['guest']['customer_group_id'];
            $allData['firstname'] = $this->session->data['guest']['firstname'];
            $allData['lastname'] = $this->session->data['guest']['lastname'];
            $allData['email'] = $this->session->data['guest']['email'];
            $allData['telephone'] = $this->session->data['guest']['telephone'];
            $allData['fax'] = $this->session->data['guest']['fax'];

            $payment_address = $this->session->data['guest']['payment'];
        }

        $allData['payment_firstname'] = $payment_address['firstname'];
        $allData['payment_lastname'] = $payment_address['lastname'];
        $allData['payment_company'] = $payment_address['company'];
        $allData['payment_company_id'] = $payment_address['company_id'];
        $allData['payment_tax_id'] = $payment_address['tax_id'];
        $allData['payment_address_1'] = $payment_address['address_1'];
        $allData['payment_address_2'] = $payment_address['address_2'];
        $allData['payment_city'] = $payment_address['city'];
        $allData['payment_postcode'] = $payment_address['postcode'];
        $allData['payment_zone'] = $payment_address['zone'];
        $allData['payment_zone_id'] = $payment_address['zone_id'];
        $allData['payment_country'] = $payment_address['country'];
        $allData['payment_country_id'] = $payment_address['country_id'];
        $allData['payment_address_format'] = $payment_address['address_format'];

        if ($this->cart->hasShipping()) {
            if ($this->customer->isLogged()) {
                $this->load->model('account/address');

                $shipping_address = $this->model_account_address->getAddress($this->session->data['shipping_address_id']);
            } elseif (isset($this->session->data['guest'])) {
                $shipping_address = $this->session->data['guest']['shipping'];
            }

            $allData['shipping_firstname'] = $shipping_address['firstname'];
            $allData['shipping_lastname'] = $shipping_address['lastname'];
            $allData['shipping_company'] = $shipping_address['company'];
            $allData['shipping_address_1'] = $shipping_address['address_1'];
            $allData['shipping_address_2'] = $shipping_address['address_2'];
            $allData['shipping_city'] = $shipping_address['city'];
            $allData['shipping_postcode'] = $shipping_address['postcode'];
            $allData['shipping_zone'] = $shipping_address['zone'];
            $allData['shipping_zone_id'] = $shipping_address['zone_id'];
            $allData['shipping_country'] = $shipping_address['country'];
            $allData['shipping_country_id'] = $shipping_address['country_id'];
            $allData['shipping_address_format'] = $shipping_address['address_format'];

            if (isset($this->session->data['shipping_method']['title'])) {
                $allData['shipping_method'] = $this->session->data['shipping_method']['title'];
            } else {
                $allData['shipping_method'] = '';
            }

            if (isset($this->session->data['shipping_method']['code'])) {
                $allData['shipping_code'] = $this->session->data['shipping_method']['code'];
            } else {
                $allData['shipping_code'] = '';
            }
        } else {
            $allData['shipping_firstname'] = '';
            $allData['shipping_lastname'] = '';
            $allData['shipping_company'] = '';
            $allData['shipping_address_1'] = '';
            $allData['shipping_address_2'] = '';
            $allData['shipping_city'] = '';
            $allData['shipping_postcode'] = '';
            $allData['shipping_zone'] = '';
            $allData['shipping_zone_id'] = '';
            $allData['shipping_country'] = '';
            $allData['shipping_country_id'] = '';
            $allData['shipping_address_format'] = '';
            $allData['shipping_method'] = '';
            $allData['shipping_code'] = '';
        }

        $allData['payment_method'] = "payu";
        $allData['payment_code'] = "payu";
        $allData['comment'] = 'Express Checkout by PayU';

        if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
            $allData['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
        } elseif(!empty($this->request->server['HTTP_CLIENT_IP'])) {
            $allData['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
        } else {
            $allData['forwarded_ip'] = '';
        }

        if (isset($this->request->server['HTTP_USER_AGENT'])) {
            $allData['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
        } else {
            $allData['user_agent'] = '';
        }

        if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
            $allData['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
        } else {
            $allData['accept_language'] = '';
        }

        $totalreward = 0;
        $totalcost = 0;
        foreach ($allData['products'] as $product) {
            $totalreward += $product['reward'] * $product['quantity'];
            $totalcost += $product['price'] * $product['quantity'];
        }
        $allData['total'] = $totalcost;
        $allData['reward'] = $totalreward;

        $allData['affiliate_id'] = '';
        $allData['commission'] = '';

        $this->load->model('localisation/language');
        $language_data = $this->model_localisation_language->getlanguages();
        foreach ($language_data as $lang) {
            if ($lang['code'] == $this->session->data['language']) {
                $allData['language_id'] = $lang['language_id'];
                break;
            }
        }

        $this->load->model('localisation/currency');
        $currency_data = $this->model_localisation_currency->getCurrencyByCode($this->session->data['currency']);

        $allData['currency_id'] = $currency_data['currency_id'];
        $allData['currency_code'] = $currency_data['code'];
        $allData['currency_value'] = $currency_data['value'];
        $allData['ip'] = $_SERVER['REMOTE_ADDR'];

        $allData['vouchers'] = array();

        if (!empty($this->session->data['vouchers'])) {
            $allData['vouchers'] = $this->session->data['vouchers'];
        }

        $this->load->model('checkout/order');
        $order_id = $this->model_checkout_order->addOrder($allData);

        return $order_id;
    }
}