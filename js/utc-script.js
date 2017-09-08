jQuery(document).ready(function($){
	// $("#publishing-action #publish").prop('disabled',true);
	var check = false;
	// checkTitle gone check whether the title is Unique or not. 
	function checkTitle(title, id,post_type, nonce) {
		var data = {
		    action: 'utc_check',
		    post_title: title,
		    post_type: post_type,
		    ajaxnonce: nonce,
		    post_id: id
		};

		$.post(ajaxurl, data, function(response) {
			if( undefined !== response && '' !== response ){
				var response = jQuery.parseJSON( response );
				if( undefined !== response.status  &&  'error' === response.status ){
					$('#message').remove();
					$('#poststuff').prepend('<div id=\"message\" class=\"error fade\"><p>'+response.message+'</p></div>');
					$("#publishing-action #publish").prop('disabled',true);
				}

				if( undefined !== response.status  &&  'updated' === response.status ){
					$('#message').remove();
					$('#poststuff').prepend('<div id=\"message\" class=\"updated fade\"><p>'+response.message+'</p></div>');
					$("#publishing-action #publish").prop('disabled',false);
					check = true;
				}
			}
		}); 
	}

	// On Post Title change we are going to call checkTitle which gone check title uniqueness for us.
	$('.wp-admin #title').keyup(function() {
		var title = $('#title').val();
		var id = $('#post_ID').val();
		var post_type = $('#post_type').val();
		var nonce = utc_nonce;
		checkTitle(title, id,post_type, nonce);
	});
});
