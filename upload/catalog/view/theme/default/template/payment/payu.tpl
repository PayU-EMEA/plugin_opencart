<?php if ($testmode) { ?>
<div class="warning"><?php echo $text_testmode; ?></div>
<?php } ?>


<form action="<?php echo $actionUrl ?>" method="get" id="payment">
	<input type="hidden" name="sessionId" value="<?php echo $sessionId;?>">
	<input type="hidden" name="oauth_token" value="<?php echo $accessToken;?>">
	<input type="hidden" name="lang" value="<?php echo $lang ?>">
  <div class="buttons">
    <div class="right"><a onclick="$('#payment').submit();"><img src="<?php echo $payu_button; ?>"></a></div>
  </div>
</form>
