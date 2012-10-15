<?php  
class ControllerModuleExpressPayU extends Controller {
	protected function index($setting) {
		static $module = 0;

		$this->document->addScript('catalog/view/javascript/payu.js');
	
		$this->data['link'] = $this->url->link('payment/payu/expresscheckout');
		$this->data['image'] = $this->config->get('payu_button');
		$this->data['module'] = $module++;

	//change the language of a button depending on client's language
			if (!($this->session->data['language']=='pl')){
			$this->data['image'] = str_replace('/pl/','/en/',$this->data["image"]);
		}

		if (file_exists('catalog/view/theme/' . $this->config->get('config_template') . '/stylesheet/express_payu.css')) {
			$this->document->addStyle('catalog/view/theme/' . $this->config->get('config_template') . '/stylesheet/express_payu.css');
		} else {
			$this->document->addStyle('catalog/view/theme/default/stylesheet/express_payu.css');
		}

		$this->data['class'] = "payu_".$setting['position'];
		if (!$setting['display']) {
			$this->data['class'] = "payu_none";
		}

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/express_payu.tpl')) {
			$this->template = $this->config->get('config_template') . '/template/module/express_payu.tpl';
		} else {
			$this->template = 'default/template/module/express_payu.tpl';
		}

		$this->render();
	}
}
?>