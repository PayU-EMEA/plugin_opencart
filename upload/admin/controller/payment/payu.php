<?php
/*
* ver. 0.1.7
* PayU Payment Modules
*
* @copyright  Copyright 2012 by PayU
* @license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)
* http://www.payu.com
* http://twitter.com/openpayu
*/
class ControllerPaymentPayU extends Controller {
	private $error = array();
//Config page
	public function index() {
		$this->load->language('payment/payu');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('setting/setting');

//new config
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('openpayu', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

//language data
		$this->data['heading_title'] = $this->language->get('heading_title');

		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		$this->data['text_all_zones'] = $this->language->get('text_all_zones');
		$this->data['text_yes'] = $this->language->get('text_yes');
		$this->data['text_no'] = $this->language->get('text_no');

		$this->data['entry_currency'] = $this->language->get('entry_currency');
		$this->data['entry_merchantposid'] = $this->language->get('entry_merchantposid');
		$this->data['entry_test'] = $this->language->get('entry_test'); 
		$this->data['entry_signaturekey'] = $this->language->get('entry_signaturekey'); 
		$this->data['entry_clientsecret'] = $this->language->get('entry_clientsecret');
		$this->data['entry_posauthkey'] = $this->language->get('entry_posauthkey');
		$this->data['entry_clientid'] = $this->language->get('entry_clientid');
		$this->data['entry_status'] = $this->language->get('entry_status');
		$this->data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$this->data['entry_failed_status'] = $this->language->get('entry_failed_status');
		$this->data['entry_complete_status'] = $this->language->get('entry_complete_status');
		$this->data['entry_pending_status'] = $this->language->get('entry_pending_status');
		$this->data['entry_cancelled_status'] = $this->language->get('entry_cancelled_status');
		$this->data['entry_reject_status'] = $this->language->get('entry_reject_status');
		$this->data['entry_sent_status'] = $this->language->get('entry_sent_status');
		$this->data['entry_returned_status'] = $this->language->get('entry_returned_status');
		$this->data['entry_new_status'] = $this->language->get('entry_new_status');
		$this->data['entry_button'] = $this->language->get('entry_button');
		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');

//error data
		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}
		if (isset($this->error['signaturekey'])) {
			$this->data['error_signaturekey'] = $this->error['signaturekey'];
		} else {
			$this->data['error_signaturekey'] = '';
		}
		if (isset($this->error['merchantposid'])) {
			$this->data['error_merchantposid'] = $this->error['merchantposid'];
		} else {
			$this->data['error_merchantposid'] = '';
		}
		if (isset($this->error['clientsecret'])) {
			$this->data['error_clientsecret'] = $this->error['clientsecret'];
		} else {
			$this->data['error_clientsecret'] = '';
		}
		if (isset($this->error['clientid'])) {
			$this->data['error_clientid'] = $this->error['clientid'];
		} else {
			$this->data['error_clientid'] = '';
		}
		if (isset($this->error['posauthkey'])) {
			$this->data['error_posauthkey'] = $this->error['posauthkey'];
		} else {
			$this->data['error_posauthkey'] = '';
		}
		if (isset($this->error['sort_order'])) {
			$this->data['error_sort_order'] = $this->error['sort_order'];
		} else {
			$this->data['error_sort_order'] = '';
		}

//preloaded config
	//
		if (isset($this->request->post['payu_signaturekey'])) {
			$this->data['payu_signaturekey'] = $this->request->post['payu_signaturekey'];
		} else {
			$this->data['payu_signaturekey'] = $this->config->get('payu_signaturekey');
		}
		if (isset($this->request->post['payu_merchantposid'])) {
			$this->data['payu_merchantposid'] = $this->request->post['payu_merchantposid'];
		} else {
			$this->data['payu_merchantposid'] = $this->config->get('payu_merchantposid');
		}
		if (isset($this->request->post['payu_clientsecret'])) {
			$this->data['payu_clientsecret'] = $this->request->post['payu_clientsecret'];
		} else {
			$this->data['payu_clientsecret'] = $this->config->get('payu_clientsecret');
		}
		if (isset($this->request->post['payu_posauthkey'])) {
			$this->data['payu_posauthkey'] = $this->request->post['payu_posauthkey'];
		} else {
			$this->data['payu_posauthkey'] = $this->config->get('payu_posauthkey');
		}
		if (isset($this->request->post['payu_clientid'])) {
			$this->data['payu_clientid'] = $this->request->post['payu_clientid'];
		} else {
			$this->data['payu_clientid'] = $this->config->get('payu_clientid');
		}
		if (isset($this->request->post['payu_test'])) {
			$this->data['payu_test'] = $this->request->post['payu_test'];
		} else {
			$this->data['payu_test'] = $this->config->get('payu_test');
		}
		if (isset($this->request->post['payu_status'])) {
			$this->data['payu_status'] = $this->request->post['payu_status'];
		} else {
			$this->data['payu_status'] = $this->config->get('payu_status');
		}
	//Status
	//cancelled, complete, failed, new, pending, reject, returned, sent
		if (isset($this->request->post['payu_new_status'])) {
			$this->data['payu_new_status'] = $this->request->post['payu_new_status'];
		} else {
			$this->data['payu_new_status'] = $this->config->get('payu_new_status');
		}
		//2
		if (isset($this->request->post['payu_reject_status'])) {
			$this->data['payu_reject_status'] = $this->request->post['payu_reject_status'];
		} else {
			$this->data['payu_reject_status'] = $this->config->get('payu_reject_status');
		}
		//3
		if (isset($this->request->post['payu_sent_status'])) {
			$this->data['payu_sent_status'] = $this->request->post['payu_sent_status'];
		} else {
			$this->data['payu_sent_status'] = $this->config->get('payu_sent_status');
		}
		//4
		if (isset($this->request->post['payu_failed_status'])) {
			$this->data['payu_failed_status'] = $this->request->post['payu_failed_status'];
		} else {
			$this->data['payu_failed_status'] = $this->config->get('payu_failed_status');
		}
		//5
		if (isset($this->request->post['payu_returned_status'])) {
			$this->data['payu_returned_status'] = $this->request->post['payu_returned_status'];
		} else {
			$this->data['payu_returned_status'] = $this->config->get('payu_returned_status');
		}
		//6
		if (isset($this->request->post['payu_cancelled_status'])) {
			$this->data['payu_cancelled_status'] = $this->request->post['payu_cancelled_status'];
		} else {
			$this->data['payu_cancelled_status'] = $this->config->get('payu_cancelled_status');
		}
		//7
		if (isset($this->request->post['payu_pending_status'])) {
			$this->data['payu_pending_status'] = $this->request->post['payu_pending_status'];
		} else {
			$this->data['payu_pending_status'] = $this->config->get('payu_pending_status');
		}
		//8
		if (isset($this->request->post['payu_complete_status'])) {
			$this->data['payu_complete_status'] = $this->request->post['payu_complete_status'];
		} else {
			$this->data['payu_complete_status'] = $this->config->get('payu_complete_status');
		}
		
		if (isset($this->request->post['payu_sort_order'])) {
			$this->data['payu_sort_order'] = $this->request->post['payu_sort_order'];
		} else {
			$this->data['payu_sort_order'] = $this->config->get('payu_sort_order');
		}
		if (isset($this->request->post['payu_test'])) {
			$this->data['payu_test'] = $this->request->post['payu_test'];
		} else {
			$this->data['payu_test'] = $this->config->get('payu_test');
		}
		
		$getjson = $this->getjson();
		$jsondata = json_decode($getjson,true);
		$buttonlist = $jsondata['media']['buttons'];
		$this->data['button_list'] = $buttonlist;

		if (isset($this->request->post['payu_button'])) {
			$this->data['payu_button'] = $this->request->post['payu_button'];
		} else {
			$this->data['payu_button'] = $this->config->get('payu_button');
		}

		$this->data['breadcrumbs'] = array();
		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);
		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_payment'),
			'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);
		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('payment/payu', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

//links
		$this->data['action'] = $this->url->link('payment/payu', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		$this->load->model('localisation/order_status');
		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

//template
		$this->template = 'payment/payu.tpl';
		$this->children = array(
			'common/header',	
			'common/footer'	
		);

		$this->response->setOutput($this->render());
	}	//index	


	//validate
	private function validate() {
		//permisions
		if (!$this->user->hasPermission('modify', 'payment/payu')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		//check for errors
		if (!$this->request->post['payu_signaturekey']) {
			$this->error['signaturekey'] = $this->language->get('error_signaturekey');
		}
		if (!$this->request->post['payu_merchantposid']) {
			$this->error['merchantposid'] = $this->language->get('error_merchantposid');
		}
		if (!$this->request->post['payu_clientsecret']) {
			$this->error['clientsecret'] = $this->language->get('error_clientsecret');
		}
		if (!$this->request->post['payu_clientid']) {
			$this->error['clientid'] = $this->language->get('error_clientid');
		}
		if (!$this->request->post['payu_posauthkey']) {
			$this->error['posauthkey'] = $this->language->get('error_posauthkey');
		}
		if (!$this->request->post['payu_sort_order']) {
			$this->error['sort_order'] = $this->language->get('error_sort_order');
		}
		//if errors correct them
		if (!$this->error) {
			return true;
		} else {
			return false;
		}	

	}

	public function install() {
		$this->load->model('payment/payu');
		$this->model_payment_payu->createDatabaseTables();
	}
	public function uninstall() {
		$this->load->model('payment/payu');
		$this->model_payment_payu->dropDatabaseTables();
	}


	protected function getjson() {
		$dat = curl_init("http://openpayu.com/pl/goods/v1/json");
		//curl_setopt($dat, CURLOPT_POST, 1);
		curl_setopt($dat, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt($dat, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($dat, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($dat, CURLOPT_HEADER, false);
		curl_setopt($dat, CURLOPT_RETURNTRANSFER, 1);

		$resp = curl_exec($dat);
		return $resp;
	}
}