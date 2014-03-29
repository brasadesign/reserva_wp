jQuery(document).ready(function() {
	
	if( jQuery('#rwp_action').val() == 'edit' )
		jQuery('#rwp_edit_cancel').show();

	// Actions on the cancel edit button
	jQuery('#rwp_edit_cancel').on('click', function() {
		jQuery('.rwp_form .main input, .rwp_form textarea').val('');
		
		jQuery('#rwp_name').removeAttr('disabled');
		jQuery('#rwp_action').val('create');
		jQuery('#rwp_submit').val('Criar objeto');
		jQuery('#rwp_edit_cancel').hide();				
	});

	// Actions on the edit object button
	jQuery('.rwp_edit_object').on('click', function() {

		idents = jQuery(this).attr('rel');
		inputs = jQuery('.rwp_form input, textarea');				

		jQuery.each(inputs, function(i,v) {

			nom = jQuery(this).attr('name');
			val = jQuery('.rwp_object.'+idents).find('td.'+nom).html();

			if(nom == "rwp_name") {
				jQuery('#rwp_orig_name').val(val);
				jQuery('#rwp_name').attr('disabled','disabled');
			}
				

			if(typeof(val) != "undefined")
				jQuery(this).val(val);

		});

		jQuery('#rwp_action').val('edit');
		jQuery('#rwp_submit').val('Editar objeto');
		jQuery('#rwp_edit_cancel').show();

	});

	// Actions on the delete object button
	jQuery('.rwp_delete_object').on('click', function() {

		idents = jQuery(this).attr('rel');
		
		if(confirm('Tem certeza que deseja excluir o objeto '+idents+'?')) {

			jQuery.post(reserva_wp.ajaxurl, {
				action: 'reserva_wp_edit_object',
				name: idents,
				ajax: true
			},
			function(response) {
				
				if(response == true) {
					jQuery('tr.'+idents).detach();
				}
					
			});

		}

		

	});

});