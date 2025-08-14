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
            toggleLabel = state ? 'Disabilita' : 'Abilita';
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
            { data: 'id', title: 'ID', render: function(d){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-user-detail&id=' + d + '">' + d + '</a>'; } },
            { data: 'email', title: 'Email' },
            { data: 'username', title: 'Username' },
            { data: 'name', title: 'Nome', render: function(d, type, row){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-user-detail&id=' + row.id + '">' + d + '</a>'; } },
            { data: 'category', title: 'Categoria' },
            { data: 'timeout', title: 'Timeout', render: function(d, type){ if(type === 'display'){ if(!d){ return ''; } var now = new Date(); var t = new Date(d.replace(' ', 'T')); return now < t ? d : ''; } return d; } },
            { data: 'timeout', title: 'In timeout', className: 'rp-icon-col', render: function(d, type){ if(!d){ return type === 'display' ? '' : 0; } var now = new Date(); var t = new Date(d.replace(' ', 'T')); var active = now < t; if(type === 'display'){ return active ? '<span class="dashicons dashicons-clock rp-icon-clock"></span>' : ''; } return active ? 1 : 0; } },
            { data: 'enabled', title: 'Abilitato', className: 'rp-icon-col', render: function(d, type){ return type === 'display' ? renderBool(d) : d; } },
            { data: null, title: 'Azioni', orderable: false, render: function(d){ return actionButtons('users', d); } }
        ],
        events: [
            { data: null, title: '', orderable: false, render: renderCheckbox },
            { data: 'id', title: 'ID', render: function(d){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-event-detail&id=' + d + '">' + d + '</a>'; } },
            { data: 'group_id', title: 'Gruppo' },
            { data: 'category', title: 'Categoria' },
            { data: 'name', title: 'Nome', render: function(d, type, row){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-event-detail&id=' + row.id + '">' + d + '</a>'; } },
            { data: 'start_datetime', title: 'Inizio' },
            { data: 'end_datetime', title: 'Fine' },
            { data: 'max_players', title: 'Giocatori max' },
            { data: 'enabled', title: 'Abilitato', className: 'rp-icon-col', render: function(d, type){ return type === 'display' ? renderBool(d) : d; } },
            { data: null, title: 'Stato', render: function(d){ var now = new Date(); var start = new Date(d.start_datetime.replace(' ', 'T')); return now > start ? 'chiuso' : 'aperto'; } },
            { data: null, title: 'Giocatori', render: function(d){ return d.max_players ? d.players_count + '/' + d.max_players : ''; } },
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
            if(!confirm('Delete item?')){ return; }
            showOverlay(true);
            $.ajax({
                url: rp_admin.rest_url + entity + '/' + id,
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
            showOverlay(true);
            $.ajax({
                url: rp_admin.rest_url + entity + '/' + id,
                method: 'PUT',
                contentType: 'application/json',
                data: JSON.stringify(data),
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                complete: function(){ hideOverlay(); },
                success: function(){ table.DataTable().ajax.reload(); }
            });
        });
    }
    function initList(){
        var table = $('#res-pong-list');
        if(!table.length){ return; }
        var entity = table.data('entity');
        function listUrl(){
            if(entity === 'events'){
                return restUrl(entity, 'open_only=' + ($('#rp-open-filter').is(':checked') ? 1 : 0));
            }else if(entity === 'reservations'){
                return restUrl(entity, 'active_only=' + ($('#rp-active-filter').is(':checked') ? 1 : 0));
            }
            return restUrl(entity);
        }
        var dt = table.DataTable({
            ajax: {
                url: listUrl(),
                dataSrc: '',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); }
            },
            columns: columns[entity]
        });
        dt.on('preXhr.dt', function(){
            showOverlay(true);
        });
        dt.on('xhr.dt', function(){
            hideOverlay();
        });
        dt.on('error.dt', function(){
            hideOverlay();
        });
        if(entity === 'events'){
            $('#rp-open-filter').on('change', function(){ dt.ajax.url(listUrl()).load(); });
        }else if(entity === 'reservations'){
            $('#rp-active-filter').on('change', function(){ dt.ajax.url(listUrl()).load(); });
        }
        $(dt.column(0).header()).html('<input type="checkbox" id="rp-select-all">');
        table.on('change', '#rp-select-all', function(){
            var checked = $(this).is(':checked');
            table.find('.rp-select').prop('checked', checked);
        });
        table.on('change', '.rp-select', function(){
            if(!this.checked){ $('#rp-select-all').prop('checked', false); }
        });
        handleActions(table, entity);
        $('#res-pong-add').on('click', function(e){
            e.preventDefault();
            window.location = rp_admin.admin_url + '?page=res-pong-' + entity.slice(0,-1) + '-detail';
        });
        $('#rp-apply-bulk').on('click', function(){
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
                if(callback){ callback(); }
            });
        }
        function initForm(){ if(id){ populateForm(entity, id, form); } }
        if(entity === 'reservations'){
            loadReservationOptions(initForm);
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
                    }
                },
                error: function(){
                    showNotice('error', 'Error saving');
                }
            });
        });
        $('#res-pong-delete').on('click', function(e){
            e.preventDefault();
            if(!id || !confirm('Delete item?')){ return; }
            showOverlay(true);
            $.ajax({
                url: rp_admin.rest_url + entity + '/' + id,
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
    function isEventOpen(data){
        var now = new Date();
        var start = new Date(data.event_start_datetime.replace(' ', 'T'));
        return now <= start;
    }
    function initUserReservations(){
        var table = $('#res-pong-user-reservations');
        if(!table.length){ return; }
        var user = table.data('user');
        if(!user){ return; }
        var dt = table.DataTable({
            ajax: {
                url: restUrl('reservations', 'user_id=' + user + '&active_only=1'),
                dataSrc: '',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); }
            },
            columns: [
                { data: 'event_name', title: 'Evento', render: function(d, type, row){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-event-detail&id=' + row.event_id + '">' + d + '</a>'; } },
                { data: 'name', title: 'Name', render: function(d, type, row){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-user-detail&id=' + row.user_id + '">' + d + '</a>'; } },
                { data: 'created_at', title: 'Created At' },
                { data: 'presence_confirmed', title: 'Presence', render: function(d, type){ return type === 'display' ? renderBool(d) : d; } },
                { data: null, title: 'Azioni', orderable: false, render: function(d){ return isEventOpen(d) ? '<button class="button rp-unsign" data-id="'+d.id+'">Unsubscribe</button>' : ''; } }
            ]
        });
        table.on('click', '.rp-unsign', function(){
            var id = $(this).data('id');
            if(!confirm('Delete item?')){ return; }
            showOverlay(true);
            $.ajax({
                url: rp_admin.rest_url + 'reservations/' + id,
                method: 'DELETE',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                complete: function(){ hideOverlay(); },
                success: function(){ dt.ajax.reload(); }
            });
        });
    }
    function initEventReservations(){
        var table = $('#res-pong-event-reservations');
        if(!table.length){ return; }
        var event = table.data('event');
        if(!event){ return; }
        var dt = table.DataTable({
            ajax: {
                url: restUrl('reservations', 'event_id=' + event + '&active_only=1'),
                dataSrc: '',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); }
            },
            columns: [
                { data: 'user_id', title: 'User ID', render: function(d){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-user-detail&id=' + d + '">' + d + '</a>'; } },
                { data: 'name', title: 'Name', render: function(d, type, row){ return '<a href="' + rp_admin.admin_url + '?page=res-pong-user-detail&id=' + row.user_id + '">' + d + '</a>'; } },
                { data: 'username', title: 'Username' },
                { data: 'presence_confirmed', title: 'Presence', render: function(d, type){ return type === 'display' ? renderBool(d) : d; } },
                { data: null, title: 'Azioni', orderable: false, render: function(d){
                    var toggleLabel = parseInt(d.presence_confirmed) ? 'Disabilita' : 'Abilita';
                    var buttons = '<button class="button rp-toggle" data-id="' + d.id + '">' + toggleLabel + '</button>';
                    if(isEventOpen(d)){
                        buttons += ' <button class="button rp-unsign" data-id="' + d.id + '">Disiscrivi</button>';
                    }
                    return buttons;
                } }
            ]
        });
        handleActions(table, 'reservations');
        table.on('click', '.rp-unsign', function(){
            var id = $(this).data('id');
            if(!confirm('Delete item?')){ return; }
            showOverlay(true);
            $.ajax({
                url: rp_admin.rest_url + 'reservations/' + id,
                method: 'DELETE',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                complete: function(){ hideOverlay(); },
                success: function(){ dt.ajax.reload(); }
            });
        });
    }
    $(function(){
        initList();
        initDetail();
        initUserReservations();
        initEventReservations();
    });
})(jQuery);
