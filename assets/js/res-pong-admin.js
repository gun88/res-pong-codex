(function($){
    function renderCheckbox(data){
        return '<input type="checkbox" class="rp-select" value="' + data.id + '">';
    }
    function renderBool(val){
        return parseInt(val) === 1
            ? '<span class="dashicons dashicons-yes rp-icon-yes"></span>'
            : '<span class="dashicons dashicons-no-alt rp-icon-no"></span>';
    }
    function showOverlay(indeterminate){
        var overlay = $('#rp-progress-overlay');
        var bar = overlay.find('progress');
        var text = $('#rp-progress-text');
        if(indeterminate){
            bar.removeAttr('max').removeAttr('value');
            text.hide();
        }else{
            bar.attr({max:100, value:0});
            text.show().text('0%');
        }
        overlay.show();
        return {bar: bar, text: text};
    }
    function hideOverlay(){
        $('#rp-progress-overlay').hide();
    }
    function clearNotice(){
        $('.wrap').first().find('.notice').remove();
    }
    function showNotice(type, text){
        var wrap = $('.wrap').first();
        clearNotice();
        var notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + text + '</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Ignora questa notifica.</span></button></div>');
        notice.prependTo(wrap);
    }
    $(document).on('click', '.notice-dismiss', function(){
        $(this).closest('.notice').remove();
    });
    function showButtonMessage(btn, type, text){
        btn.siblings('.notice').remove();
        var notice = $('<div class="notice notice-' + type + '"><p>' + text + '</p></div>');
        btn.after(notice);
    }
    function rpConfirm(message){
        return new Promise(function(resolve){
            $('<div>').text(message).dialog({
                modal: true,
                title: 'Conferma',
                buttons: {
                    'Sì': function(){ $(this).dialog('close'); resolve(true); },
                    'No': function(){ $(this).dialog('close'); resolve(false); }
                },
                close: function(){ $(this).remove(); }
            });
        });
    }
    function restUrl(path, params){
        var url = rp_admin.rest_url + path;
        if(params){
            url += (url.indexOf('?') === -1 ? '?' : '&') + params;
        }
        return url;
    }
    function actionButtons(entity, data){
        var edit = '<button class="button rp-edit rp-action-btn" data-id="' + data.id + '" title="Modifica"><span class="dashicons dashicons-edit"></span></button>';
        var del = '<button class="button rp-delete rp-button-danger rp-action-btn" data-id="' + data.id + '" title="Cancella"><span class="dashicons dashicons-trash"></span></button>';
        var toggleLabel, state, toggleClass, toggleIcon;
        if(entity === 'reservations'){
            state = parseInt(data.presence_confirmed);
            toggleLabel = state ? 'Assente' : 'Presente';
        }else{
            state = parseInt(data.enabled);
            toggleLabel = state ? 'Disabilita' : 'Abilita';
        }
        toggleClass = state ? 'rp-button-disable' : 'rp-button-enable';
        toggleIcon = state ? 'dashicons-no-alt' : 'dashicons-yes';
        var toggle = '<button class="button rp-toggle rp-action-btn ' + toggleClass + '" data-id="' + data.id + '" title="' + toggleLabel + '"><span class="dashicons ' + toggleIcon + '"></span></button>';
        var buttons = edit + del + toggle;
        if(entity === 'users'){
            buttons += '<button class="button rp-impersonate rp-action-btn" data-id="' + data.id + '" title="Impersona"><span class="dashicons dashicons-admin-users"></span></button>';
            if(!data.password){
                buttons += '<button class="button rp-invite rp-action-btn" data-id="' + data.id + '" title="Invita"><span class="dashicons dashicons-email"></span></button>';
            }
        }else if(entity === 'events'){
            buttons += '<button class="button rp-contact rp-action-btn" data-id="' + data.id + '" title="Contatta"><span class="dashicons dashicons-email"></span></button>';
        }
        return '<div class="rp-action-group">' + buttons + '</div>';
    }
    var columns = {
        users: [
            { data: null, title: '', orderable: false, render: renderCheckbox },
            { data: 'name', title: 'Nome', render: function(d, type, row){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-user-detail&id=' + row.id + '">' + d + '</a>'; } },
            { data: 'id', title: 'Tessera', render: function(d){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-user-detail&id=' + d + '">' + d + '</a>'; } },
            { data: 'email', title: 'Email' },
            { data: 'username', title: 'Nome utente' },
            { data: 'category', title: 'Categoria' },
            { data: 'enabled', title: 'Abilitato', className: 'rp-icon-col', render: function(d, type){ return type === 'display' ? renderBool(d) : d; } },
            { data: 'timeout', title: 'Timeout', render: function(d, type){ if(type === 'display'){ if(!d){ return ''; } var now = new Date(); var t = new Date(d.replace(' ', 'T')); return now < t ? d : ''; } return d; } },
            { data: 'timeout', title: 'In timeout', className: 'rp-icon-col', render: function(d, type){ if(!d){ return type === 'display' ? '' : 0; } var now = new Date(); var t = new Date(d.replace(' ', 'T')); var active = now < t; if(type === 'display'){ return active ? '<span class="dashicons dashicons-clock rp-icon-clock"></span>' : ''; } return active ? 1 : 0; } },
            { data: null, title: 'Azioni', className: 'rp-action-group-col', orderable: false, render: function(d){ return actionButtons('users', d); } }
        ],
        events: [
            { data: null, title: '', orderable: false, render: renderCheckbox },
            { data: 'name', title: 'Nome', render: function(d, type, row){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-event-detail&id=' + row.id + '">' + d + '</a>'; } },
            { data: 'id', title: 'ID', render: function(d){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-event-detail&id=' + d + '">' + d + '</a>'; } },
            { data: 'group_id', title: 'Gruppo', render: function(d, type, row){
                if(!d){ return ''; }
                if(type === 'display'){
                    return '<a href="' + rp_admin.admin_url + '?page=res-pong-event-detail&id=' + d + '">' + row.group_name + ' (' + d + ')</a>';
                }
                return d;
            } },
            { data: 'start_datetime', title: 'Inizio' },
            { data: 'end_datetime', title: 'Fine' },
            { data: 'category', title: 'Categoria' },
            { data: 'enabled', title: 'Abilitato', className: 'rp-icon-col', render: function(d, type){ return type === 'display' ? renderBool(d) : d; } },
            { data: null, title: 'Stato', render: function(d){ var now = new Date(); var start = new Date(d.start_datetime.replace(' ', 'T')); return now > start ? 'chiuso' : 'aperto'; } },
            { data: 'players_count', title: 'Giocatori' },
            { data: 'max_players', title: 'Giocatori max' },
            { data: null, title: 'Azioni', className: 'rp-action-group-col', orderable: false, render: function(d){ return actionButtons('events', d); } }
        ],
        reservations: [
            { data: null, title: '', orderable: false, render: renderCheckbox },
            { data: 'id', title: 'ID', render: function(d){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-reservation-detail&id=' + d + '">' + d + '</a>'; } },
            { data: 'user_id', title: 'ID utente', render: function(d){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-user-detail&id=' + d + '">' + d + '</a>'; } },
            { data: 'username', title: 'Nome utente' },
            { data: 'event_id', title: 'ID evento', render: function(d){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-event-detail&id=' + d + '">' + d + '</a>'; } },
            { data: 'event_name', title: 'Evento' },
            { data: 'created_at', title: 'Creato il' },
            { data: 'presence_confirmed', title: 'Presenza', className: 'rp-icon-col', render: function(d, type){ return type === 'display' ? renderBool(d) : d; } },
            { data: null, title: 'Azioni', className: 'rp-action-group-col', orderable: false, render: function(d){ return actionButtons('reservations', d); } }
        ]
    };
    function handleActions(table, entity){
        table.on('click', '.rp-edit', function(){
            var id = $(this).data('id');
            window.location = rp_admin.admin_url + '?page=res-pong-' + entity.slice(0,-1) + '-detail&id=' + id;
        });
        table.on('click', '.rp-delete', function(){
            var id = $(this).data('id');
            var row = table.DataTable().row($(this).closest('tr')).data();
            var url = rp_admin.rest_url + entity + '/' + id;
            var proceed = function(){
                if(!confirm('Eliminare l\'elemento?')){ return; }
                clearNotice();
                showOverlay(true);
                $.ajax({
                    url: url,
                    method: 'DELETE',
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                    complete: function(){ hideOverlay(); },
                    success: function(){ table.DataTable().ajax.reload(); },
                    error: function(xhr){
                        var msg = 'Errore durante l\'eliminazione';
                        if(xhr.responseJSON && xhr.responseJSON.message){ msg += ': ' + xhr.responseJSON.message; }
                        showNotice('error', msg);
                    }
                });
            };
            if(entity === 'events' && row.group_id){
                rpConfirm('Applicare la modifica a tutta la serie di eventi?').then(function(apply){
                    if(apply){ url += '&apply_group=1'; }
                    proceed();
                });
            } else {
                proceed();
            }
        });
        table.on('click', '.rp-toggle', function(){
            var id = $(this).data('id');
            var row = table.DataTable().row($(this).closest('tr')).data();
            var data = {};
            if(entity === 'reservations'){
                data.presence_confirmed = row.presence_confirmed == 1 ? 0 : 1;
            }else{
                data.enabled = row.enabled == 1 ? 0 : 1;
            }
            var url = rp_admin.rest_url + entity + '/' + id;
            var send = function(){
                clearNotice();
                showOverlay(true);
                $.ajax({
                    url: url,
                    method: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify(data),
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                    complete: function(){ hideOverlay(); },
                    success: function(){ table.DataTable().ajax.reload(); },
                    error: function(xhr){
                        var msg = 'Errore durante l\'aggiornamento';
                        if(xhr.responseJSON && xhr.responseJSON.message){ msg += ': ' + xhr.responseJSON.message; }
                        showNotice('error', msg);
                    }
                });
            };
            if(entity === 'events' && row.group_id){
                rpConfirm('Applicare la modifica a tutta la serie di eventi?').then(function(apply){
                    if(apply){ url += '&apply_group=1'; }
                    send();
                });
            } else {
                send();
            }
        });
        if(entity === 'users'){
            table.on('click', '.rp-invite', function(){
                var id = $(this).data('id');
                clearNotice();
                showOverlay(true);
                $.ajax({
                    url: rp_admin.rest_url + 'users/' + id + '/invite',
                    method: 'POST',
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                    complete: function(){ hideOverlay(); },
                    success: function(){ showNotice('success', 'Invito inviato'); },
                    error: function(xhr){
                        var msg = 'Invito fallito';
                        if(xhr.responseJSON && xhr.responseJSON.message){ msg += ': ' + xhr.responseJSON.message; }
                        showNotice('error', msg);
                    }
                });
            });
            table.on('click', '.rp-impersonate', function(){
                var id = $(this).data('id');
                clearNotice();
                showOverlay(true);
                $.ajax({
                    url: rp_admin.rest_url + 'users/' + id + '/impersonate',
                    method: 'POST',
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                    complete: function(){ hideOverlay(); },
                    success: function(resp){ if(resp && resp.url){ window.open(resp.url, '_blank'); } },
                    error: function(xhr){
                        var msg = 'Impersonazione fallita';
                        if(xhr.responseJSON && xhr.responseJSON.message){ msg += ': ' + xhr.responseJSON.message; }
                        showNotice('error', msg);
                    }
                });
            });
        }else if(entity === 'events'){
            table.on('click', '.rp-contact', function(){
                var id = $(this).data('id');
                window.location = rp_admin.admin_url + '?page=res-pong-email&event_id=' + id;
            });
        }
    }
    function initTable(table, entity, urlFunc, opts){
        opts = opts || {};
        var order = opts.order || [];
        if(!order.length){
            if(entity === 'users'){ order = [[1, 'asc']]; }
            else if(entity === 'events'){ order = [[2, 'desc']]; }
            else if(entity === 'reservations'){ order = [[6, 'desc']]; }
        }
        var showAll = false;
        var dt = table.DataTable({
            ajax: {
                url: urlFunc(showAll),
                dataSrc: '',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); }
            },
            columns: columns[entity],
            order: order
        });
        dt.on('preXhr.dt', function(){ showOverlay(true); });
        dt.on('xhr.dt error.dt', function(){ hideOverlay(); });

        $(dt.column(0).header()).html('<input type="checkbox" id="rp-select-all">');
        table.on('change', '#rp-select-all', function(){
            var checked = $(this).is(':checked');
            table.find('.rp-select').prop('checked', checked);
        });
        table.on('change', '.rp-select', function(){
            if(!this.checked){ $('#rp-select-all').prop('checked', false); }
        });

        handleActions(table, entity);

        var wrapper = $(dt.table().container());
        var toolbar = $('<div class="rp-toolbar"></div>');
        var length = wrapper.find('div.dataTables_length');
        var filter = wrapper.find('div.dataTables_filter');
        toolbar.append(length);
        var bulk = $('<div class="rp-bulk"><select id="rp-bulk-action"><option value="">Bulk Actions</option></select> <button class="button" id="rp-apply-bulk">Apply</button></div>');
        var opt = '';
        if(entity === 'users'){
            opt = '<option value="delete">Delete</option><option value="enable">Enable</option><option value="disable">Disable</option><option value="timeout">Timeout</option>';
        }else if(entity === 'events'){
            opt = '<option value="delete">Delete</option><option value="enable">Enable</option><option value="disable">Disable</option>';
        }else if(entity === 'reservations'){
            opt = '<option value="delete">Delete</option><option value="enable">Presente</option><option value="disable">Assente</option>';
        }
        bulk.find('select').append(opt);
        var separator0 = $('<span>•</span>');
        var separator1 = $('<span>•</span>');
        var separator2 = $('<span>•</span>');
        var addBtn = $('<button class="button rp-button-add" id="res-pong-add"><span class="dashicons dashicons-plus"></span><span>Aggiungi</span></button>');
        var importBtn = $('<button class="button" id="res-pong-import">Importa CSV</button>');
        var exportBtn = $('<button class="button" id="res-pong-export">Esporta CSV</button>');
        toolbar.append(separator0, bulk);
        toolbar.append(separator1, addBtn);
        if(!opts.noCsv){
            toolbar.append(separator2, importBtn, exportBtn);
        }
        var addFilter = opts.filterFuture || entity === 'events' || entity === 'reservations';
        if(addFilter){
            var separator3 = $('<span>•</span>');
            var futureFilter = $('<label>Filtra <select class="rp-future-filter"><option value="0">Solo Futuro</option><option value="1">Mostra tutto</option></select></label>');
            toolbar.append(separator3, futureFilter);
        }
        toolbar.append(filter);
        wrapper.prepend(toolbar);

        addBtn.on('click', function(e){
            e.preventDefault();
            var url = rp_admin.admin_url + '?page=res-pong-' + entity.slice(0,-1) + '-detail';
            if(opts.addParams){
                url += '&' + opts.addParams;
            }
            window.location = url;
        });
        exportBtn.on('click', function(e){
            e.preventDefault();
            window.location = restUrl(entity + '/export', '_wpnonce=' + rp_admin.nonce);
        });
        importBtn.on('click', function(e){
            e.preventDefault();
            var input = $('<input type="file" accept=".csv" style="display:none">');
            $('body').append(input);
            input.on('change', function(){
                var file = this.files[0];
                if(!file){ input.remove(); return; }
                var formData = new FormData();
                formData.append('file', file);
                clearNotice();
                showOverlay(true);
                $.ajax({
                    url: rp_admin.rest_url + entity + '/import',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                    complete: function(){ hideOverlay(); input.remove(); },
                    success: function(){ dt.ajax.reload(); showNotice('success', 'Import completed'); },
                    error: function(xhr){
                        var msg = 'Import failed';
                        if(xhr.responseJSON && xhr.responseJSON.message){ msg += ': ' + xhr.responseJSON.message; }
                        showNotice('error', msg);
                    }
                });
            }).trigger('click');
        });
        toolbar.find('#rp-apply-bulk').on('click', function(){
            var action = $('#rp-bulk-action').val();
            var ids = table.find('.rp-select:checked').map(function(){ return this.value; }).get();
            if(!action || ids.length === 0){ return; }
            if(action === 'delete' && !confirm('Delete selected items?')){ return; }
            var timeoutDate = null;
            if(action === 'timeout'){
                var def = new Date();
                def.setHours(0,0,0,0);
                def.setDate(def.getDate() + 7);
                var defStr = def.getFullYear() + '-' + ('0' + (def.getMonth()+1)).slice(-2) + '-' + ('0' + def.getDate()).slice(-2) + ' 00:00:00';
                timeoutDate = prompt('Timeout date (YYYY-MM-DD HH:MM:SS)', defStr);
                if(!timeoutDate){ return; }
            }
            clearNotice();
            var overlay = showOverlay(false);
            var bar = overlay.bar;
            var text = overlay.text;
            var i = 0;
            function next(){
                if(i >= ids.length){ hideOverlay(); dt.ajax.reload(); return; }
                var id = ids[i];
                var url = rp_admin.rest_url + entity + '/' + id;
                var method = action === 'delete' ? 'DELETE' : 'PUT';
                var data = null;
                if(action === 'enable'){ data = entity === 'reservations' ? { presence_confirmed:1 } : { enabled:1 }; }
                if(action === 'disable'){ data = entity === 'reservations' ? { presence_confirmed:0 } : { enabled:0 }; }
                if(action === 'timeout'){ data = { timeout: timeoutDate }; }
                $.ajax({
                    url: url,
                    method: method,
                    contentType: 'application/json',
                    data: data ? JSON.stringify(data) : null,
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                    complete: function(){
                        i++;
                        var perc = Math.round(i / ids.length * 100);
                        bar.val(perc); text.text(perc + '%');
                        next();
                    }
                });
            }
            next();
        });
        if(addFilter){
            toolbar.find('.rp-future-filter').on('change', function(){
                showAll = $(this).val() === '1';
                dt.ajax.url(urlFunc(showAll)).load();
            });
        }
        return dt;
    }
    function populateForm(entity, id, form){
        $.ajax({
            url: rp_admin.rest_url + entity + '/' + id,
            method: 'GET',
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
            success: function(data){
                for(var key in data){
                    var field = form.find('[name='+key+']');
                    if(!field.length){ continue; }
                    if(field.attr('type') === 'checkbox'){
                        field.prop('checked', parseInt(data[key]) === 1);
                    } else if(field.attr('type') === 'datetime-local'){
                        field.val(data[key].replace(' ', 'T'));
                    } else {
                        field.val(data[key]);
                    }
                }
                if(typeof data.password !== 'undefined'){
                    var hasPassword = !!data.password;
                    $('#rp-invite-wrapper').toggle(!hasPassword);
                    $('#rp-reset-wrapper').toggle(hasPassword);
                }
            }
        });
    }
    function initDetail(){
        var form = $('#res-pong-detail-form');
        if(!form.length){ return; }
        var entity = form.data('entity');
        var id = form.data('id');
        var params = new URLSearchParams(window.location.search);
        var preUser = params.get('user_id');
        var preEvent = params.get('event_id');
        function loadReservationOptions(callback){
            var uReq = $.ajax({
                url: rp_admin.rest_url + 'users',
                method: 'GET',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); }
            });
            var eReq = $.ajax({
                url: rp_admin.rest_url + 'events&open_only=0',
                method: 'GET',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); }
            });
            $.when(uReq, eReq).done(function(uRes, eRes){
                var users = uRes[0];
                var events = eRes[0];
                var uSel = $('#user_id');
                $.each(users, function(_, u){
                    uSel.append('<option value="' + u.id + '">' + u.last_name + ' ' + u.first_name + ' (' + u.id + ')</option>');
                });
                var eSel = $('#event_id');
                $.each(events, function(_, e){
                    var dt = e.start_datetime.substring(0,16);
                    eSel.append('<option value="' + e.id + '">' + e.name + ' - ' + dt + ' (' + e.id + ')</option>');
                });
                if(!id){
                    if(preUser){ uSel.val(preUser); }
                    if(preEvent){ eSel.val(preEvent); }
                }
                if(callback){ callback(); }
            });
        }
        function loadEventGroupOptions(callback){
            $.ajax({
                url: rp_admin.rest_url + 'events&open_only=0',
                method: 'GET',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                success: function(events){
                    var sel = $('#group_id');
                    sel.append('<option value="">Nessuno</option>');
                    $.each(events, function(_, e){
                        sel.append('<option value="' + e.id + '">' + e.name + ' (' + e.id + ')</option>');
                    });
                    if(callback){ callback(); }
                }
            });
        }
        function setupRecurrence(){
            function toggle(){
                if($('#group_id').val()){
                    $('#recurrence_row, #recurrence_end_row').hide();
                }else{
                    $('#recurrence_row, #recurrence_end_row').show();
                    $('#recurrence_end').prop('disabled', $('#recurrence').val() === 'none');
                }
            }
            $('#group_id').on('change', toggle);
            $('#recurrence').on('change', function(){
                $('#recurrence_end').prop('disabled', $('#recurrence').val() === 'none');
            });
            if(id){
                $('#recurrence_row, #recurrence_end_row').hide();
            }else{
                toggle();
            }
        }
        function initForm(){ if(id){ populateForm(entity, id, form); } }
        if(entity === 'reservations'){
            loadReservationOptions(initForm);
        }else if(entity === 'events'){
            loadEventGroupOptions(function(){ initForm(); setupRecurrence(); });
        }else{
            initForm();
        }
        form.on('submit', function(e){
            e.preventDefault();
            var data = {};
            form.serializeArray().forEach(function(item){ data[item.name] = item.value; });
            form.find('input[type=checkbox]').each(function(){ data[this.name] = $(this).is(':checked') ? 1 : 0; });
            form.find('input[type=datetime-local]').each(function(){
                var val = this.value;
                if(val && val.length === 16){ val += ':00'; }
                data[this.name] = val.replace('T', ' ');
            });
            var method = id ? 'PUT' : 'POST';
            var url = rp_admin.rest_url + entity + (id ? '/' + id : '');
            var submit = function(){
                clearNotice();
                showOverlay(true);
                $.ajax({
                    url: url,
                    method: method,
                    contentType: 'application/json',
                    data: JSON.stringify(data),
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                    complete: function(){ hideOverlay(); },
                    success: function(resp){
                        showButtonMessage(form.find('button[type=submit]'), 'success', 'Salvato');
                        if(!id && resp && resp.id){
                            if(entity === 'users' || entity === 'events'){
                                setTimeout(function(){
                                    window.location = rp_admin.admin_url + '?page=res-pong-' + entity.slice(0,-1) + '-detail&id=' + resp.id;
                                }, 2000);
                            }else{
                                id = resp.id;
                                form.attr('data-id', id);
                                history.replaceState(null, '', rp_admin.admin_url + '?page=res-pong-' + entity.slice(0,-1) + '-detail&id=' + id);
                            }
                        }
                    },
                    error: function(xhr){
                        var msg = 'Errore durante il salvataggio';
                        if(xhr.responseJSON && xhr.responseJSON.message){ msg += ': ' + xhr.responseJSON.message; }
                        var type = xhr.status === 409 ? 'warning' : 'error';
                        showButtonMessage(form.find('button[type=submit]'), type, msg);
                    }
                });
            };
            if(entity === 'events' && id && $('#group_id').val()){
                rpConfirm('Applicare la modifica a tutta la serie di eventi?').then(function(apply){
                    if(apply){ url += '&apply_group=1'; }
                    submit();
                });
            } else {
                submit();
            }
        });
        $('#res-pong-delete').on('click', function(e){
            e.preventDefault();
            if(!id){ return; }
            var url = rp_admin.rest_url + entity + '/' + id;
            var executeDelete = function(){
                if(!confirm('Eliminare l\'elemento?')){ return; }
                clearNotice();
                showOverlay(true);
                $.ajax({
                    url: url,
                    method: 'DELETE',
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                    complete: function(){ hideOverlay(); },
                    success: function(){
                        window.location = rp_admin.admin_url + '?page=res-pong-' + entity;
                    },
                    error: function(xhr){
                        var msg = 'Errore durante l\'eliminazione';
                        if(xhr.responseJSON && xhr.responseJSON.message){ msg += ': ' + xhr.responseJSON.message; }
                        showButtonMessage($('#res-pong-delete'), 'error', msg);
                    }
                });
            };
            if(entity === 'events' && $('#group_id').val()){
                rpConfirm('Applicare la modifica a tutta la serie di eventi?').then(function(apply){
                    if(apply){ url += '&apply_group=1'; }
                    executeDelete();
                });
            } else {
                executeDelete();
            }
        });
        $('#res-pong-impersonate').on('click', function(){
            if(!id){ return; }
            clearNotice();
            showOverlay(true);
            $.ajax({
                url: rp_admin.rest_url + 'users/' + id + '/impersonate',
                method: 'POST',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                complete: function(){ hideOverlay(); },
                success: function(resp){ if(resp && resp.url){ window.open(resp.url, '_blank'); } },
                error: function(xhr){
                    var msg = 'Impersonazione fallita';
                    if(xhr.responseJSON && xhr.responseJSON.message){ msg += ': ' + xhr.responseJSON.message; }
                    showNotice('error', msg);
                }
            });
        });
        var pwdForm = $('#res-pong-password-form');
        if(pwdForm.length){
            pwdForm.on('submit', function(e){
                e.preventDefault();
                var pass = $('#new_password').val();
                var confirm = $('#confirm_password').val();
            if(pass.length < 6 || pass !== confirm){
                showButtonMessage(pwdForm.find('button[type=submit]'), 'error', 'Le password devono coincidere ed essere lunghe almeno 6 caratteri.');
                return;
            }
            clearNotice();
            showOverlay(true);
            $.ajax({
                url: rp_admin.rest_url + 'users/' + id + '/reset-password',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ password: pass }),
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                complete: function(){ hideOverlay(); },
                success: function(){ showButtonMessage(pwdForm.find('button[type=submit]'), 'success', 'Password salvata'); },
                error: function(xhr){
                    var msg = 'Errore durante il salvataggio della password';
                    if(xhr.responseJSON && xhr.responseJSON.message){ msg += ': ' + xhr.responseJSON.message; }
                    showButtonMessage(pwdForm.find('button[type=submit]'), 'error', msg);
                }
            });
        });
        $('#rp-send-invite').on('click', function(){
            var text = $('#rp-invite-text').val();
            clearNotice();
            showOverlay(true);
            $.ajax({
                url: rp_admin.rest_url + 'users/' + id + '/invite',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ text: text }),
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                complete: function(){ hideOverlay(); },
                success: function(){ showButtonMessage($('#rp-send-invite'), 'success', 'Invito inviato'); },
                error: function(xhr){
                    var msg = 'Invito fallito';
                    if(xhr.responseJSON && xhr.responseJSON.message){ msg += ': ' + xhr.responseJSON.message; }
                    showButtonMessage($('#rp-send-invite'), 'error', msg);
                }
            });
        });
        $('#rp-send-reset').on('click', function(){
            var text = $('#rp-reset-text').val();
            clearNotice();
            showOverlay(true);
            $.ajax({
                url: rp_admin.rest_url + 'users/' + id + '/reset-password',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ text: text }),
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                complete: function(){ hideOverlay(); },
                success: function(){ showButtonMessage($('#rp-send-reset'), 'success', 'Reset inviato'); },
                error: function(xhr){
                    var msg = 'Errore durante il reset';
                    if(xhr.responseJSON && xhr.responseJSON.message){ msg += ': ' + xhr.responseJSON.message; }
                    showButtonMessage($('#rp-send-reset'), 'error', msg);
                }
            });
        });
        }
        var timeoutForm = $('#res-pong-timeout-form');
        if(timeoutForm.length){
            if(id){ populateForm('users', id, timeoutForm); }
            timeoutForm.on('submit', function(e){
                e.preventDefault();
                var val = timeoutForm.find('[name=timeout]').val();
                if(!val){ return; }
                clearNotice();
                showOverlay(true);
                $.ajax({
                    url: rp_admin.rest_url + 'users/' + id,
                    method: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify({ timeout: val.replace('T', ' ') }),
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                    complete: function(){ hideOverlay(); },
                    success: function(){ showButtonMessage(timeoutForm.find('button[type=submit]'), 'success', 'Timeout salvato'); },
                    error: function(xhr){
                        var msg = 'Errore durante il salvataggio del timeout';
                        if(xhr.responseJSON && xhr.responseJSON.message){ msg += ': ' + xhr.responseJSON.message; }
                        showButtonMessage(timeoutForm.find('button[type=submit]'), 'error', msg);
                    }
                });
            });
            $('#rp-remove-timeout').on('click', function(){
                clearNotice();
                showOverlay(true);
                $.ajax({
                    url: rp_admin.rest_url + 'users/' + id,
                    method: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify({ timeout: null }),
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                    complete: function(){ hideOverlay(); },
                    success: function(){
                        timeoutForm.find('[name=timeout]').val('');
                        showButtonMessage($('#rp-remove-timeout'), 'success', 'Timeout rimosso');
                    },
                    error: function(xhr){
                        var msg = 'Errore durante la rimozione del timeout';
                        if(xhr.responseJSON && xhr.responseJSON.message){ msg += ': ' + xhr.responseJSON.message; }
                        showButtonMessage($('#rp-remove-timeout'), 'error', msg);
                    }
                });
            });
        }
    }

    function initEmailPage(){
        var form = $('#rp-email-form');
        if(!form.length){ return; }
        var toInput = $('#rp-email-to');
        var btn = form.find('button[type=submit]');
        $.ajax({
            url: rp_admin.rest_url + 'users',
            method: 'GET',
            beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
            success: function(users){
                var items = users.map(function(u){
                    var label = u.email + ' (' + u.last_name + ' ' + u.first_name + ')';
                    var search = (u.email + ' ' + u.last_name + ' ' + u.first_name).toLowerCase();
                    return { label: label, value: u.email, search: search };
                });
                toInput.autocomplete({
                    source: function(request, response){
                        var term = request.term.toLowerCase();
                        response(items.filter(function(it){ return it.search.indexOf(term) !== -1; }));
                    },
                    minLength: 0,
                    focus: function(){ return false; },
                    select: function(event, ui){
                        var terms = toInput.val().split(/,\s*/);
                        terms.pop();
                        terms.push(ui.item.value);
                        terms.push('');
                        toInput.val(terms.join(', '));
                        return false;
                    }
                }).on('keydown', function(event){
                    if(event.keyCode === $.ui.keyCode.TAB && $(this).autocomplete('instance').menu.active){
                        event.preventDefault();
                    }
                }).on('focus', function(){ $(this).autocomplete('search', ''); });
            }
        });
        var params = new URLSearchParams(window.location.search);
        var eventId = params.get('event_id');
        if(eventId){
            $.ajax({
                url: rp_admin.rest_url + 'reservations?event_id=' + eventId + '&active_only=1',
                method: 'GET',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                success: function(res){
                    var emails = res.map(function(r){ return r.email; });
                    toInput.val(emails.join(', '));
                }
            });
        }
        form.on('submit', function(e){
            e.preventDefault();
            var recipients = toInput.val().split(',').map(function(s){ return s.trim(); }).filter(Boolean);
            var data = {
                subject: $('#rp-email-subject').val(),
                text: $('#rp-email-text').val(),
                recipients: recipients
            };
            btn.prop('disabled', true);
            showOverlay(true);
            $.ajax({
                url: rp_admin.rest_url + 'email',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                complete: function(){ btn.prop('disabled', false); hideOverlay(); },
                success: function(){ showButtonMessage(btn, 'success', 'Email inviata'); },
                error: function(xhr){
                    var msg = 'Errore invio email';
                    if(xhr.responseJSON && xhr.responseJSON.message){ msg += ': ' + xhr.responseJSON.message; }
                    showButtonMessage(btn, 'error', msg);
                }
            });
        });
    }
    $(function(){
        var list = $('#res-pong-list');
        if(list.length){
            var ent = list.data('entity');
            initTable(list, ent, function(showAll){
                var params = '';
                if(ent === 'events'){ params = 'open_only=' + (showAll ? '0' : '1'); }
                else if(ent === 'reservations'){ params = 'active_only=' + (showAll ? '0' : '1'); }
                return restUrl(ent, params);
            }, { filterFuture: ent === 'events' || ent === 'reservations' });
        }
        var ur = $('#res-pong-user-reservations');
        if(ur.length){
            var uid = ur.data('user');
            initTable(ur, 'reservations', function(showAll){ return restUrl('reservations', 'user_id=' + uid + '&active_only=' + (showAll ? '0' : '1')); }, { addParams: 'user_id=' + uid, noCsv: true, filterFuture: true });
        }
        var er = $('#res-pong-event-reservations');
        if(er.length){
            var eid = er.data('event');
            initTable(er, 'reservations', function(showAll){ return restUrl('reservations', 'event_id=' + eid + '&active_only=' + (showAll ? '0' : '1')); }, { addParams: 'event_id=' + eid, noCsv: true, filterFuture: true });
        }
        initDetail();
        initEmailPage();
    });
})(jQuery);
