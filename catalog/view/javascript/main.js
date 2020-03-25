function isEmptyObj(obj) {
	for (let k in obj) {
		return false;
	}
	return true;
}

$(document).ready(function(){
	var to = {
		cart: null,
	}
	
	$('.products-style-btn').click(function(){
		let type = $(this).attr('data-type'),
			products = $(this).parents('.products');
		$.post(
			'?route=product/products_list',
			{ type: type },
			function(json) {
				let data = $.parseJSON(json);
				if (data.type && data.types_list) {
					for (let i in data.types_list) {
						products.removeClass('_'+data.types_list[i]);
					}
					products.addClass('_'+data.type);
				}
			}
		)
		
	});
	
	$('.product-thumb').click(function(){
		let $this = $(this);
			src = $this.attr('href');
		$this.parent().find('.product-thumb').removeClass('_active');
		$this.addClass('_active');
		$('.product-image').attr('href', src)
			.find('img').attr('src', src);
		return false;
	});
	$('.product-image').click(function(){ return false; });
	
	
	$('button[name=cart]').click(function(){
		let data = {
			product_id: $(this).parents('[data-id]').attr('data-id'),
			quantity: 1
		}
		$.ajax({
			url: 'index.php?route=checkout/cart/add',
			type: 'post',
			data: data,
			dataType: 'json',
			beforeSend: function() {
				//$('#button-cart').button('loading');
			},
			complete: function() {
				//$('#button-cart').button('reset');
			},
			success: function(json) {
				$('.alert-dismissible, .text-danger').remove();
				$('.form-group').removeClass('has-error');

				if (json['error']) {
					if (json['error']['option']) {
						for (i in json['error']['option']) {
							message(json['error']['option'][i], 0);
							/*
							let element = $('#input-option' + i.replace('_', '-'));

							if (element.parent().hasClass('input-group')) {
								element.parent().after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
							} else {
								element.after('<div class="text-danger">' + json['error']['option'][i] + '</div>');
							}*/
						}
					}

					if (json['error']['recurring']) {
						message(json['error']['recurring'], 0);
						//$('select[name=\'recurring_id\']').after('<div class="text-danger">' + json['error']['recurring'] + '</div>');
					}

					// Highlight any found errors
					//$('.text-danger').parent().addClass('has-error');
				}

				if (json['success']) {
					message(json['success'], 3);
					//$('.breadcrumb').after('<div class="alert alert-success alert-dismissible">' + json['success'] + '<button type="button" class="close" data-dismiss="alert">&times;</button></div>');

					// Need to set timeout otherwise it wont update the total
					setTimeout(function () {
						let total = json['total'].substr(8, 3) * 1,
							$count = $('#cart .head-cart-count');
						$count.text(total);
						if (total) $count.fadeIn(100);
						else $count.fadeOut(100);
					}, 100);

					// $('html, body').animate({ scrollTop: 0 }, 'slow');

					$('#cart > .head-cart-dropdown').load('index.php?route=common/cart/info .head-cart-dropdown > div');
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
		return false;
	});
	
	
	
	
	
	
	
	/* Popup hide */
	$('body').click(function() {
		to.cart = setTimeout(function(){
			$('.head-cart-dropdown').fadeOut(100);
			$('#cart .btn-cart').removeClass('_active');
		}, 100);
	});
	$('#cart').click(function() { setTimeout(function(){clearTimeout(to.cart);}, 50); });
	
	
	/* FIX Price */
	function fixPrice(element) {
		let price = $(element).text(),
			textPrice = '';
		if (price.substr(price.length-2, 2)!='р.') return false;
		price = price.substr(0, price.length-2);
		for (let i=1; i<=((price.length-(price.length%3))/3); i++) {
			textPrice = ' ' + price.substr(price.length - i*3, 3) + textPrice;
		}
		textPrice = price.substr(0, price.length%3) + textPrice;
		textPrice += ' <span class="fraction"><s>Р</s></span>';
		$(element).html(textPrice);
	}
	$('.price').each(function(){ fixPrice(this) });
	
	
	$('.alert').each(function(){
		let $this = $(this),
			text = $this.text();
			type = '_info';
		if ($this.hasClass('alert-success')) type='_success';
		if ($this.hasClass('alert-error')) type='_error';
		message(text, type, 10000);
	});
});




//  Messages 
function message(text, type, delay) {
	type = type==null ? 3 : type;
	delay = !delay ? 5000 : delay;
	
	let style = '';
	switch(type) {
		case 0: style='_error'; break;
		case 1: style='_warning'; break;
		case 2: style='_success'; break;
		default: style='_info';
	}
	
	let messages = $('#messages');
	if (!messages.length) {
		messages = $('<div id="messages"></div>');
		messages.css({
					});
		$('body').append(messages);
	}
	
	let message = $('<div><p>'+text+'</p></div>')
			.addClass('message')
			.addClass(style)
			.appendTo(messages)
			.hide()
			.slideDown(100);
	setTimeout(function() { message.slideUp(100); }, delay);
	setTimeout(function() { message.remove(); }, delay+100);
}



// Cart add remove functions
var cart = {
	'add': function(product_id, quantity) {
		$.ajax({
			url: 'index.php?route=checkout/cart/add',
			type: 'post',
			data: 'product_id=' + product_id + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1),
			dataType: 'json',
			beforeSend: function() {
				//$('#cart > button').button('loading');
			},
			complete: function() {
				//$('#cart > button').button('reset');
			},
			success: function(json) {
				$('.alert-dismissible, .text-danger').remove();

				if (json['redirect']) {
					location = json['redirect'];
				}

				if (json['success']) {
					//$('#content').parent().before('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');

					// Need to set timeout otherwise it wont update the total
					setTimeout(function () {
						let total = json['total'].substr(8, 3) * 1,
							$count = $('#cart .head-cart-count');
						$count.text(total);
						if (total) $count.fadeIn(100);
						else $count.fadeOut(100);
					}, 100);

					//// $('html, body').animate({ scrollTop: 0 }, 'slow');

					$('#cart > .head-cart-dropdown').load('index.php?route=common/cart/info .head-cart-dropdown > div');
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	},
	'update': function(key, quantity) {
		$.ajax({
			url: 'index.php?route=checkout/cart/edit',
			type: 'post',
			data: 'key=' + key + '&quantity=' + (typeof(quantity) != 'undefined' ? quantity : 1),
			dataType: 'json',
			beforeSend: function() {
				//$('#cart > button').button('loading');
			},
			complete: function() {
				//$('#cart > button').button('reset');
			},
			success: function(json) {
console.log(json);
				// Need to set timeout otherwise it wont update the total
				setTimeout(function () {
					let total = json['total'].substr(8, 3) * 1,
						$count = $('#cart .head-cart-count');
					$count.text(total);
					if (total) $count.fadeIn(100);
					else $count.fadeOut(100);
				}, 100);

				if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
					location = 'index.php?route=checkout/cart';
				} else {
					$('#cart > .head-cart-dropdown').load('index.php?route=common/cart/info .head-cart-dropdown .head-cart-item');
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	},
	'remove': function(key) {
		$.ajax({
			url: 'index.php?route=checkout/cart/remove',
			type: 'post',
			data: 'key=' + key,
			dataType: 'json',
			beforeSend: function() {
				//$('#cart > button').button('loading');
			},
			complete: function() {
				//$('#cart > button').button('reset');
			},
			success: function(json) {
				message(json['success'], 3, 1000);
				// Need to set timeout otherwise it wont update the total
				setTimeout(function () {
					let total = json['total'].substr(8, 3) * 1,
						$count = $('#cart .head-cart-count');
					$count.text(total);
					if (total) $count.fadeIn(100);
					else $count.fadeOut(100);
				}, 100);

				if (false && (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout')) {
					location = 'index.php?route=checkout/cart';
				} else {
					$('#cart > .head-cart-dropdown').load('index.php?route=common/cart/info .head-cart-dropdown > div');
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	}
}

var voucher = {
	'add': function() {

	},
	'remove': function(key) {
		$.ajax({
			url: 'index.php?route=checkout/cart/remove',
			type: 'post',
			data: 'key=' + key,
			dataType: 'json',
			beforeSend: function() {
				$('#cart > button').button('loading');
			},
			complete: function() {
				$('#cart > button').button('reset');
			},
			success: function(json) {
				// Need to set timeout otherwise it wont update the total
				setTimeout(function () {
					$('#cart > button').html('<span id="cart-total"><i class="fa fa-shopping-cart"></i> ' + json['total'] + '</span>');
				}, 100);

				if (getURLVar('route') == 'checkout/cart' || getURLVar('route') == 'checkout/checkout') {
					location = 'index.php?route=checkout/cart';
				} else {
					$('#cart > ul').load('index.php?route=common/cart/info ul li');
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	}
}

var wishlist = {
	'add': function(product_id) {
		$.ajax({
			url: 'index.php?route=account/wishlist/add',
			type: 'post',
			data: 'product_id=' + product_id,
			dataType: 'json',
			success: function(json) {
				$('.alert-dismissible').remove();

				if (json['redirect']) {
					location = json['redirect'];
				}

				if (json['success']) {
					$('#content').parent().before('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');
				}

				$('#wishlist-total span').html(json['total']);
				$('#wishlist-total').attr('title', json['total']);

				// $('html, body').animate({ scrollTop: 0 }, 'slow');
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	},
	'remove': function() {

	}
}

var compare = {
	'add': function(product_id) {
		$.ajax({
			url: 'index.php?route=product/compare/add',
			type: 'post',
			data: 'product_id=' + product_id,
			dataType: 'json',
			success: function(json) {
				$('.alert-dismissible').remove();

				if (json['success']) {
					$('#content').parent().before('<div class="alert alert-success alert-dismissible"><i class="fa fa-check-circle"></i> ' + json['success'] + ' <button type="button" class="close" data-dismiss="alert">&times;</button></div>');

					$('#compare-total').html(json['total']);

					// $('html, body').animate({ scrollTop: 0 }, 'slow');
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			}
		});
	},
	'remove': function() {

	}
}