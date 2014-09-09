<?php
/*
* ver. 0.2.0
* PayU Payment Modules
*
* @copyright  Copyright 2014 by PayU
* @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
* http://www.payu.com
* http://twitter.com/openpayu
*/
class ControllerPaymentPayU extends Controller
{
    
    const ORDER_V2_NEW = 'NEW';
    const ORDER_V2_PENDING =  'PENDING';
    const ORDER_V2_CANCELED = 'CANCELED';
    const ORDER_V2_REJECTED = 'REJECTED';
    const ORDER_V2_COMPLETED = 'COMPLETED';
    const ORDER_V2_WAITING_FOR_CONFIRMATION = 'WAITING_FOR_CONFIRMATION';
    
    protected $vouchersAmount = 0.0;
    
    //loading PayU SDK
    protected function loadLibConfig()
    {
        require_once(DIR_SYSTEM . 'library/sdk_v21/openpayu.php');

        OpenPayU_Configuration::setMerchantPosId($this->config->get('payu_merchantposid'));
        OpenPayU_Configuration::setSignatureKey($this->config->get('payu_signaturekey'));
        OpenPayU_Configuration::setEnvironment('secure');
        OpenPayU_Configuration::setApiVersion ( 2.1 );
        OpenPayU_Configuration::setSender("OpenCart ver/Plugin ver 2.1.1" );

        $this->logger = new Log('payu.log');
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
        $this->logger->write(OpenPayU_Configuration::getServiceUrl());
//        $this->logger->write($order);

        $result = OpenPayU_Order::create($order);
        $this->logger->write($result);
        if ($result->getStatus () == 'SUCCESS') {
            $this->session->data['sessionId'] = $result->getResponse ()->orderId;
            
            $this->model_payment_payu->addOrder($this->session->data['order_id'], $this->session->data['sessionId']);
            $this->data['actionUrl'] = $result->getResponse ()->redirectUri;
            $this->data['sessionId'] = $this->session->data['sessionId'];
            $this->data['lang'] = strtolower($this->session->data['language']);
        } else {
            $this->data['error'] = true;
            $this->data['text_error'] = $this->language->get('text_error_message');
            $this->logger->write(
                $result->getError() . ' [request: ' . serialize($result) . ']'
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

        if (!$result->getStatus () == 'SUCCESS') {
            $this->logger->write(
                $result->getError() . ' [result: ' . serialize($result) . ']'
            );
        } else {
            $order_id = $this->model_payment_payu->getOrderIdBySessionId($this->session->data['sessionId']);
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
        
        $body = file_get_contents ( 'php://input' );
        $data = trim ( $body );
        
        $result = OpenPayU_Order::consumeNotification ( $data );
        $response = $result->getResponse();

        if ( isset ($response->order->orderId) ) {
            
            //$doc = htmlspecialchars_decode($this->request->post['DOCUMENT']);

            try {
                
                $session_id = $response->order->orderId;

                $order_id = $this->model_payment_payu->getOrderIdBySessionId($session_id);
                $retrieve = OpenPayU_Order::retrieve($session_id);
                $retrieve_response = $retrieve->getResponse();

                if (!($retrieve->getStatus() == 'SUCCESS')) {
                    $this->logger->write(
                        $retrieve->getError() . ' [response: ' . serialize($retrieve->getResponse()) . ']'
                    );
                } else {
                    
                    $this->updatecustomerdata($order_id, $retrieve_response->orders[0]->buyer);
                    
                    $orderStatus = $retrieve_response->orders[0]->status;
                    $paymentStatus = $retrieve_response->orders[0]->status;
                    
                    $newstatus = $this->getpaymentstatusid($paymentStatus, $orderStatus);
                    
                    $this->updatestatus($order_id, $newstatus);

                    header("HTTP/1.1 200 OK");
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
                case self::ORDER_V2_NEW:
                    return $this->config->get('payu_new_status');
                case "PAYMENT_STATUS_CANCEL" :
                    return $this->config->get('payu_cancelled_status');
                case self::ORDER_V2_CANCELED :
                    return $this->config->get('payu_cancelled_status');
                case "PAYMENT_STATUS_REJECT" :
                    return $this->config->get('payu_reject_status');
                case "PAYMENT_STATUS_INIT" :
                    return $this->config->get('payu_pending_status');
                case self::ORDER_V2_PENDING :
                    return $this->config->get('payu_pending_status');
                case self::ORDER_V2_WAITING_FOR_CONFIRMATION :
                    return $this->config->get('payu_pending_status');
                case "PAYMENT_STATUS_SENT" :
                    return $this->config->get('payu_sent_status');
                case "PAYMENT_STATUS_NOAUTH" :
                    return $this->config->get('payu_failed_status');
                case "PAYMENT_STATUS_REJECT_DONE" :
                    return $this->config->get('payu_returned_status');
                case self::ORDER_V2_REJECTED :
                    return $this->config->get('payu_returned_status');
                case "PAYMENT_STATUS_END" :
                    return $this->config->get('payu_complete_status');
                case self::ORDER_V2_COMPLETED :
                    return $this->config->get('payu_complete_status');
                case "PAYMENT_STATUS_ERROR" :
                    return $this->config->get('payu_failed_status');
                default:
                    return "GET_PAYMENT_STATUS_ERROR";
            }
        }
        switch ($orderStatus) {
            
            case self::ORDER_V2_NEW:
                return $this->config->get('payu_new_status');
            case self::ORDER_V2_CANCELED :
                return $this->config->get('payu_cancelled_status');
            case self::ORDER_V2_PENDING :
                return $this->config->get('payu_pending_status');
            case self::ORDER_V2_WAITING_FOR_CONFIRMATION :
                return $this->config->get('payu_pending_status');
            case self::ORDER_V2_REJECTED :
                return $this->config->get('payu_returned_status');
            case self::ORDER_V2_COMPLETED :
                return $this->config->get('payu_complete_status');
           
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
                return "GET_ORDER_STATUS_ERROR";
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
            if (!empty($customerdata->delivery)) {
                
                $delivery = $customerdata->delivery;
                
                $update_data['payment_iso_code_2'] = $delivery->countryCode;
                $update_data['payment_zone'] = $delivery->state;
                if ($delivery->recipientName) {
                    $firstlastcompany = explode(" ", $delivery->recipientName, 3);
                    $update_data['payment_firstname'] = $firstlastcompany[0];
                    $update_data['payment_lastname'] = $firstlastcompany[1];
                    $update_data['payment_company'] = ' ';
                }
                $addressstring = $delivery->street;
                $update_data['payment_address_1'] = substr($addressstring, 0, 128);
                $update_data['payment_address_2'] = substr($addressstring, 128);
                $update_data['payment_city'] = $delivery->city;
                $update_data['payment_postcode'] = $delivery->postalCode;
            }

            if (!empty($customerdata)) {
                $update_data['firstname'] = $customerdata->firstName;
                $update_data['lastname'] = $customerdata->lastName;
                $update_data['email'] = $customerdata->email;
                $update_data['telephone'] = $customerdata->phone;
            }

            if (isset($customerdata->delivery) && !empty($customerdata->delivery)) {
            	
            	$delivery = $customerdata->delivery;
            	
                //$update_data['shipping_method'] = $customerdata['Shipping']['ShippingType'];
/*                 $update_data['shipping_iso_code_2'] = $delivery->countryCode;
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
                        $chosenOne = $oneShipping;
                    }
                }

                $update_data['shipping_code'] = $chosenOne['code']; */

                if (isset($delivery) && !empty($delivery)) {
                    if (isset($delivery->state)) {
                        $update_data['shipping_zone'] = $delivery->state;
                    }

                    list($update_data['shipping_firstname'], $update_data['shipping_lastname']) = explode(
                        " ",
                        $delivery->recipientName,
                        2
                    );
                    $addressstring = $delivery->street . ' ' . $delivery->postalCode;
                    $update_data['shipping_address_1'] = substr($addressstring, 0, 128);
                    $update_data['shipping_address_2'] = substr($addressstring, 128);
                    if(!empty($delivery->city))
                    	$update_data['shipping_city'] = $delivery->city;
                    $update_data['shipping_postcode'] = $delivery->postalCode;
                    //$newTotal = $customerdata['Shipping']['ShippingCost']['Net'];
                }

                /* if (isset($update_data['products'])) {
                    foreach ($update_data['products'] as $oneProduct) {
                        $newTotal += $oneProduct['price'] * $oneProduct['quantity'];
                    }
                }
                $update_data['total'] = $newTotal; */
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

        if ($result->getStatus()=='SUCCESS') {

                $this->session->data['sessionId'] = $result->getResponse ()->orderId;

                $this->model_payment_payu->addOrder($this->session->data['order_id'], $this->session->data['sessionId']);
                $this->data['actionUrl'] = $result->getResponse ()->redirectUri;

            header('Location:'. $this->data['actionUrl']);

        } else {
            $this->logger->write($result->getError() . ' [' . serialize($result->getResponse()) . ']');
            $this->redirect($this->url->link('checkout/cart'));
        }
    }

    //building order for express checkout
    public function buildorder()
    {
        
        $OCRV2 = array();
        
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
        $shippingCostAmount = 0.0;

        $decimalPlace = $this->currency->getDecimalPlace();
        
        if (!empty($this->session->data['vouchers'])) {
        	foreach ($this->session->data['vouchers'] as $voucher) {
        		$this->vouchersAmount += $this->currency->format($voucher['amount']);
        	}
        }
        
        foreach ($this->cart->getProducts() as $item) {
            
        	if(empty($decimalPlace)) {
                $item['price'] *= 100;
            }
            
            $gross = $this->tax->calculate($item['price'], $item['tax_class_id']);
            
            if ($item['shipping'] == 1) {
                $orderType = 'MATERIAL';
            }
            
            $itemGross = str_ireplace(
                            array('.',' '),
                            array('',''),
                            $this->currency->format($gross, $order_info['currency_code'], false, false));
            
            $OCRV2['products'] [] = array (
                    'quantity' => $item['quantity'],'name' => $item['name'],'unitPrice' => $itemGross
            );
            
            $grandTotal += $itemGross * $item['quantity'];

        }
        


        $shoppingCart = array(
            'GrandTotal' => $grandTotal,
            'CurrencyCode' => $order_info['currency_code'],
            'ShoppingCartItems' => $cartItems
        );

        //$this->session->data['sessionId'] = md5(rand() . rand() . rand() . rand()) . $this->session->data['order_id'];


        $order = array(
            'MerchantPosId' => OpenPayU_Configuration::getMerchantPosId(),
            'SessionId' => '',
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
                'email' => $order_info['email'],
                'firstName' => $order_info['firstname'],
                'lastName' => $order_info['lastname'],
                'phone' => $order_info['telephone']
            );
            
        } elseif (!empty($this->session->data['customer_id'])) {
            
            $this->load->model('account\customer');
            $custdata = $this->model_account_customer->getCustomer($this->session['customer_id']);

            if (!empty($custdata['email'])) {
                
                $customer = array(
                        'email' => $order_info['email'],
                        'firstName' => $order_info['firstname'],
                        'lastName' => $order_info['lastname'],
                        'phone' => $order_info['telephone']
                );
                
            }
        }

        if ($orderType == 'MATERIAL') {
            if (!empty($customer) && !empty($order_info['shipping_city']) && !empty($order_info['shipping_postcode']) && !empty($order_info['payment_iso_code_2'])) {
                
                $customer['delivery'] = array(
                    'street' => $order_info['shipping_address_1'] . " " . ($order_info['shipping_address_2'] ?$order_info['shipping_address_2']: '')  ,
                    'postalCode' => $order_info['shipping_postcode'],
                    'city' => $order_info['shipping_city'],
                    //'State' => $order_info['shipping_zone'],
                    'countryCode' => $order_info['payment_iso_code_2'],
                    'recipientName' => $order_info['shipping_firstname'] . " " . $order_info['shipping_lastname'],
                    'recipientPhone' => $order_info['telephone'],
                    'recipientEmail' => $order_info['email'] );
                
            }
            
            if (!empty($order_info['shipping_method'])) {

                $shippingCostList = array();
                $shippingCost = $shippingCostAmount = $this->session->data['shipping_method']['cost'];
                
                if(empty($decimalPlace))
                {
                    $shippingCost *= 100;
                    $shippingCostAmount = $shippingCost;
                }
                
                $price = $this->currency->format(
                        $this->tax->calculate(
                                $shippingCost,
                                $this->session->data['shipping_method']['tax_class_id']
                        ));
                
                $price = preg_replace("/[^0-9]/", "", $price);
                
                $shippingCostList ['shippingMethods'] [] = array (
                        'name' => $order_info['shipping_method'],'country' => $order_info['payment_iso_code_2'],'price' => $price
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
                        $shippingCostAmount = $shippingCost;
                    }
                    
                    $price = $this->currency->format(
                            $this->tax->calculate($onemethod['cost'], $onemethod['tax_class_id']),
                            $order_info['currency_code'],
                            false,
                            false
                    );
                    
                    $price = preg_replace("/[^0-9]/", "", $price);
                    
                    $shippingCostList ['shippingMethods'] [] = array (
                            'name' => $onemethod['title'],'country' => $country['iso_code_2'],'price' => $price
                    );
                    
                }

            }

        }
        
        $OCRV2 ['merchantPosId'] = OpenPayU_Configuration::getMerchantPosId();
        $OCRV2 ['orderUrl'] = $this->url->link('payment/payu/callback') . '?order=' . $this->session->data['order_id'];
        $OCRV2 ['description'] = "Zamówienie #" . $this->session->data['order_id'];
        $OCRV2 ['customerIp'] = $OCReq['CustomerIp'];
        $OCRV2 ['notifyUrl'] = $OCReq['NotifyUrl'];
        $OCRV2 ['cancelUrl'] = $OCReq['OrderCancelUrl'];
        $OCRV2 ['continueUrl'] = $OCReq['OrderCompleteUrl'];
        $OCRV2 ['currencyCode'] = $order_info['currency_code'];
        
        $total = $order_info['total'];
        
        if(empty($decimalPlace)) {
                 $total *= 100;
        }
        
        $total = str_ireplace(
                            array('.',' '),
                            array('',''),
                            $this->currency->format($total - $shippingCostAmount, $order_info['currency_code'], false, false));
        
        $OCRV2 ['totalAmount'] = $total;
        
        $OCRV2 ['extOrderId'] = $this->session->data['order_id'];
        if(isset($shippingCostList))
        	$OCRV2 ['shippingMethods'] = $shippingCostList['shippingMethods'];
        $OCRV2 ['buyer'] = $customer;
        
        return $OCRV2;

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
                
                $this->vouchersAmount += $voucher['amount'];
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