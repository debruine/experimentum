<!--

var KEYCODE = {
    'backspace' : 8,
    'tab' : 9,
    'enter' : 13,
    'shift' : 16,
    'ctrl' : 17,
    'cmd' : 224,
    'alt' : 18,
    'pause_break' : 19,
    'caps_lock' : 20,
    'esc' : 27,
    'space' : 32,
    'page_up' : 33,
    'page_down' : 34,
    'end' : 35,
    'home' : 36,
    'left_arrow' : 37,
    'up_arrow' : 38,
    'right_arrow' : 39,
    'down_arrow' : 40,
    'insert' : 45,
    'delete' : 46,
    '0' : 48,
    '1' : 49,
    '2' : 50,
    '3' : 51,
    '4' : 52,
    '5' : 53,
    '6' : 54,
    '7' : 55,
    '8' : 56,
    '9' : 57,
    'equal' : 61,
    'plus' : 61,
    'minus' : 173,
    'underscore' : 173,
    'a' : 65,
    'b' : 66,
    'c' : 67,
    'd' : 68,
    'e' : 69,
    'f' : 70,
    'g' : 71,
    'h' : 72,
    'i' : 73,
    'j' : 74,
    'k' : 75,
    'l' : 76,
    'm' : 77,
    'n' : 78,
    'o' : 79,
    'p' : 80,
    'q' : 81,
    'r' : 82,
    's' : 83,
    't' : 84,
    'u' : 85,
    'v' : 86,
    'w' : 87,
    'x' : 88,
    'y' : 89,
    'z' : 90,
    'left_window' : 91,
    'right_window' : 92,
    'select_key' : 93,
    '0n' : 96,
    '1n' : 97,
    '2n' : 98,
    '3n' : 99,
    '4n' : 100,
    '5n' : 101,
    '6n' : 102,
    '7n' : 103,
    '8n' : 104,
    '9n' : 105,
    'multiply' : 106,
    'add' : 107,
    'subtract' : 109,
    'decimal_point' : 110,
    'divide' : 111,
    'f1' : 112,
    'f2' : 113,
    'f3' : 114,
    'f4' : 115,
    'f5' : 116,
    'f6' : 117,
    'f7' : 118,
    'f8' : 119,
    'f9' : 120,
    'f10' : 121,
    'f11' : 122,
    'f12' : 123,
    'num_lock' : 144,
    'scroll_lock' : 145,
    'semicolon' : 186,
    'equal_sign' : 187,
    'comma' : 188,
    'dash' : 189,
    'period' : 190,
    'forward_slash' : 191,
    'grave_accent' : 192,
    'open_bracket' : 219,
    'backslash' : 220,
    'closebracket' : 221,
    'single_quote' : 222
};

// set defaults for ajax
$.xhrPool = []; // array of uncompleted requests
$.xhrPool.abortAll = function() { // our abort function
    $(this).each(function(idx, jqXHR) {
        jqXHR.abort();
    });
    $.xhrPool.length = 0;
};

$.ajaxSetup({
    //dataType: 'json',
    type: 'POST',
    beforeSend: function(jqXHR) { // before jQuery send the request we will push it to our array
        $.xhrPool.push(jqXHR);
    },
    complete: function(jqXHR) { // when some of the requests completed it will splice from the array
        var index;

        index = $.xhrPool.indexOf(jqXHR);
        if (index > -1) {
            $.xhrPool.splice(index, 1);
        }
    }
});

/****************************************************/
/* !onLoad items                                    */
/****************************************************/

$(function() {
    // remove console functions if they are undefined (old IE)
    if (typeof console === "undefined") {
        console = { 
            log: function() { },
            warn: function() { },
            time: function() { },
            timeEnd: function() { },
            debug: function() { },
        };
    }

    stripe('tbody');
    
    $('.warning').on('dblclick', function() {$(this).remove(); })
    
    // set up help dialog box
    if ($('#help').length > 0) {
        $('<div />')
            .html('help')
            .addClass('helpbutton')
            .insertAfter($('#login_info'))
            .click( function() { $('#help').dialog({show: "scale",
                hide: "scale",
                width: 650,
                position: ['center', 50]
            }); });
    }
    
    // give button styles to all inputs in a buttons div
    $('.buttons input, .buttons a, .buttons button, #quit_study').button();
    
    // format all datepicker types
    $( ".datepicker[yearrange][mindate][maxdate]" ).each( function() {
        var minmax = $(this).attr('yearrange').split(':');
        var yearscovered = parseInt(minmax[1]) - parseInt(minmax[0]);
    
        $(this).datepicker({
            dateFormat: "yy-mm-dd",
            yearRange: $(this).attr('yearrange'),
            minDate: $(this).attr('mindate'),
            maxDate: $(this).attr('maxdate'),
            changeMonth: true,
            changeYear: (yearscovered>1) ? true : false
        });
    });
    
    // format all horizontal radiobuttons
    $("ul.radio").buttonset();
    
    // setup sliders
    $('div.slider').each( function() {
        $(this).slider({
            min: parseFloat($(this).attr('min')),
            max: parseFloat($(this).attr('max')),
            step: parseFloat($(this).attr('step')),
            change: function(e, ui) {
                $(ui.handle).show();
                $(this).attr('title', '');
            }
        }).attr("title", "Click on the slider to show the handle");
    });
    
    // quit study button
    $('#quit_study').click(function() {
        console.log("Quit Study Clicked");
        $('<div />').dialog({
            title: 'Do you want to quit the study?',
            modal: false,
            position: ['center', 100],
            width: 500,
            buttons: {
                "Cancel": function() {
                    $(this).dialog('close');
                },
                "Quit and Keep Data in Study": function() {
                    $.ajax({
                        url: '/include/scripts/session_end',
                        type: 'POST',
                        data: { keep: true },
                        success: function(data) {
                            window.location = "/";
                        }
                    });
    
                    $(this).dialog('close');
                },
                "Quit and Remove Data from Study": function() {
                    $.ajax({
                        url: '/include/scripts/session_end',
                        type: 'POST',
                        data: { keep: false },
                        success: function(data) {
                            window.location = "/";
                        }
                    });
    
                    $(this).dialog('close');
                }
            }
        });
    });

 /*   
    $(document).keydown(function(e) {
        var navKeys,  // list of keycodes for navigation (except when in input boxes)
            funcKeys; // list of keycodes for text functions (except when in input boxes)
    
        navKeys = [
            KEYCODE.left_arrow,
            KEYCODE.right_arrow,
            KEYCODE.down_arrow,
            KEYCODE.up_arrow,
            KEYCODE.delete,
            KEYCODE.backspace
        ];
    
        funcKeys = [
            KEYCODE.x,
            KEYCODE.c,
            KEYCODE.v,
            KEYCODE.a
        ];
    
        if (    (    $('.ui-dialog').is(':visible')
                     || $('input:focus').length
                     || $('textarea:focus').length
                ) &&
                (    ((e.ctrlKey || e.metaKey) && ( funcKeys.indexOf(e.which) !== -1 ))
                     || (navKeys.indexOf(e.which) !== -1)
                )
    
            ) {
            // do not override cut/paste/copy/select all keyboard shortcuts
            // and delete/arrow functions when dialog windows are open
            // or on the login page or an input/textarea is focussed
            return true; 
        } else if (e.which == KEYCODE.a) {                                      // !cmd-a
            $('#select_all').click();
        }
        e.preventDefault();
    });
*/
});

/****************************************************/
/* !EVERY PAGE FUNCTIONS                            */
/****************************************************/

// pad a number with leading zeros (or other chararcter)
String.prototype.pad = function(width, padder) {
    var len;

    padder = padder || '0';
    len = this.length;
    return (len >= width) ? this : new Array(width - len + 1).join(padder) + this;
}
Number.prototype.pad = function(width, padder) {
    str = this + ''; // turn numbers into strings
    return(str.pad(width, padder));
}

// change the height of a textarea to fit the amount of text in it
function textarea_expand(ta, min, max) {
    if (ta.scrollHeight>ta.clientHeight){
        ta.style.height=(ta.scrollHeight)+"px";
    } else {
        ta.style.height="10px";
        ta.style.height=(ta.scrollHeight)+"px";
    }
    
    if (ta.clientHeight < min) ta.style.height= min+"px";
    if (ta.clientHeight > max && max>0) ta.style.height= max+"px";
}

function logout() {
    console.log('logging out');
    $.get("/include/scripts/logout", function (response) {
        window.location = window.location;
    });
}

function login() {
    var un = $("#login_username").val();
    var pw = $("#login_password").val();
    console.log('logging in ' + un);
    
    if (un != "" && pw != "") {
        $.ajax({
            url: '/include/scripts/login',
            dataType: 'json',
            data: {
                username: un,
                password: pw
            },
            success: function(data) {
                if (data.login == 'login') {
                    console.log("logged in");
                    $("#login_error").hide();
                    window.location = data.url;
                } else {
                    $("#login_error").html( data.error ).show();
                }
            }
        });
    }
}

function reset_password() {
    var un = $("#login_username").val();
    console.log('resetting password for ' + un);
    
    $.ajax({
        url: '/include/scripts/password_reset',
        dataType: 'json',
        data: {
            username: un
        },
        success: function(data) {
            if (data.error) {
                $("#login_error").html( data.error ).show();
            } else {
                $("#login_error").html( data.msg ).show();
            }
        }
    });
}

function guestLogin(project_id) {
    $('<div />').append('<p>Please indicate your age and gender. Providing this information is optional, but some studies have restrictions or different versions depending on this information.</p>\n')
                .append('<table><tr><td>Age:</td><td><input id="guest_age" name="guest_age" type="number" min="0" max="120" /></td></tr>\n' +
                        "<tr><td>Gender:</td><td><select name='guest_sex' id='guest_sex'>\n" +
                        "    <option value='NULL'></option>\n" +
                        "    <option value='male'>male</option>\n" +
                        "    <option value='female'>female</option>\n" + 
                        "    <option value='nonbinary'>non-binary</option>\n" + 
                        "    <option value='na'>prefer not to answer</option>\n" + 
                        "</select></td></tr></table>\n")
                .dialog({
        title: 'Guest Login',
        modal: true,
        position: ['center', 100],
        width: 500,
        buttons: {
            "Cancel": function() {
                $(this).dialog('close');
            },
            "Login": function() {
                $.ajax({
                    url: '/include/scripts/login_guest',
                    type: 'POST',
                    data: {
                        sex: $('#guest_sex').val(),
                        age: $('#guest_age').val()
                    },
                    success: function(data) {
                        if (data == "login") {
                            $("#login_error_header").dialog("close").css('background', 'none');
                            window.location.reload(false);
                        } else {
                            var parsedResponse = data.split(":");
                            if (parsedResponse[0] == "newpage") {
                                window.location = parsedResponse[1];
                            } else {
                                $("#login_error_header").html( data ).css('background', 'none').dialog("open");;
                            }
                        }
                    }
                });

                $(this).dialog('close');
            }
        }
    });
}

// download without leaving the page
function postIt(url, data) {
    $('#jQueryPostItForm').remove();
    $('body').append($('<form/>', {
        id: 'jQueryPostItForm',
        method: 'POST',
        action: url
    }));
    for (var i in data) {
        if (data.hasOwnProperty(i)) {
            $('#jQueryPostItForm').append($('<input/>', {
                type: 'hidden',
                name: i,
                value: data[i]
            }));
        }
    }
    $('#jQueryPostItForm').submit();
}


/****************************************************/
/* ! QUESTIONNAIRE FUNCTIONS                        */
/****************************************************/

function stripe(e) {
    $(e).children(":visible:odd").addClass("odd").removeClass("even");
    $(e).children(":visible:even").addClass("even").removeClass("odd");
}

function stripeAllTables() {
    alert('stripeAllTables() is deprecated.');
}

function narrowTable(tbl, searchText) {
    $(tbl).children(':not(.nosearch)').each( function() {
        if ($(this).html().toLowerCase().indexOf(searchText.toLowerCase()) != -1) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    
    stripe(tbl);
}


// Limit the text field to only numbers (no decimals)
function formatInt(input) {
    var num = input.value.replace(/\,/g,'');
    if(!isNaN(num)){
        if(num.indexOf('.') > -1) {
            alert("You may not enter any decimals.");
            input.value = input.value.substring(0,input.value.length-1);
        }
    } else {
        alert('You may enter only numbers in this field!');
        input.value = input.value.substring(0,input.value.length-1);
    }
}

function setOriginalValues(table_id) {
    $('#' + table_id + ' textarea, #' + table_id + ' input, #' + table_id + ' select').each( function(i) {
        $(this).attr('original_value', $(this).val())
                .removeClass('unsaved')
                .bind( "change keypress", function() {
                    if ($(this).val() != $(this).attr('original_value')) {
                        $(this).addClass('unsaved');
                    } else { 
                        $(this).removeClass('unsaved');
                    }
                });
                /*
                .dblclick( function() {
                    var inputElement = $(this);
                    
                    if (inputElement.val() != inputElement.attr('original_value')) {
                        $('<div />').html('Revert to original value?').dialog({
                            modal: true,
                            position: ['center', 100],
                            buttons: {
                                "Cancel": function() {
                                    $(this).dialog('close');
                                },
                                "Revert": function() {
                                    inputElement.val(inputElement.attr('original_value'));
                                    inputElement.removeClass('unsaved');
                                    inputElement.trigger('change');
                                    $(this).dialog('close');
                                }
                            }
                        });
                    }
                });
                */

    });
}

function growl(title, interval, pos) {
    pos = pos || "center";

    var growlDialog = $('<div />').attr('title', title).dialog({
        hide: "fade",
        position: pos,
        width: 400,
        height: "auto"
    }).fadeOut(0);
    if (interval >= 100) {
        setTimeout(function() { growlDialog.dialog("close"); }, interval);
    }
    
    //return growlDialog;
}

function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}

function postIt(url, data){

    $('body').append($('<form/>', {
      id: 'jQueryPostItForm',
      method: 'POST',
      action: url
    }));

    for(var i in data){
      $('#jQueryPostItForm').append($('<input/>', {
        type: 'hidden',
        name: i,
        value: data[i]
      }));
    }

    $('#jQueryPostItForm').submit();
}

function folderize(json, appendElement) {
    
    appendElement   .unbind('click')                    // remove folderize function
                    .parent('ul').find('li.folder').addClass('closed'); // close all folders at and below this level
    appendElement   .removeClass('closed')              // open folder, since you just clicked on it
                    .find('span').click( function() {   // add a folder opening function when clicked   
                        $(this).parent().removeClass('closed') // open this folder on click
                                .siblings('li').addClass('closed') // close sibling folders
                                .find('li').addClass('closed'); // close all folders below this level
                    });
    
    var theFolder = $('<ul />').css('margin-left', appendElement.width()+10);

    $.each(json, function(folder, contents) {
        var theItem = $('<li />');

        if (contents.length > 1) {
            // contents are an image name
            var splitName = contents.split('/');
            var shortName = splitName[splitName.length - 1];
            var ext = shortName.substr(shortName.length - 3);
            
            var classes = {
                'jpg': 'image',
                'gif': 'image',
                'png': 'image',
                'mp3': 'audio',
                'ogg': 'audio',
                'wav': 'audio',
                'm4v': 'video',
                'txt': 'text',
                'csv': 'csv',
            };
            
            var avtype = {
                'mp3': 'audio/mpeg',
                'ogg': 'audio/ogg',
                'wav': 'audio/wave',
                'm4v': 'video/mp4'
            };
            
            theItem .html('<span>' + shortName + '</span>')
                    .attr('url', contents)
                    .addClass(classes[ext])
                    .addClass('file')
                    .click( function() {
                        if (classes[ext]=='image') {
                            $('#imagebox img').show().attr('src', $(this).attr('url'));
                            $('#imagebox audio, #imagebox video').hide();
                        } else if (classes[ext]=='audio') {
                            $('#imagebox img').hide().attr('src', '/images/linearicons/volume-high.php');
                            $('#imagebox audio').show();
                            $('#imagebox video').hide();
                            $('#imagebox audio').get(0).pause();
                            $('#imagebox audio source').attr('src', $(this).attr('url'));
                            $("#imagebox audio").get(0).load();
                        } else if (classes[ext]=='video') {
                            $('#imagebox img').hide().attr('src', '/images/linearicons/camera-video.php');
                            $('#imagebox audio').hide();
                            $('#imagebox video').show();
                            $('#imagebox video').get(0).pause();
                            $('#imagebox video source').attr('src', $(this).attr('url'));
                            $('#imagebox video').get(0).load();
                        }
                        $('#imagebox #imageurl').html($(this).attr('url'));
                        
                        $('li.file.ui-selected').removeClass('ui-selected');
                        $(this).addClass('ui-selected');
                        $(this).siblings('li').addClass('closed').find('li').addClass('closed');
                    });
        } else {
            // contents are more files/folders
            
            var splitName = folder.split('/');
            var shortName = splitName[splitName.length - 1];
            
            theItem .html('<span>' + shortName + '</span>')
                    .attr('url', folder)
                    .addClass('folder closed')
                    .data('contents', contents)
                    .click( function() {
                        var fname = folder.replace(/\/stimuli\/uploads\/\d+\//, '');
                        $('#subdir').val(fname);
                        folderize($(this).data('contents'), $(this)); 
                    });
        }
        
        theFolder.append( theItem );
    });

    appendElement.append( theFolder );
    $('#finder > ul').css('margin-left', 0); // fix first ul
    /*$('#finder li.folder > ul').selectable({
        filter: "li.file"
    });*/
    $('#imagebox audio, #imagebox video, #imagebox img').hide();
}

function sizeToViewport() {
    var new_height = $(window).height() - $('#finder').offset().top - $('#footer').height()-30;
    $('#finder').height(new_height);
}

-->