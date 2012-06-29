<?php
class ControllerPaymentOpenPayU extends Controller {
	private $error = array();
//Config page
	public function index() {
		$this->load->language('payment/openpayu');
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
		if (isset($this->request->post['openpayu_signaturekey'])) {
			$this->data['openpayu_signaturekey'] = $this->request->post['openpayu_signaturekey'];
		} else {
			$this->data['openpayu_signaturekey'] = $this->config->get('openpayu_signaturekey');
		}
		if (isset($this->request->post['openpayu_merchantposid'])) {
			$this->data['openpayu_merchantposid'] = $this->request->post['openpayu_merchantposid'];
		} else {
			$this->data['openpayu_merchantposid'] = $this->config->get('openpayu_merchantposid');
		}
		if (isset($this->request->post['openpayu_clientsecret'])) {
			$this->data['openpayu_clientsecret'] = $this->request->post['openpayu_clientsecret'];
		} else {
			$this->data['openpayu_clientsecret'] = $this->config->get('openpayu_clientsecret');
		}
		if (isset($this->request->post['openpayu_posauthkey'])) {
			$this->data['openpayu_posauthkey'] = $this->request->post['openpayu_posauthkey'];
		} else {
			$this->data['openpayu_posauthkey'] = $this->config->get('openpayu_posauthkey');
		}
		if (isset($this->request->post['openpayu_clientid'])) {
			$this->data['openpayu_clientid'] = $this->request->post['openpayu_clientid'];
		} else {
			$this->data['openpayu_clientid'] = $this->config->get('openpayu_clientid');
		}
		if (isset($this->request->post['openpayu_test'])) {
			$this->data['openpayu_test'] = $this->request->post['openpayu_test'];
		} else {
			$this->data['openpayu_test'] = $this->config->get('openpayu_test');
		}
		if (isset($this->request->post['openpayu_status'])) {
			$this->data['openpayu_status'] = $this->request->post['openpayu_status'];
		} else {
			$this->data['openpayu_status'] = $this->config->get('openpayu_status');
		}
	//Status
	//cancelled, complete, failed, new, pending, reject, returned, sent
		if (isset($this->request->post['openpayu_new_status'])) {
			$this->data['openpayu_new_status'] = $this->request->post['openpayu_new_status'];
		} else {
			$this->data['openpayu_new_status'] = $this->config->get('openpayu_new_status');
		}
		//2
		if (isset($this->request->post['openpayu_reject_status'])) {
			$this->data['openpayu_reject_status'] = $this->request->post['openpayu_reject_status'];
		} else {
			$this->data['openpayu_reject_status'] = $this->config->get('openpayu_reject_status');
		}
		//3
		if (isset($this->request->post['openpayu_sent_status'])) {
			$this->data['openpayu_sent_status'] = $this->request->post['openpayu_sent_status'];
		} else {
			$this->data['openpayu_sent_status'] = $this->config->get('openpayu_sent_status');
		}
		//4
		if (isset($this->request->post['openpayu_failed_status'])) {
			$this->data['openpayu_failed_status'] = $this->request->post['openpayu_failed_status'];
		} else {
			$this->data['openpayu_failed_status'] = $this->config->get('openpayu_failed_status');
		}
		//5
		if (isset($this->request->post['openpayu_returned_status'])) {
			$this->data['openpayu_returned_status'] = $this->request->post['openpayu_returned_status'];
		} else {
			$this->data['openpayu_returned_status'] = $this->config->get('openpayu_returned_status');
		}
		//6
		if (isset($this->request->post['openpayu_cancelled_status'])) {
			$this->data['openpayu_cancelled_status'] = $this->request->post['openpayu_cancelled_status'];
		} else {
			$this->data['openpayu_cancelled_status'] = $this->config->get('openpayu_cancelled_status');
		}
		//7
		if (isset($this->request->post['openpayu_pending_status'])) {
			$this->data['openpayu_pending_status'] = $this->request->post['openpayu_pending_status'];
		} else {
			$this->data['openpayu_pending_status'] = $this->config->get('openpayu_pending_status');
		}
		//8
		if (isset($this->request->post['openpayu_complete_status'])) {
			$this->data['openpayu_complete_status'] = $this->request->post['openpayu_complete_status'];
		} else {
			$this->data['openpayu_complete_status'] = $this->config->get('openpayu_complete_status');
		}
		
		if (isset($this->request->post['openpayu_sort_order'])) {
			$this->data['openpayu_sort_order'] = $this->request->post['openpayu_sort_order'];
		} else {
			$this->data['openpayu_sort_order'] = $this->config->get('openpayu_sort_order');
		}
		if (isset($this->request->post['openpayu_test'])) {
			$this->data['openpayu_test'] = $this->request->post['openpayu_test'];
		} else {
			$this->data['openpayu_test'] = $this->config->get('openpayu_test');
		}
		
		$getjson = $this->getjson();
		$jsondata = json_decode($getjson,true);
		$buttonlist = $jsondata['media']['buttons'];
		$this->data['button_list'] = $buttonlist;

		if (isset($this->request->post['openpayu_button'])) {
			$this->data['openpayu_button'] = $this->request->post['openpayu_button'];
		} else {
			$this->data['openpayu_button'] = $this->config->get('openpayu_button');
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
			'href'      => $this->url->link('payment/openpayu', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

//links
		$this->data['action'] = $this->url->link('payment/openpayu', 'token=' . $this->session->data['token'], 'SSL');		
		$this->data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		$this->load->model('localisation/order_status');
		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

//template
		$this->template = 'payment/openpayu.tpl';
		$this->children = array(
			'common/header',	
			'common/footer'	
		);

		$this->response->setOutput($this->render());
	}	//index	


	//validate
	private function validate() {
		//permisions
		if (!$this->user->hasPermission('modify', 'payment/openpayu')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		//check for errors
		if (!$this->request->post['openpayu_signaturekey']) {
			$this->error['signaturekey'] = $this->language->get('error_signaturekey');
		}
		if (!$this->request->post['openpayu_merchantposid']) {
			$this->error['merchantposid'] = $this->language->get('error_merchantposid');
		}
		if (!$this->request->post['openpayu_clientsecret']) {
			$this->error['clientsecret'] = $this->language->get('error_clientsecret');
		}
		if (!$this->request->post['openpayu_clientid']) {
			$this->error['clientid'] = $this->language->get('error_clientid');
		}
		if (!$this->request->post['openpayu_posauthkey']) {
			$this->error['posauthkey'] = $this->language->get('error_posauthkey');
		}
		if (!$this->request->post['openpayu_sort_order']) {
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
		//$this->load->model('payment/openpayu');
		//$this->model_payment_openpayu->createDatabaseTables();
	}
	public function uninstall() {
		//$this->load->model('payment/openpayu');
		//$this->model_payment_openpayu->dropDatabaseTables();
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


?>