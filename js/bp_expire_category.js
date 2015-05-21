jQuery(document).ready(function(){    
	jQuery( '.datepicker' ).datepicker({
		showOn: 'both',
		buttonImage: bpCalImgURL.url,
		buttonImageOnly: true,
		buttonText: 'Select Date',
		dateFormat: 'yy-mm-dd'
	});
	var current_cat = jQuery('select#cat option:selected').val();
	
	jQuery('#bp_expire_cat_submit').on('click',function(){
		jQuery('#category-ajax-response').html('Saving....')
		var cat_id = jQuery('select#cat option:selected').val();
		var old_id = jQuery('#bp_expire_category_id').val();
		var date = jQuery('#bp_expiration_date').val();
		var post_id = jQuery('#bp_post_id').val();
		
		if(current_cat != cat_id){
			//we've changed cats, so need to remove from checklist as well
			jQuery('#in-category-'+current_cat).prop('checked',false);	
			//and check the new one
			jQuery('#in-category-'+cat_id).prop('checked',true);	
		}
		
		sendAjaxRequest(cat_id,old_id,date,post_id);
		
	})
});

function sendAjaxRequest(cat_id,old_id,date,post_id){
	var data = {
		'action': 'bp_expire_category_save',
		dataType: 'json',
		'term_id': cat_id,
		'old_id': old_id,
		'date': date,
		'post_id': post_id 
	};
	// We can also pass the url value separately from ajaxurl for front end AJAX implementations
	jQuery.post('admin-ajax.php', data, function(str) {
			//	console.log(str);
		str = jQuery.parseJSON(str);
		   if(str.result == "OK"){
				jQuery('#category-ajax-response').html('Category expiration has been scheduled').delay(5000).fadeOut(1000);
				jQuery('#bp_expire_category_id').val(str.id);
			}else{
				jQuery('#category-ajax-response').html('Category expiration date could not be saved.  Try again later. Error '+str.error).delay(8000).fadeOut(1000);
			}
	});
}
	