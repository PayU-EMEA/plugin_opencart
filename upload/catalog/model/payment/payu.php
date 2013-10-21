<?php
/*
* ver. 0.1.6
* PayU Payment Modules
*
* @copyright  Copyright 2012 by PayU
* @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
* http://www.payu.com
* http://twitter.com/openpayu
*/
class ModelPaymentPayu extends Model
{
    public function getMethod($address, $total)
    {
        $this->load->language('payment/payu');
        $status = true;
        /*
          $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('payu_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

          if ($this->config->get('payu_total') > $total) {
              $status = false;
          } elseif (!$this->config->get('payu_geo_zone_id')) {
              $status = true;
          } elseif ($query->num_rows) {
              $status = true;
          } else {
              $status = false;
          }
          */

        $method_data = array();
        if ($status) {
            $method_data = array(
                'code' => 'payu',
                'title' => $this->language->get('text_title'),
                'sort_order' => $this->config->get('payu_sort_order')
            );
        }

        return $method_data;
    }

    public function customerupdate($order_id, $data)
    {
        $shippingcountry = $this->db->query("SELECT * FROM " . DB_PREFIX . "country WHERE iso_code_2 = '" . $this->db->escape($data['shipping_iso_code_2']) . "'");
        $data['shipping_country'] = $shippingcountry->row['name'];
        $data['shipping_iso_code_3'] = $shippingcountry->row['iso_code_3'];
        $data['shipping_country_id'] = $shippingcountry->row['country_id'];
        if (!empty($data['shipping_zone'])) {
            $shippingzone = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE name = '" . $data['shipping_zone'] . "'");
            $data['shipping_zone_id'] = $shippingzone->row['zone_id'];
            $data['shipping_zone_code'] = $shippingzone->row['code'];
            $data['shipping_zone'] = $shippingzone->row['name'];
        } else {
            $data['shipping_zone_id'] = 7777777777;
            $data['shipping_zone_code'] = 'zone was not provided by PAYU';
            $data['shipping_zone'] = 'zone was not provided by PAYU';
        }
        $this->db->query("UPDATE `" . DB_PREFIX . "order` SET firstname = '" . $this->db->escape($data['firstname']) . "', lastname = '" . $this->db->escape($data['lastname']) . "', email = '" . $this->db->escape($data['email']) . "', telephone = '" . $this->db->escape($data['telephone']) . "', fax = '" . $this->db->escape($data['fax']) . "', shipping_firstname = '" . $this->db->escape($data['shipping_firstname']) . "', shipping_lastname = '" . $this->db->escape($data['shipping_lastname']) . "',  shipping_company = '" . $this->db->escape($data['shipping_company']) . "', shipping_address_1 = '" . $this->db->escape($data['shipping_address_1']) . "', shipping_address_2 = '" . $this->db->escape($data['shipping_address_2']) . "', shipping_city = '" . $this->db->escape($data['shipping_city']) . "', shipping_postcode = '" . $this->db->escape($data['shipping_postcode']) . "', shipping_country = '" . $this->db->escape($data['shipping_country']) . "', shipping_country_id = '" . (int)$data['shipping_country_id'] . "', shipping_code = '" . (int)$data['shipping_code'] . "', shipping_zone = '" . $this->db->escape($data['shipping_zone']) . "', shipping_zone_id = '" . (int)$data['shipping_zone_id'] . "', shipping_address_format = '" . $this->db->escape($data['shipping_address_format']) . "', shipping_method = '" . $this->db->escape($data['shipping_method']) . "', payment_firstname = '" . $this->db->escape($data['payment_firstname']) . "', payment_lastname = '" . $this->db->escape($data['payment_lastname']) . "', payment_company = '" . $this->db->escape($data['payment_company']) . "', payment_address_1 = '" . $this->db->escape($data['payment_address_1']) . "', payment_address_2 = '" . $this->db->escape($data['payment_address_2']) . "', payment_city = '" . $this->db->escape($data['payment_city']) . "', payment_postcode = '" . $this->db->escape($data['payment_postcode']) . "', payment_country = '" . $this->db->escape($data['payment_country']) . "', payment_country_id = '" . (int)$data['payment_country_id'] . "', payment_zone = '" . $this->db->escape($data['payment_zone']) . "', payment_zone_id = '" . (int)$data['payment_zone_id'] . "', payment_address_format = '" . $this->db->escape($data['payment_address_format']) . "', payment_method = '" . $this->db->escape($data['payment_method']) . "', comment = '" . $this->db->escape($data['comment']) . "', order_status_id = '" . (int)$data['order_status_id'] . "', affiliate_id  = '" . (int)$data['affiliate_id'] . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");
    }
}