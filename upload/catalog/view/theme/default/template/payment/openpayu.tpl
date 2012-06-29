<?php if ($testmode) { ?>
<div class="warning"><?php echo $text_testmode; ?></div>
<?php } ?>


<form action="<?php echo OpenPayU_Configuration::getAuthUrl(); ?>" method="get" id="payment">
	<input type="hidden" name="redirect_uri" value="<?php echo $beforesummary;?>">
	<input type="hidden" name="response_type" value="code">
	<input type="hidden" name="client_id" value="<?php echo OpenPayU_Configuration::getClientId(); ?>">
  <div class="buttons">
    <div class="right"><a onclick="$('#payment').submit();"><img src="<?php echo $openpayu_button; ?>"></a></div>
  </div>
</form>
