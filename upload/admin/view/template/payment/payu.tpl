<?php echo $header; ?>
<div id="content">
<!-- Szybka nawigacja -->
<div class="breadcrumb">
    <?php foreach ($breadcrumbs as $breadcrumb) { ?>
    <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
    <?php } ?>
</div>
<!-- Komunikaty błędów -->
<?php if ($error_warning) { ?>
<div class="warning"><?php echo $error_warning; ?></div>
<?php } ?>
<div class="box">
<!-- Nagłówek z przyciskami -->
<div class="heading">
    <h1><img src="view/image/payment.png" alt="" /> <?php echo $heading_title; ?></h1>
    <div class="buttons"><a onclick="$('#form').submit();" class="button"><span><?php echo $button_save; ?></span></a><a onclick="location = '<?php echo $cancel; ?>';" class="button"><span><?php echo $button_cancel; ?></span></a>
    </div>
</div>
<div class="content">
<!-- konfigurację musimy podać jako dane POST -->
<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
<!-- Wypełniamy formularz -->
<table class="form">
<!-- merchant POS ID -->
<tr>
    <td><span class="required">*</span> <?php echo $entry_merchantposid; ?></td>
    <td><input type="text" name="payu_merchantposid" value="<?php echo $payu_merchantposid; ?>" size="40" />
        <?php if ($error_merchantposid) { ?>
        <span class="error"><?php echo $error_merchantposid; ?></span>
        <?php } ?>
    </td>
</tr>
<!-- POS Auth Key -->
<tr>
    <td><span class="required">*</span> <?php echo $entry_posauthkey; ?></td>
    <td><input type="text" name="payu_posauthkey" value="<?php echo $payu_posauthkey; ?>" size="40" />
        <?php if ($error_posauthkey) { ?>
        <span class="error"><?php echo $error_posauthkey; ?></span>
        <?php } ?>
    </td>
</tr>
<!-- client ID -->
<tr>
    <td><span class="required">*</span> <?php echo $entry_clientid; ?></td>
    <td><input type="text" name="payu_clientid" value="<?php echo $payu_clientid; ?>" size="40" />
        <?php if ($error_clientid) { ?>
        <span class="error"><?php echo $error_clientid; ?></span>
        <?php } ?>
    </td>
</tr>
<!-- signature key -->
<tr>
    <td><span class="required">*</span> <?php echo $entry_clientsecret; ?></td>
    <td><input type="text" name="payu_clientsecret" value="<?php echo $payu_clientsecret; ?>" size="40" />
        <?php if ($error_clientsecret) { ?>
        <span class="error"><?php echo $error_clientsecret; ?></span>
        <?php } ?>
    </td>
</tr>
<!-- client secret -->
<tr>
    <td><span class="required">*</span> <?php echo $entry_signaturekey; ?></td>
    <td><input type="text" name="payu_signaturekey" value="<?php echo $payu_signaturekey; ?>" size="40" />
        <?php if ($error_signaturekey) { ?>
        <span class="error"><?php echo $error_signaturekey; ?></span>
        <?php } ?>
    </td>
</tr>

<!-- Sandbox -->
<tr>
    <td><?php echo $entry_test; ?></td>
    <td><?php if ($payu_test) { ?>
        <input type="radio" name="payu_test" value="1"  checked="checked"/>
        <?php echo $text_yes; ?>
        <input type="radio" name="payu_test" value="0" />
        <?php echo $text_no; ?>
        <?php } else { ?>
        <input type="radio" name="payu_test" value="1" />
        <?php echo $text_yes; ?>
        <input type="radio" name="payu_test" value="0" checked="checked"/>
        <?php echo $text_no; ?>
        <?php } ?>
    </td>
</tr>
<tr>
    <td><?php echo $entry_status; ?></td>
    <td><select name="payu_status">
        <?php if ($payu_status) { ?>
        <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
        <option value="0"><?php echo $text_disabled; ?></option>
        <?php } else { ?>
        <option value="1"><?php echo $text_enabled; ?></option>
        <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
        <?php } ?>
    </select></td>
</tr>
<!--Bloki wiązań statusów -->

<tr>
    <td><?php echo $entry_cancelled_status; ?></td>
    <td><select name="payu_cancelled_status">
        <?php foreach ($order_statuses as $order_status) { ?>
        <?php if ($order_status['order_status_id'] == $payu_cancelled_status) { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
        <?php } else { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
        <?php } ?>
        <?php } ?>
    </select></td>
</tr>
<tr>
    <td><?php echo $entry_complete_status; ?></td>
    <td><select name="payu_complete_status">
        <?php foreach ($order_statuses as $order_status) { ?>
        <?php if ($order_status['order_status_id'] == $payu_complete_status) { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
        <?php } else { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
        <?php } ?>
        <?php } ?>
    </select></td>
</tr>
<tr>
    <td><?php echo $entry_failed_status; ?></td>
    <td><select name="payu_failed_status">
        <?php foreach ($order_statuses as $order_status) { ?>
        <?php if ($order_status['order_status_id'] == $payu_failed_status) { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
        <?php } else { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
        <?php } ?>
        <?php } ?>
    </select></td>
</tr>
<tr>
    <td><?php echo $entry_new_status; ?></td>
    <td><select name="payu_new_status">
        <?php foreach ($order_statuses as $order_status) { ?>
        <?php if ($order_status['order_status_id'] == $payu_new_status) { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
        <?php } else { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
        <?php } ?>
        <?php } ?>
    </select></td>
</tr>

<tr>
    <td><?php echo $entry_pending_status; ?></td>
    <td><select name="payu_pending_status">
        <?php foreach ($order_statuses as $order_status) { ?>
        <?php if ($order_status['order_status_id'] == $payu_pending_status) { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
        <?php } else { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
        <?php } ?>
        <?php } ?>
    </select></td>
</tr>
<tr>
    <td><?php echo $entry_reject_status; ?></td>
    <td><select name="payu_reject_status">
        <?php foreach ($order_statuses as $order_status) { ?>
        <?php if ($order_status['order_status_id'] == $payu_reject_status) { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
        <?php } else { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
        <?php } ?>
        <?php } ?>
    </select></td>
</tr>
<tr>
    <td><?php echo $entry_returned_status; ?></td>
    <td><select name="payu_returned_status">
        <?php foreach ($order_statuses as $order_status) { ?>
        <?php if ($order_status['order_status_id'] == $payu_returned_status) { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
        <?php } else { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
        <?php } ?>
        <?php } ?>
    </select></td>
</tr>
<tr>
    <td><?php echo $entry_sent_status; ?></td>
    <td><select name="payu_sent_status">
        <?php foreach ($order_statuses as $order_status) { ?>
        <?php if ($order_status['order_status_id'] == $payu_sent_status) { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
        <?php } else { ?>
        <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
        <?php } ?>
        <?php } ?>
    </select></td>
</tr>
<!-- sort order -->
<tr>
    <td><?php echo $entry_sort_order; ?></td>
    <td><input type="text" name="payu_sort_order" value="<?php echo $payu_sort_order; ?>" size="5" />
        <?php if ($error_sort_order) { ?>
        <span class="error"><?php echo $error_sort_order; ?></span>
        <?php } ?>
    </td>
</tr>
<!-- To jedziem z koksem (tzn. batonami)-->
<tr>
    <td><?php echo $entry_button; ?></td>
    <td><table class="form">
        <?php foreach ($button_list as $oneButton){ ?>
        <tr><td>
            <?php if ($payu_button==$oneButton) { ?>
            <input type="radio" name="payu_button" value="<?php echo $oneButton ?>"  checked="checked"/>
            <?php } else { ?>
            <input type="radio" name="payu_button" value="<?php echo $oneButton ?>" />
            <?php }?>
        </td>
            <td>
                <img src="<?php echo $oneButton ?>">
            </td>
            <td>
                <img src="<?php echo str_replace('/pl/','/en/',$oneButton) ?>">
            </td>
        </tr>
        <?php } ?>
    </table>
    </td>
</tr>
</table>
</form>
</div>
</div>


<?php echo $footer; ?>