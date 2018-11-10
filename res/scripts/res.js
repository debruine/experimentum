$('#function_buttonset').buttonset();

$( "#view-item" ).click(function() {
    window.location = '/' + $('#item_type').val() + '?id=' + $('#item_id').val();
});

$( "#edit-item" ).click(function() {
    window.location = '/res/' + $('#item_type').val() + '/builder?id=' + $('#item_id').val();
});

$( "#data-download" ).click(function() { 
    postIt('/res/scripts/download', {
        type: $('#item_type').val(),
        id: $('#item_id').val()
    });
});

$( "#download-quest" ).click(function() { 
    postIt('/res/scripts/download', {
        download: 'quest',
        type: $('#item_type').val(),
        id: $('#item_id').val()
    });
});

$( "#download-exp" ).click(function() {
    postIt('/res/scripts/download', {
        download: 'exp',
        type: $('#item_type').val(),
        id: $('#item_id').val()
    });
});

$( "#get-json" ).click(function() { 
    postIt('/res/scripts/get_json', {
        table: $('#item_type').val(),
        id: $('#item_id').val()
    });
});

$('#gosets').click( function() {
    var s = $('#insets').val();
    window.location.href = "/res/set/info?id=" + s;
});

$( "#status" ).css('fontWeight', 'normal').change( function() {
    var $sel = $(this);
    var item_id = $('#item_id').val();
    var item_type = $('#item_type').val();
    
    $sel.css('color', 'red');

    $.ajax({
        url: '/res/scripts/status',
        type: 'POST',
        data: {
            type: item_type,
            status: $sel.val(),
            id: item_id
        },
        success: function(data) {
            if (data == 'Status of ' + item_type + '_' + item_id + ' changed to '+ $sel.val() ) {
                $sel.css('color', 'inherit');
            } else {
                growl(data, 30);
            }
        }
    });
});

$( "#delete-item" ).click( function() {
    var type = $('#item_type').val();
    
    $( "<div/>").html("Do you really want to delete this item?").dialog({
        title: "Delete Item",
        position: ['center', 100],
        modal: true,
        buttons: {
            Cancel: function() {
                $( this ).dialog( "close" );
            },
            "Delete": function() {
                $( this ).dialog( "close" );
                $.ajax({
                    url: "/res/scripts/item_delete",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        type: $('#item_type').val(),
                        id: $('#item_id').val()
                    },
                    success: function(data) {
                        if (!data.error) {
                            type = (type=="sets") ? "set" : type;
                            window.location = '/res/'+ type +'/';
                        } else {
                            $('<div title="Problem with Deletion" />').html(data.error).dialog();
                        }
                    }
                });
            },
        }
    });
});

$( "#duplicate-item" ).click( function() {
    var type = $('#item_type').val();
    
    $( "<div/>").html("Do you really want to duplicate this item?").dialog({
        title: "Duplicate Item",
        position: ['center', 100],
        modal: true,
        buttons: {
            Cancel: function() {
                $( this ).dialog( "close" );
            },
            "Duplicate": function() {
                $( this ).dialog( "close" );
                $.ajax({
                    url: "/res/scripts/item_duplicate",
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        type: type,
                        id: $('#item_id').val()
                    },
                    success: function(data) {
                        if (!data.error) {
                            type = (type=="sets") ? "set" : type;
                            window.location = '/res/'+ type +'/info?id=' + data.new_id;
                        } else {
                            $('<div title="Problem with Duplication" />').html(data.error).dialog();
                        }
                    }
                });
            },
        }
    });
});


$( "#owner-add" ).click( function() {
    var owner_id = $('#owner-add-input').data('id');
    
    if (owner_id == '' || owner_id == 0) { return false; }
    
    if ($('#owner-edit .owner-delete[owner-id=' + owner_id + ']').length == 0) {
        var new_owner = "<li><span class='new-owner'>" + $('#owner-add-input').val() + "</span> (<a class='owner-delete' owner-id='"+owner_id+"'>delete</a>)</li>";
        $('#owner-edit').append(new_owner);
    } else {
        growl("You can't add a duplicate owner.");
    }
    $('#owner-add-input').val('').data('id','');
});

$('html').on("click", ".owner-delete", function() {
    if ($(this).text() == 'delete') {
        $(this).text('undelete');
        $(this).prev().addClass('delete-owner');
    } else {
        $(this).text('delete');
        $(this).prev().removeClass('delete-owner');
    }
});

$('html').on("click", ".owner-delete", function() {
    if ($(this).text() == 'delete') {
        $(this).text('undelete');
        $(this).prev().addClass('delete-owner');
    } else {
        $(this).text('delete');
        $(this).prev().removeClass('delete-owner');
    }
});

$('button.tinybutton').button();

$( "#owner-change" ).click( function() {
    var to_add = [];
    var to_delete = [];
    $('#owner-edit .owner-delete').each( function() {
        var $this = $(this);
        
        if ($this.text() == "delete") {
            to_add.push($this.attr('owner-id'));
        } else {
            to_delete.push($this.attr('owner-id'));
        }
    });
    
    if (to_add.length == 0) {
        growl("You have to keep at least one owner.");
        return false;
    }
    
    $.ajax({
        url: '/res/scripts/owners',
        type: 'POST',
        data: {
            type: $('#item_type').val(),
            id: $('#item_id').val(),
            add: to_add,
            delete: to_delete
        },
        success: function(data) {
            if (data) {
                growl(data);
            } else {
                $('#owner-edit .delete-owner').closest('li').remove();
                $('#owner-edit span').removeClass('new-owner');
            }
        }
    });
});

function item_stats(items, proj_id) {
    console.log('item_stats', items);
    
    var totals = {
        people: 0,
        men: 0,
        women: 0,
        peopled: 0,
        mend: 0,
        womend: 0,
        median: 0,
        upper: 0
    };
    
    

    $.each(items, function(idx, item) {
        var url = '/res/scripts/item_stats?item=' + item;
        if (typeof proj_id !== "undefined") {
            url += '&proj=' + proj_id;
        }
        
        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                totals.people += data.total_c;
                totals.men += data.total_male;
                totals.women += data.total_female;
                totals.peopled += data.total_dist;
                totals.mend += data.dist_male;
                totals.womend += data.dist_female;
                
                totals.median += parseInt(data.median * 10);
                totals.upper += parseInt(data.upper * 10);
                
                $('#total_people').html(totals.people + " (" + totals.peopled + ")");
                $('#total_men').html(totals.men + " (" + totals.mend + ")");
                $('#total_women').html(totals.women + " (" + totals.womend + ")");
                console.log($('#itemtype').text().substr(0, 3));
                if ($('#itemtype').text().substr(0, 3) == "One") {
                    $('#total_median').html(parseInt(totals.median/items.length)/10 + ' min');
                    $('#total_upper').html(parseInt(totals.upper/items.length)/10 + ' min');
                } else {
                    $('#total_median').html(totals.median/10 + ' min');
                    $('#total_upper').html(totals.upper/10 + ' min');
                }
                
                var cells = $('#' + item + ' td');
                
                cells[4].innerHTML = data.total_c      + " (" + data.total_dist + ")";
                cells[5].innerHTML = data.total_male   + " (" + data.dist_male + ")";
                cells[6].innerHTML = data.total_female + " (" + data.dist_female + ")";
                cells[7].innerHTML = data.median;
                cells[8].innerHTML = data.upper;
            }
        });
    });
}