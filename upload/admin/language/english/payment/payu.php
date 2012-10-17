<?php
// Heading
$_['heading_title']     = 'PayU account';

// Text
$_['text_module']       = 'Modules';
$_['text_payu']       = '<a onclick="window.open(\'http://www.payu.pl/\');"><img src="view/image/payment/payu.png" alt="PayU account" title="PayU account" style="border: 1px solid #EEEEEE;" /></a>';
$_['text_success']      = 'Success: You have modified the PayU payment extension!';
$_['text_payment']		= 'Payment';

// Entry
$_['entry_merchantposid']		= 'Merchant POS ID:<br /><span class="help">OAuth protocol - client_id</span>';
$_['entry_posauthkey']			= 'POS Auth Key:<br /><span class="help">Point of sale authentication key</span>';
$_['entry_clientsecret']		= 'Key (MD5):<br /><span class="help">OAuth protocol - client_secret</span>';
$_['entry_clientid'] 			= 'Client ID:<br /><span class="help">OAuth protocol - client_id</span>';
$_['entry_signaturekey'] 		= 'Second key (MD5):<br /><span class="help">Symmetrical key for encrypting communication - secret key</span>';
$_['entry_test']				= 'Sandbox:<br /><span class="help">Sandbox mode</span>';
$_['entry_status']				= 'Status:';
$_['entry_sort_order']			= 'Sort Order:';
$_['entry_failed_status']		= 'Failed Status:<br /><span class="help">&nbsp;</span>';
$_['entry_complete_status']	    = 'Completed Status:<br /><span class="help">&nbsp;</span>';
$_['entry_cancelled_status']	= 'Cancelled Status:<br /><span class="help">&nbsp;</span>';
$_['entry_pending_status']		= 'Pending Status:<br /><span class="help">&nbsp;</span>';
$_['entry_reject_status'] 		= 'Rejected Status:<br /><span class="help">&nbsp;</span>';
$_['entry_sent_status'] 		= 'Sent Status:<br /><span class="help">&nbsp;</span>';
$_['entry_returned_status']	    = 'Returned Status:<br /><span class="help">&nbsp;</span>';
$_['entry_new_status']			= 'New Status:<br /><span class="help">&nbsp;</span>';
$_['entry_button']				= 'Small logo:<br /><span class="help">visible as payment accepting button</span>';
// Error
$_['error_permission']			= 'Warning: You do not have permission to modify module PayU !';
$_['error_merchantposid']		= '* Merchant POS ID Required!';
$_['error_signaturekey']		= '* Signature Key Required!';
$_['error_clientsecret']		= '* Client Secret Required!';
$_['error_posauthkey']			= '* POS Auth Key Required!';
$_['error_clientid']			= '* Client ID Required!';
$_['error_sort_order']			= '* Sort Order Required!';