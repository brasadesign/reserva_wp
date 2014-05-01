jQuery(document).ready(function() {
	
	if( jQuery('#rwp_action').val() == 'edit' )
		jQuery('#rwp_edit_cancel').show();

	// Actions on the cancel edit button
	jQuery('#rwp_edit_cancel').on('click', function() {
		jQuery('.rwp_form .main input, .rwp_form textarea').val('');
		
		jQuery('#rwp_name').removeAttr('readonly');
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
				jQuery('#rwp_name').attr('readonly','readonly');
			}
				

			if(typeof(val) != "undefined")
				jQuery(this).val(val);

		});

		jQuery('#rwp_action').val('edit');
		jQuery('#rwp_submit').val('Editar objeto');
		jQuery('#rwp_edit_cancel').show();

	});

	// Actions on the delete object button
	jQuery('.rwp_delete_thing').on('click', function() {

		idents = jQuery(this).attr('rel');
		thing = jQuery('form').find('#rwp_thing').val();
		
		if(confirm('Tem certeza que deseja excluir '+idents+'?')) {

			jQuery.post(reserva_wp.ajaxurl, {
				action: 'reserva_wp_edit_'+thing,
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

	var allDates = [];
      jQuery( "#bookingdatepicker" ).multiDatesPicker({
          numberOfMonths: 1,
          showButtonPanel: true,
          minDate: 0, 
          maxDate: "+6M",
          
          onSelect: function(date,datepicker) {
              thisDate = date.split('/');
              thisDate = thisDate[2]+'-'+thisDate[0]+'-'+thisDate[1];
              
              index = jQuery.inArray(thisDate, allDates);
              // check if selecting or deselecting
              if(index != -1) {
                  allDates.splice(index, 1);
                      console.log(jQuery('#date-'+thisDate));
                     jQuery('#date-'+thisDate).remove();
                 } else {
                  allDates.push(thisDate);
                     jQuery('#datepicker-inputs').append('<label id="date-'+thisDate+'" for="date-type-'+thisDate+'">'+thisDate+': <input type="radio" name="date-type-'+thisDate+'" value="ind" checked/>Indisponível <input type="radio" name="date-type-'+thisDate+'" value="oft" />Oferta <br></label>')
              }
              
              jQuery('#dates').val(allDates.join(", "));
              // console.log(allDates);
          }
      });

	  jQuery('#datepicker-inputs input').on('change', function() {
	  		console.log('change');
	  });

});