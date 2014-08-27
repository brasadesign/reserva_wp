jQuery(document).ready(function () {

    jQuery.fn.exists = function () {
        return this.length > 0;
    }

    if (jQuery('#rwp_action').val() == 'edit')
        jQuery('#rwp_edit_cancel').show();

    // Actions on the cancel edit button
    jQuery('#rwp_edit_cancel').on('click', function () {
        jQuery('.rwp_form .main input, .rwp_form textarea').val('');

        jQuery('#rwp_name').removeAttr('readonly');
        jQuery('#rwp_action').val('create');
        jQuery('#rwp_submit').val('Criar objeto');
        jQuery('#rwp_edit_cancel').hide();
    });

    // Actions on the edit object button
    jQuery('.rwp_edit_object').on('click', function () {

        idents = jQuery(this).attr('rel');
        inputs = jQuery('.rwp_form input, textarea');

        jQuery.each(inputs, function (i, v) {

            nom = jQuery(this).attr('name');
            val = jQuery('.rwp_object.' + idents).find('td.' + nom).html();

            if (nom == "rwp_name") {
                jQuery('#rwp_orig_name').val(val);
                jQuery('#rwp_name').attr('readonly', 'readonly');
            }


            if (typeof(val) != "undefined")
                jQuery(this).val(val);

        });

        jQuery('#rwp_action').val('edit');
        jQuery('#rwp_submit').val('Editar objeto');
        jQuery('#rwp_edit_cancel').show();

    });

    // Actions on the delete object button
    jQuery('.rwp_delete_thing').on('click', function () {

        idents = jQuery(this).attr('rel');
        thing = jQuery('form').find('#rwp_thing').val();

        if (confirm('Tem certeza que deseja excluir ' + idents + '?')) {

            jQuery.post(reserva_wp.ajaxurl, {
                    action: 'reserva_wp_edit_' + thing,
                    name: idents,
                    ajax: true
                },
                function (response) {

                    if (response == true) {
                        jQuery('tr.' + idents).detach();
                    }

                });

        }


    });

    // Actions on the delete object button
    jQuery('input[type="image"]').on('click', function () {

        idents = jQuery(this).attr('class');
        thing = jQuery(this).attr('rel');

        jQuery.post(reserva_wp.ajaxurl, {
                action: 'reserva_wp_set_plano',
                plano: idents,
                transacao: thing,
                ajax: true
            },
            function (response) {

                if (response.status == 'ok') {
                    // plano selecionado
                }

            });


    });


    if (jQuery("#bookingdatepicker").exists()) {

        if (jQuery("#bookingdatepicker").hasClass('front')) {
            front = true;
            disabled = true;
            numMonths = 6,
                button = false;
        } else {
            disabled = false;
            numMonths = 1,
                button = true;
        }

        aDates = [];
        var date;
        jQuery.each(addDates, function (i, v) {
            date = v.split('-');
            aDates.push(new Date(date[0], parseInt(date[1]) - 1, date[2]));
        });
        var dateToday = new Date();
        jQuery("#bookingdatepicker.front").multiDatesPicker({
            // resetDates: 'disabled',
            addDates: getDates(),
            numberOfMonths: 1,
            showButtonPanel: false,
            minDate: dateToday,
            maxDate: "+12M",
            // maxDate: "+12M",
            beforeShowDay: function (date) {
                console.log(ofertas);
                d = date.toString().substr(0, 15);
                classdate = d.replace(' ', '_');

                if (jQuery.inArray(d, indisponiveis) > -1) {
                    cls = 'ui-state-highlight date-' + classdate;
                    return [true, cls, false]
                }
                else if (jQuery.inArray(d, ofertas) > -1) {
                    console.log('teste');
                    cls = 'ui-state-highlight oferta date-' + classdate;
                   // jQuery(this).addClass('ui-state-highlight oferta');
                    //jQuery(this).addClass('oferta');
                    //jQuery(this).addClass('ui-state-highlight');
                    return true;
                }
                else{
                    cls = "ui-state-default";
                    return [true, cls, false]
                }
                //jQuery('#bookingdatepicker td').unbind('click');

                // console.log(cls);

            },
            onSelect: false
        });

        jQuery("#bookingdatepicker").not('.front').multiDatesPicker({
            // resetDates: 'disabled',
            //addDates: getDates(),
            numberOfMonths: 1,
            showButtonPanel: false,
            minDate: 0,
            maxDate: "+12M",
            beforeShowDay: function (date) {

                d = date.toString().substr(0, 15);
                cls = "ui-state-default";
                classdate = d.replace(' ', '_');

                if (jQuery.inArray(d, indisponiveis) > -1) {
                    cls = 'ui-state-highlight date-' + classdate;
                }
                if (jQuery.inArray(d, ofertas) > -1) {
                    cls = 'ui-state-highlight oferta date-' + classdate;
                }

                return [true, cls, 'tip']
            },
            onSelect: function (date, datepicker) {
                thisDate = date.split('/');
                mes = parseInt(thisDate[1]);
                if (mes < 10)
                    mes = 0 + mes.toString();
                thisDate = thisDate[2] + '-' + mes + '-' + thisDate[0];

                if (jQuery('#date-' + thisDate).exists()) {
                    return false;
                    // indDates.splice(index, 1);
                    // jQuery('#date-'+thisDate).remove();
                    // removeFromLists(thisDate);
                } else {
                    // indexInd = jQuery.inArray(thisDate, indDates);
                    // indexOft = jQuery.inArray(thisDate, oftDates);

                    addToLists('indisponiveis', thisDate);
                    jQuery('#datepicker-inputs').append('<label id="date-' + thisDate + '" for="date-type-' + thisDate + '">' + thisDate + ': <input type="radio" name="rwp_date_type[' + thisDate + ']" value="ind" checked/>Indisponível <input type="radio" name="rwp_date_type[' + thisDate + ']" value="oft" />Oferta <input type="button" value="x" rel="date-' + thisDate + '" /><br></label>')
                }
            }
        });

    }

    jQuery('.ui-state-highlight').on('click', function (e) {
        e.preventDefault();
        return false;
    });
    jQuery('.ui-state-default').on('click', function (e) {
        if (jQuery('#bookingdatepicker').attr('data-front') == 'true') {
            e.preventDefault();
            return false;
        }
    });


    jQuery('#datepicker-inputs').on('change', 'input[type="radio"]', function () {

        days = jQuery('.ui-datepicker-calendar td');
        date = jQuery(this).attr('name').slice(14, -1);
        val = jQuery(this).val();

        if ('oft' == val)
            addToLists('ofertas', date);

        if ('ind' == val)
            addToLists('indisponiveis', date);
    });

    jQuery('#datepicker-inputs').on('click', 'input[type="button"]', function () {

        name = jQuery(this).attr('rel');
        date = name.slice(5);
        jQuery('#' + name).remove();
        removeFromLists(date);

    });


    function addToLists(to, date) {

        if ('ofertas' == to) {
            from = indDates;
            to = oftDates;
            origList = indisponiveis;
            finalList = ofertas;
        }
        if ('indisponiveis' == to) {
            from = oftDates;
            to = indDates;
            origList = ofertas;
            finalList = indisponiveis;
        }

        if (typeof(date) == 'string')
            date = date.split('-');

        index = jQuery.inArray(date, from);
        if (index > -1)
            from.splice(index, 1);

        to.push(date[0] + '-' + date[1] + '-' + date[2]);

        for (i = 0; i < to.length; i++) {
            d = to[i].split('-');
            format = new Date(d[0], parseInt(d[1]) - 1, d[2]).toString().substr(0, 15);

            index = jQuery.inArray(format, origList);
            if (index > -1)
                origList.splice(index, 1);

            if (jQuery.inArray(format, finalList) == -1)
                finalList.push(format);
        }

        addDates = indDates.concat(oftDates);
        aDates = [];
        jQuery.each(addDates, function (i, v) {
            d = v.split('-');
            aDates.push(new Date(d[0], parseInt(d[1]) - 1, d[2]));
        });

        jQuery("#bookingdatepicker").datepicker('refresh');

        return false;
    }

    function removeFromLists(date) {
        // if(typeof(date) == 'string')
        // dateL = date.split('-');

        dateF = makeDateForList(date);

        indexOf = jQuery.inArray(dateF, ofertas);
        indexIn = jQuery.inArray(dateF, indisponiveis);
        indexD1 = jQuery.inArray(date, oftDates);
        indexD2 = jQuery.inArray(date, indDates);

        if (indexOf > -1)
            ofertas.splice(indexOf, 1);
        if (indexIn > -1)
            indisponiveis.splice(indexIn, 1);
        if (indexD1 > -1)
            oftDates.splice(indexD1, 1);
        if (indexD2 > -1)
            indDates.splice(indexD2, 1);

        addDates = indDates.concat(oftDates);
        aDates = [];
        jQuery.each(addDates, function (i, v) {
            d = v.split('-');
            aDates.push(new Date(d[0], parseInt(d[1]) - 1, d[2]));
        });

        jQuery("#bookingdatepicker").datepicker('refresh');

        return false;
    }

    function getDates() {
        return aDates;
    }

    function makeDateForList(date) {
        if (typeof(date) == 'string')
            date = date.split('-');

        return new Date(date[0], parseInt(date[1]) - 1, date[2]).toString().substr(0, 15);
    }

});