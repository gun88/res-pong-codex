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
    function showNotice(type, text){
        var wrap = $('.wrap').first();
        wrap.find('.notice').remove();
        $('<div class="notice notice-' + type + ' is-dismissible"><p>' + text + '</p></div>').prependTo(wrap);
    }
    function restUrl(path, params){
        var url = rp_admin.rest_url + path;
        if(params){
            url += (url.indexOf('?') === -1 ? '?' : '&') + params;
        }
        return url;
    }
    function actionButtons(entity, data){
        var edit = '<button class="button rp-edit rp-action-btn" data-id="' + data.id + '">Modifica</button>';
        var del = '<button class="button rp-delete rp-button-danger rp-action-btn" data-id="' + data.id + '">Cancella</button>';
        var toggleLabel, state, toggleClass;
        if(entity === 'reservations'){
            state = parseInt(data.presence_confirmed);
            toggleLabel = state ? 'Assente' : 'Presente';
        }else{
            state = parseInt(data.enabled);
            toggleLabel = state ? 'Disabilita' : 'Abilita';
        }
        toggleClass = state ? 'rp-button-disable' : 'rp-button-enable';
        var toggle = '<button class="button rp-toggle rp-action-btn ' + toggleClass + '" data-id="' + data.id + '">' + toggleLabel + '</button>';
        return edit + ' ' + del + ' ' + toggle;
    }
    var columns = {
        users: [
            { data: null, title: '', orderable: false, render: renderCheckbox },
            { data: 'name', title: 'Nome', render: function(d, type, row){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-user-detail&id=' + row.id + '">' + d + '</a>'; } },
            { data: 'id', title: 'Tessera', render: function(d){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-user-detail&id=' + d + '">' + d + '</a>'; } },
            { data: 'email', title: 'Email' },
            { data: 'username', title: 'Username' },
            { data: 'category', title: 'Categoria' },
            { data: 'enabled', title: 'Abilitato', className: 'rp-icon-col', render: function(d, type){ return type === 'display' ? renderBool(d) : d; } },
            { data: 'timeout', title: 'Timeout', render: function(d, type){ if(type === 'display'){ if(!d){ return ''; } var now = new Date(); var t = new Date(d.replace(' ', 'T')); return now < t ? d : ''; } return d; } },
            { data: 'timeout', title: 'In timeout', className: 'rp-icon-col', render: function(d, type){ if(!d){ return type === 'display' ? '' : 0; } var now = new Date(); var t = new Date(d.replace(' ', 'T')); var active = now < t; if(type === 'display'){ return active ? '<span class="dashicons dashicons-clock rp-icon-clock"></span>' : ''; } return active ? 1 : 0; } },
            { data: null, title: 'Azioni', orderable: false, render: function(d){ return actionButtons('users', d); } }
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
            { data: null, title: 'Azioni', orderable: false, render: function(d){ return actionButtons('events', d); } }
        ],
        reservations: [
            { data: null, title: '', orderable: false, render: renderCheckbox },
            { data: 'id', title: 'ID', render: function(d){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-reservation-detail&id=' + d + '">' + d + '</a>'; } },
            { data: 'user_id', title: 'ID utente', render: function(d){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-user-detail&id=' + d + '">' + d + '</a>'; } },
            { data: 'username', title: 'Username' },
            { data: 'event_id', title: 'ID evento', render: function(d){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-event-detail&id=' + d + '">' + d + '</a>'; } },
            { data: 'event_name', title: 'Evento' },
            { data: 'created_at', title: 'Creato il' },
            { data: 'presence_confirmed', title: 'Presenza', className: 'rp-icon-col', render: function(d, type){ return type === 'display' ? renderBool(d) : d; } },
            { data: null, title: 'Azioni', orderable: false, render: function(d){ return actionButtons('reservations', d); } }
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
            if(entity === 'events' && row.group_id){
                if(confirm('Applicare la modifica a tutta la serie di eventi?')){ url += '?apply_group=1'; }
            }
            if(!confirm('Delete item?')){ return; }
            showOverlay(true);
            $.ajax({
                url: url,
                method: 'DELETE',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                complete: function(){ hideOverlay(); },
                success: function(){ table.DataTable().ajax.reload(); }
            });
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
            if(entity === 'events' && row.group_id){
                if(confirm('Applicare la modifica a tutta la serie di eventi?')){ url += '?apply_group=1'; }
            }
            showOverlay(true);
            $.ajax({
                url: url,
                method: 'PUT',
                contentType: 'application/json',
                data: JSON.stringify(data),
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                complete: function(){ hideOverlay(); },
                success: function(){ table.DataTable().ajax.reload(); }
            });
        });
    }
    function initTable(table, entity, urlFunc, opts){
        opts = opts || {};
        var dt = table.DataTable({
            ajax: {
                url: urlFunc(),
                dataSrc: '',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); }
            },
            columns: columns[entity]
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
        var addBtn = $('<button class="button rp-button-add" id="res-pong-add"><span class="dashicons dashicons-plus"></span> Aggiungi</button>');
        var importBtn = $('<button class="button" id="res-pong-import">Importa CSV</button>');
        var exportBtn = $('<button class="button" id="res-pong-export">Esporta CSV</button>');
        toolbar.append(separator0, bulk);
        toolbar.append(separator1, addBtn);
        if(!opts.noCsv){
            toolbar.append(separator2, importBtn, exportBtn);
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
                    error: function(){ showNotice('error', 'Import failed'); }
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
                    $('#res-pong-invite').prop('disabled', !!data.password);
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
            form.find('input[type=datetime-local]').each(function(){ data[this.name] = this.value.replace('T', ' '); });
            var method = id ? 'PUT' : 'POST';
            var url = rp_admin.rest_url + entity + (id ? '/' + id : '');
            if(entity === 'events' && id && $('#group_id').val()){
                if(confirm('Applicare la modifica a tutta la serie di eventi?')){ url += '?apply_group=1'; }
            }
            showOverlay(true);
            $.ajax({
                url: url,
                method: method,
                contentType: 'application/json',
                data: JSON.stringify(data),
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                complete: function(){ hideOverlay(); },
                success: function(resp){
                    showNotice('success', 'Saved');
                    if(!id && resp && resp.id){
                        id = resp.id;
                        form.attr('data-id', id);
                        history.replaceState(null, '', rp_admin.admin_url + '?page=res-pong-' + entity.slice(0,-1) + '-detail&id=' + id);
                        if(entity === 'events'){
                            $('#recurrence_row, #recurrence_end_row').hide();
                        }
                    }
                },
                error: function(){
                    showNotice('error', 'Error saving');
                }
            });
        });
        $('#res-pong-delete').on('click', function(e){
            e.preventDefault();
            if(!id){ return; }
            var url = rp_admin.rest_url + entity + '/' + id;
            var proceed = true;
            if(entity === 'events' && $('#group_id').val()){
                if(confirm('Applicare la modifica a tutta la serie di eventi?')){ url += '?apply_group=1'; }
                proceed = confirm('Delete item?');
            }else{
                proceed = confirm('Delete item?');
            }
            if(!proceed){ return; }
            showOverlay(true);
            $.ajax({
                url: url,
                method: 'DELETE',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                complete: function(){ hideOverlay(); },
                success: function(){
                    window.location = rp_admin.admin_url + '?page=res-pong-' + entity;
                },
                error: function(){
                    showNotice('error', 'Error deleting');
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
                    alert('Passwords must match and be at least 6 characters.');
                    return;
                }
                showOverlay(true);
                $.ajax({
                    url: rp_admin.rest_url + 'users/' + id + '/reset-password',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ password: pass }),
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                    complete: function(){ hideOverlay(); },
                    success: function(){ alert('Password saved'); }
                });
            });
            $('#res-pong-invite').on('click', function(){
                showOverlay(true);
                $.ajax({
                    url: rp_admin.rest_url + 'users/' + id + '/invite',
                    method: 'POST',
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                    complete: function(){ hideOverlay(); },
                    success: function(){ alert('Invited'); }
                });
            });
            $('#res-pong-reset-password').on('click', function(){
                showOverlay(true);
                $.ajax({
                    url: rp_admin.rest_url + 'users/' + id + '/reset-password',
                    method: 'POST',
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                    complete: function(){ hideOverlay(); },
                    success: function(){ alert('Password reset'); }
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
                showOverlay(true);
                $.ajax({
                    url: rp_admin.rest_url + 'users/' + id,
                    method: 'PUT',
                    contentType: 'application/json',
                    data: JSON.stringify({ timeout: val.replace('T', ' ') }),
                    beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                    complete: function(){ hideOverlay(); },
                    success: function(){ alert('Timeout saved'); }
                });
            });
        }
    }
    $(function(){
        var list = $('#res-pong-list');
        if(list.length){
            initTable(list, list.data('entity'), function(){ return restUrl(list.data('entity')); });
        }
        var ur = $('#res-pong-user-reservations');
        if(ur.length){
            var uid = ur.data('user');
            initTable(ur, 'reservations', function(){ return restUrl('reservations', 'user_id=' + uid + '&active_only=1'); }, { addParams: 'user_id=' + uid, noCsv: true });
        }
        var er = $('#res-pong-event-reservations');
        if(er.length){
            var eid = er.data('event');
            initTable(er, 'reservations', function(){ return restUrl('reservations', 'event_id=' + eid + '&active_only=1'); }, { addParams: 'event_id=' + eid, noCsv: true });
        }
        initDetail();
    });
})(jQuery);
