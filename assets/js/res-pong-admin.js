(function($){
    function renderCheckbox(data){
        return '<input type="checkbox" class="rp-select" value="' + data.id + '">';
    }
    function renderBool(val){
        return parseInt(val) === 1 ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no-alt"></span>';
    }
    function actionButtons(entity, data){
        var edit = '<button class="button rp-edit" data-id="' + data.id + '">Modifica</button>';
        var del = '<button class="button rp-delete" data-id="' + data.id + '">Cancella</button>';
        var toggleLabel, state;
        if(entity === 'reservations'){
            state = parseInt(data.presence_confirmed);
            toggleLabel = state ? 'Disabilita' : 'Abilita';
        }else{
            state = parseInt(data.enabled);
            toggleLabel = state ? 'Disabilita' : 'Abilita';
        }
        var toggle = '<button class="button rp-toggle" data-id="' + data.id + '">' + toggleLabel + '</button>';
        return edit + ' ' + del + ' ' + toggle;
    }
    var columns = {
        users: [
            { data: null, title: '', orderable: false, render: renderCheckbox },
            { data: 'id', title: 'ID' },
            { data: 'email', title: 'Email' },
            { data: 'username', title: 'Username' },
            { data: 'first_name', title: 'First Name' },
            { data: 'last_name', title: 'Last Name' },
            { data: 'category', title: 'Category' },
            { data: 'timeout', title: 'Timeout' },
            { data: 'enabled', title: 'Enabled', render: function(d){ return renderBool(d); } },
            { data: null, title: 'Azioni', orderable: false, render: function(d){ return actionButtons('users', d); } }
        ],
        events: [
            { data: null, title: '', orderable: false, render: renderCheckbox },
            { data: 'id', title: 'ID' },
            { data: 'group_id', title: 'Group' },
            { data: 'category', title: 'Category' },
            { data: 'name', title: 'Name' },
            { data: 'start_datetime', title: 'Start' },
            { data: 'end_datetime', title: 'End' },
            { data: 'max_players', title: 'Max Players' },
            { data: 'enabled', title: 'Enabled', render: function(d){ return renderBool(d); } },
            { data: null, title: 'Status', render: function(d){ var now = new Date(); var start = new Date(d.start_datetime.replace(' ', 'T')); return now > start ? 'closed' : 'open'; } },
            { data: null, title: 'Players', render: function(d){ return d.max_players ? d.players_count + '/' + d.max_players : ''; } },
            { data: null, title: 'Azioni', orderable: false, render: function(d){ return actionButtons('events', d); } }
        ],
        reservations: [
            { data: null, title: '', orderable: false, render: renderCheckbox },
            { data: 'id', title: 'ID' },
            { data: 'user_id', title: 'User ID' },
            { data: 'username', title: 'Username' },
            { data: 'event_id', title: 'Event ID' },
            { data: 'event_name', title: 'Event' },
            { data: 'created_at', title: 'Created At' },
            { data: 'presence_confirmed', title: 'Presence', render: function(d){ return renderBool(d); } },
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
            $.ajax({
                url: rp_admin.rest_url + entity + '/' + id,
                method: 'DELETE',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
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
            $.ajax({
                url: rp_admin.rest_url + entity + '/' + id,
                method: 'PUT',
                contentType: 'application/json',
                data: JSON.stringify(data),
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                success: function(){ table.DataTable().ajax.reload(); }
            });
        });
    }
    function initList(){
        var table = $('#res-pong-list');
        if(!table.length){ return; }
        var entity = table.data('entity');
        var dt = table.DataTable({
            ajax: {
                url: rp_admin.rest_url + entity,
                dataSrc: '',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); }
            },
            columns: columns[entity]
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
            var progress = $('#rp-progress');
            var bar = progress.find('progress');
            var text = $('#rp-progress-text');
            progress.show();
            var i = 0;
            function next(){
                if(i >= ids.length){ progress.hide(); dt.ajax.reload(); return; }
                var id = ids[i];
                var url = rp_admin.rest_url + entity + '/' + id;
                var method = action === 'delete' ? 'DELETE' : 'PUT';
                var data = null;
                if(action === 'enable'){ data = entity === 'reservations' ? { presence_confirmed:1 } : { enabled:1 }; }
                if(action === 'disable'){ data = entity === 'reservations' ? { presence_confirmed:0 } : { enabled:0 }; }
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
            }
        });
    }
    function initDetail(){
        var form = $('#res-pong-detail-form');
        if(!form.length){ return; }
        var entity = form.data('entity');
        var id = form.data('id');
        if(id){ populateForm(entity, id, form); }
        form.on('submit', function(e){
            e.preventDefault();
            var data = {};
            form.serializeArray().forEach(function(item){ data[item.name] = item.value; });
            form.find('input[type=checkbox]').each(function(){ data[this.name] = $(this).is(':checked') ? 1 : 0; });
            form.find('input[type=datetime-local]').each(function(){ data[this.name] = this.value.replace('T', ' '); });
            var method = id ? 'PUT' : 'POST';
            var url = rp_admin.rest_url + entity + (id ? '/' + id : '');
            $.ajax({
                url: url,
                method: method,
                contentType: 'application/json',
                data: JSON.stringify(data),
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                success: function(){
                    window.location = rp_admin.admin_url + '?page=res-pong-' + entity;
                }
            });
        });
        $('#res-pong-delete').on('click', function(e){
            e.preventDefault();
            if(!id || !confirm('Delete item?')){ return; }
            $.ajax({
                url: rp_admin.rest_url + entity + '/' + id,
                method: 'DELETE',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                success: function(){
                    window.location = rp_admin.admin_url + '?page=res-pong-' + entity;
                }
            });
        });
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
        var dt = table.DataTable({
            ajax: {
                url: rp_admin.rest_url + 'reservations?user_id=' + user,
                dataSrc: '',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); }
            },
            columns: [
                { data: 'event_name', title: 'Evento' },
                { data: 'created_at', title: 'Created At' },
                { data: 'presence_confirmed', title: 'Presence', render: function(d){ return renderBool(d); } },
                { data: null, title: 'Azioni', orderable: false, render: function(d){ return isEventOpen(d) ? '<button class="button rp-unsign" data-id="'+d.id+'">Disiscrivi</button>' : ''; } }
            ]
        });
        table.on('click', '.rp-unsign', function(){
            var id = $(this).data('id');
            $.ajax({
                url: rp_admin.rest_url + 'reservations/' + id,
                method: 'DELETE',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
                success: function(){ dt.ajax.reload(); }
            });
        });
    }
    function initEventReservations(){
        var table = $('#res-pong-event-reservations');
        if(!table.length){ return; }
        var event = table.data('event');
        var dt = table.DataTable({
            ajax: {
                url: rp_admin.rest_url + 'reservations?event_id=' + event,
                dataSrc: '',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); }
            },
            columns: [
                { data: 'user_id', title: 'User ID' },
                { data: 'username', title: 'Username' },
                { data: 'presence_confirmed', title: 'Presence', render: function(d){ return renderBool(d); } },
                { data: null, title: 'Azioni', orderable: false, render: function(d){ return isEventOpen(d) ? '<button class="button rp-unsign" data-id="'+d.id+'">Disiscrivi</button>' : ''; } }
            ]
        });
        table.on('click', '.rp-unsign', function(){
            var id = $(this).data('id');
            $.ajax({
                url: rp_admin.rest_url + 'reservations/' + id,
                method: 'DELETE',
                beforeSend: function(xhr){ xhr.setRequestHeader('X-WP-Nonce', rp_admin.nonce); },
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
