$(function(){

$('#cart > .heading a').die('click');
$('#cart > .heading a').live('click', function() {
        $('#cart').addClass('active');
        
        $('#cart').load('index.php?route=module/cart #cart > *', function(){ create_payu_button(); });
        
        $('#cart').live('mouseleave', function() {
            $(this).removeClass('active');
        });
});

function create_payu_button(){
    $('a[href$="route=checkout/checkout"]').each(function(){
    	if($("html").attr("lang")=="pl"){
			if($(this).next().attr('rel') != 'payu_checkout')
        $(' <a class="button" rel="payu_checkout" href="?route=payment/openpayu/expresscheckout" style="color:white;padding:6px;margin-left:5px;">Płacę z PayU</a>').insertAfter($(this));
    	}else{
			if($(this).next().attr('rel') != 'payu_checkout')
        $(' <a class="button" rel="payu_checkout" href="?route=payment/openpayu/expresscheckout" style="color:white;padding:6px;margin-left:5px;">Pay with PayU</a>').insertAfter($(this));
    	}
  	});
};

create_payu_button();

});
