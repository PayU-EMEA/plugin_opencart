<?php if ($testmode) { ?>
<div class="warning"><?php echo $text_testmode; ?></div>
<?php } ?>

<?php if ($error) { ?>
<div class="warning"><?php echo $text_error; ?></div>
<?php } ?>

<?php if (!$error) { ?>
<script type="text/javascript">
function goToPayUActionUrl(){
    window.location.replace(" <?php echo urldecode($actionUrl) ?>");
}
</script>
<form action="<?php echo urldecode($actionUrl) ?>" method="get" id="payment">
  <div class="buttons">
    <div class="right"><a onclick="goToPayUActionUrl();"><img src="<?php echo $payu_button; ?>" /></a></div>
  </div>
</form>
<?php } ?>