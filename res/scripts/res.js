$('#function_buttonset').buttonset();

$( "#view-item" ).click(function() {
    var url = '/' + $('#item_type').val() + '?id=' + $('#item_id').val();
    window.open(url, '_blank');
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

$( "html" ).on("change", "#status", function() {
    var $sel = $(this);
    var item_id = $('#item_id').val();
    var item_type = $('#item_type').val();
    
    if (item_type == "set") item_type = "sets";
    
    $sel.css('color', 'red');

    $.ajax({
        url: '/res/scripts/status',
        type: 'POST',
        dataType: 'json',
        data: {
            type: item_type,
            status: $sel.val(),
            id: item_id
        },
        success: function(data) {
            if (data.error) {
                $('<div title="Problem with Status Change" />').html(data.error).dialog();
            } else {
                $sel.css('color', 'inherit');
            }
        }
    });
});

// set up status changer in index pages
function statusChanger(column, theType) {
    var status_menu = '<select class="status_changer">' +
                      '<option value="test">test</option>' +
                      '<option value="active">active</option>' +
                      '<option value="archive">archive</option>' +
                      '</select>';
    
    
    $('table.query tbody tr').each( function(i) {
        var status_cell = $(this).find('td:nth-child(' + column + ')');
        var the_id = $(this).find('td:nth-child(2)').find('a').html();
        var the_status = status_cell.html();
        status_cell.wrapInner('<span />').find('span').click(function() {
            $('select.status_changer').hide().prev('span').show();
            $(this).hide().next('select').show();
        });
        status_cell.append(status_menu);
        status_cell.find('select').hide().val(the_status).change(function() {
            var $sel = $(this);
            $.ajax({
                url: '/res/scripts/status',
                type: 'POST',
                dataType: 'json',
                data: {
                    type: theType,
                    status: $sel.val(),
                    id: the_id
                },
                success: function(data) {
                    if (data.error) {
                        $('<div title="Problem with Status Change" />').html(data.error).dialog();
                        $sel.hide().prev('span').show();
                    } else {
                        $sel.hide().prev('span').show().html($sel.val());
                    }
                }
            });
            
            
        });
    });
}

// set up dashboard checkboxes in lists
function dashboard_checkboxes(type) {
    $(".fav").each( function() {
        $(this).click( function() {
            var dash_id = $(this).attr("id").replace("dash", "");
            
            if ($(this).hasClass("heart")) {
                // remove from dashboard
                $.get('/res/scripts/dashboard?delete&type=' + type + '&id=' + dash_id, function(data) { if (data != '') alert(data); });
                
                // change hidden label to - for sorting
                $(this).removeClass("heart").text("-");
            } else {
                // add to dashboard
                $.get('/res/scripts/dashboard?add&type=' + type + '&id=' + dash_id, function(data) { if (data != '') alert(data); });
                
                // change hidden label to + for sorting
                $(this).addClass("heart").text('+');
            }
            
            $(this).blur(); // prevents icon getting stuck in focus state
        });
    });
}


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


$( "#owner-add, #owner-add-items" ).click( function() {
    var owner_id = $('#owner-add-input').data('id');
    var owner_name = $('#owner-add-input').val();
    
    if (owner_id == '' || owner_id == 0) { return false; }
    
    if ($('#owner-edit .owner-delete[owner-id=' + owner_id + ']').length == 0) {
        // not a duplicate, so add now
        
        $.ajax({
            url: '/res/scripts/owners',
            type: 'POST',
            data: {
                type: $('#item_type').val(),
                id: $('#item_id').val(),
                add: [owner_id],
                add_items: this.id == 'owner-add-items'
            },
            success: function(data) {
                if (data) {
                    growl(data);
                } else {
                    var new_owner = "<tr><td>" + owner_name + "</td> " +
                    "<td><button class='tinybutton owner-delete' owner-id='"+owner_id+"'>remove</button></td>";
                    if ($('#item_type').val() == "set" || $('#item_type').val() == "project") { 
                        new_owner += "<td><button class='tinybutton owner-delete-items' owner-id='"+owner_id+"'>remove from all items</button></td>";
                    }
                    new_owner += "</tr>";
                    $('#owner-edit').append(new_owner).find('.tinybutton').button();
                    $('#owner-add-input').val('').data('id','');
                }
            }
        });
        
    } else {
        growl("You can't add a duplicate owner.");
        $('#owner-add-input').val('').data('id','');
    }
});

$('html').on("click", ".owner-delete, .owner-delete-items", function() {
    var owner_id = $(this).attr('owner-id');
    var $row = $(this).closest('tr');
    
    if ($('#owner-edit tr').length < 2) {
        growl("You have to keep one owner.");
        return false;
    }
    
    $.ajax({
        url: '/res/scripts/owners',
        type: 'POST',
        data: {
            type: $('#item_type').val(),
            id: $('#item_id').val(),
            delete: [owner_id],
            delete_items: $(this).hasClass('owner-delete-items')
        },
        success: function(data) {
            if (data) {
                growl(data);
            } else {
                console.log('deleted');
                $row.remove();
            }
        }
    });
});

$('button.tinybutton').button();

function item_stats(items, proj_id, all_status = false) {
    console.log('item_stats', items);
    
    var totals = {
        people: 0,
        men: 0,
        women: 0,
        nb: 0,
        peopled: 0,
        mend: 0,
        womend: 0,
        nbd: 0,
        median: 0,
        upper: 0
    };
    
    if (typeof proj_id !== "undefined") {
        console.log('proj_id', proj_id);
        // get project stats
        $.ajax({
            url: '/res/scripts/proj_stats',
            type: 'POST',
            data: {id: proj_id, all: all_status},
            dataType: 'json',
            success: function(data) {
                $('#total_people').html(data.total.people + " (" + data.total.peopled + ")");
                $('#total_men').html(data.total.men + " (" + data.total.mend + ")");
                $('#total_women').html(data.total.women + " (" + data.total.womend + ")");
                $('#total_nb').html(data.total.nb + " (" + data.total.nbd + ")");
                
                $('#compl_people').html(data.compl.people + " (" + data.compl.peopled + ")");
                $('#compl_men').html(data.compl.men + " (" + data.compl.mend + ")");
                $('#compl_women').html(data.compl.women + " (" + data.compl.womend + ")");
                $('#compl_nb').html(data.compl.nb + " (" + data.compl.nbd + ")");
                
                $('#compl_median').html(data.timings.median);
                $('#compl_upper').html(data.timings.upper);
            }
        });
    }
    
    // get stats for each item
    $.each(items, function(idx, item) {
        var theData = {item: item, all: all_status}
        if (typeof proj_id !== "undefined") theData.proj = proj_id;
        
        $.ajax({
            url: '/res/scripts/item_stats',
            type: 'POST',
            data: theData,
            dataType: 'json',
            success: function(data) {
                if (data.median != "No info") totals.median += parseInt(data.median * 10);
                if (data.upper != "No info") totals.upper += parseInt(data.upper * 10);
                
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
                cells[7].innerHTML = data.total_nb + " (" + data.dist_nb + ")";
                cells[8].innerHTML = data.median;
                cells[9].innerHTML = data.upper;
            }
        });
    });
}