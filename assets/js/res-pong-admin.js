(function($){
    var columns = {
        users: [
            { data: 'id', title: 'ID' },
            { data: 'email', title: 'Email' },
            { data: 'username', title: 'Username' },
            { data: 'first_name', title: 'First Name' },
            { data: 'last_name', title: 'Last Name' }
        ],
        events: [
            { data: 'id', title: 'ID' },
            { data: 'name', title: 'Name' },
            { data: 'start_datetime', title: 'Start' },
            { data: 'end_datetime', title: 'End' },
            { data: 'enabled', title: 'Enabled' }
        ],
        reservations: [
            { data: 'id', title: 'ID' },
            { data: 'user_id', title: 'User ID' },
            { data: 'event_id', title: 'Event ID' },
            { data: 'created_at', title: 'Created At' },
            { data: 'presence_confirmed', title: 'Presence' }
        ]
    };

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
        table.on('click', 'tbody tr', function(){
            var data = dt.row(this).data();
            window.location = rp_admin.admin_url + '?page=res-pong-' + entity.slice(0,-1) + '-detail&id=' + data.id;
        });
        $('#res-pong-add').on('click', function(e){
            e.preventDefault();
            window.location = rp_admin.admin_url + '?page=res-pong-' + entity.slice(0,-1) + '-detail';
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
                    if(field.attr('type') === 'checkbox'){
                        field.prop('checked', parseInt(data[key]) === 1);
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

    $(function(){
        initList();
        initDetail();
    });
})(jQuery);
