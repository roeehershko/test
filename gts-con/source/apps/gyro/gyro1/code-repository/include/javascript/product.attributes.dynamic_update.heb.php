<? // Script for attributes dynamic update ?>
<script type="text/javascript"> 
	
	<? if ($product['price_actual'] > 0): ?>
	var product_price = <?=$product['price_actual']?>;
	<? endif; ?>
	
	<? if ($product['price_retail'] > 0 ): ?>
	var product_retail_price = <?=$product['price_retail']?>;
	<? endif; ?>
	
	var product_attributes = new Array();
	<? for ($i = 0; $i != count($product['attributes']); $i++): ?>
		
		product_attributes['<?=str_replace(array('\'','"'),array('',''),$product['attributes'][$i]['attribute'])?>'] = new Array();
		product_attributes['<?=str_replace(array('\'','"'),array('',''),$product['attributes'][$i]['attribute'])?>']['price_actual'] = 0;
		
		<? for ($j = 0; $j != count($product['attributes'][$i]['options']); $j++): ?>
		    <? if ($product['attributes'][$i]['options'][$j]['price_delta']): ?>
                product_attributes['<?=str_replace(array('\'','"'),array('',''),$product['attributes'][$i]['attribute'])?>']['<?=str_replace(array('\'','"'),array('',''),$product['attributes'][$i]['options'][$j]['option'])?>'] = <?=$product['attributes'][$i]['options'][$j]['price_delta']?>;
            <? else: ?>
                product_attributes['<?=str_replace(array('\'','"'),array('',''),$product['attributes'][$i]['attribute'])?>']['<?=str_replace(array('\'','"'),array('',''),$product['attributes'][$i]['options'][$j]['option'])?>'] = 0;
            <? endif; ?>
		<? endfor; ?>
	<? endfor; ?>
	
	function updateProductPrice(attribute, option) {
		document.getElementById('product-price').innerHTML = (product_price + product_attributes[attribute][option]).toFixed(2) + ' ' + 'ש&quot;ח';
		<? if ($product['price_retail'] > 0 ): ?>
		document.getElementById('product-retail-price').innerHTML = (product_retail_price + product_attributes[attribute][option]).toFixed(2) + ' ' + 'ש&quot;ח';
		<? endif; ?>
	}
</script>
