<form action="<?php echo $AuthUrl; ?>" method="get" id="express_payment" lang="<?php echo $language; ?>">
	<input type="hidden" name="lang" value="<?php echo $language; ?>" >
	<input type="hidden" name="redirect_uri" value="<?php echo $beforesummary;?>">
	<input type="hidden" name="response_type" value="code">
	<input type="hidden" name="client_id" value="<?php echo $ClientId; ?>">
  </div>
</form>
<script type="text/javascript">document.getElementById("express_payment").submit();</script>
