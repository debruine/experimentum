<!--

/* NO PROTOTYPE */

jQuery.noConflict(); var $j = jQuery;

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

/****************************************************/
/* !onLoad items                                    */
/****************************************************/

$j(function() {
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
    
    // set up help dialog box
    if ($j('#help').length > 0) {
        $j('<div />')
            .html('help')
            .addClass('helpbutton')
            .insertAfter($j('#login_info'))
            .click( function() { $j('#help').dialog({show: "scale",
                hide: "scale",
                width: 650,
                position: ['center', 50]
            }); });
    }
    
    // set up loginBox dialog
    $j('#loginbox').dialog({
        autoOpen: false,
        show: "scale",
        hide: "scale",
        modal: true,
        position: ['center', 'center'],
        buttons: {
            "Login" : function() { login(); },
            "Sign up for an account" : function() { window.location.href="/consent"; },
            //"Participate without an account" : function() { window.location.href="/consent?guest"; },
        }
    });
    $j('#guestloginbox').dialog({
        autoOpen: false,
        show: "scale",
        hide: "scale",
        modal: true,
        position: ['center', 'center'],
        buttons: {
            "Login" : function() { login(); }
        }
    });
    
    // set up loginError dialog
    $j('#login_error_header').dialog({
        autoOpen: false,
        show: "scale",
        hide: "scale",
        modal: true,
        position: ['center', 50],
    });
    
    // give button styles to all inputs in a buttons div
    $j('.buttons input, .buttons a, .buttons button').button();
    
    // format all datepicker types
    $j( ".datepicker[yearrange][mindate][maxdate]" ).each( function() {
        var minmax = $j(this).attr('yearrange').split(':');
        var yearscovered = parseInt(minmax[1]) - parseInt(minmax[0]);
    
        $j(this).datepicker({
            dateFormat: "yy-mm-dd",
            yearRange: $j(this).attr('yearrange'),
            minDate: $j(this).attr('mindate'),
            maxDate: $j(this).attr('maxdate'),
            changeMonth: true,
            changeYear: (yearscovered>1) ? true : false
        });
    });
    
    // format all horizontal radiobuttons
    $j("ul.radio").buttonset();
        
    /*$j("#header").click( function() {
        $j("#header").toggleClass('minimal');
    });*/
 /*   
    $j(document).keydown(function(e) {
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
    
        if (    (    $j('.ui-dialog').is(':visible')
                     || $j('input:focus').length
                     || $j('textarea:focus').length
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
            $j('#select_all').click();
        }
        e.preventDefault();
    });
*/
});

/****************************************************/
/* !EVERY PAGE FUNCTIONS                            */
/****************************************************/

// load pages for iPhone
function loadPage(url) {
    $j('body').append('<div id="progress">Loading...</div>');
    if (url == undefined) {
        $j('wrap').load('/index #wrap', hijackLinks);
    } else {
        $j('wrap').load(url + ' #wrap', hijackLinks);
    }
}

function hijackLinks() {
    $j('#wrap a[href]').click( function(e) {
        e.preventDefault();
        loadPage(e.target.href);
    });
    $j('#progress').remove();
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
    $j.get("/include/scripts/logout", function (response) {
        //FB.logout(function(response) {
          // user is now logged out of facebook
        //});

        window.location = window.location;
    });
}

function startLogin() {
    $j("#loginbox").dialog("open");
    $j("#login_username").focus();
}

function login() {
    var un = $j("#login_username").val();
    var pw = $j("#login_password").val();
    console.log('logging in ' + un);
    
    if (un != "" && pw != "") {
        
        var url = "/include/scripts/login?username=" + un + "&password=" + pw;
        
        $j("#login_error").hide();
        
        $j.get(url, function(data) {
            if (data == "login") {
                $j("#login_error").hide();
                window.location.reload(false);
            } else {
                var parsedResponse = data.split(":");
                if (parsedResponse[0] == "username") {
                    $j("#login_username").focus();
                    $j("#login_username").select();
                    $j("#login_error").html( parsedResponse[1] ).show();
                } else if (parsedResponse[0] == "password") {
                    $j("#login_password").focus();
                    $j("#login_password").select();
                    $j("#login_error").html( parsedResponse[1] ).show();
                } else if (parsedResponse[0] == "newpage") {
                    window.location = parsedResponse[1];
                } else {
                    $j("#login_error").html( data ).show();
                }
            }
        });
    }
}

function login() {
    var un = $j("#login_username").val();
    var pw = $j("#login_password").val();
    console.log('logging in ' + un);
    
    if (un != "" && pw != "") {
        
        var url = "/include/scripts/login?username=" + un + "&password=" + pw;
        
        $j("#login_error").hide();
        
        $j.get(url, function(data) {
            if (data == "login") {
                $j("#login_error").hide();
                window.location.reload(false);
            } else {
                var parsedResponse = data.split(":");
                if (parsedResponse[0] == "username") {
                    $j("#login_username").focus();
                    $j("#login_username").select();
                    $j("#login_error").html( parsedResponse[1] ).show();
                } else if (parsedResponse[0] == "password") {
                    $j("#login_password").focus();
                    $j("#login_password").select();
                    $j("#login_error").html( parsedResponse[1] ).show();
                } else if (parsedResponse[0] == "newpage") {
                    window.location = parsedResponse[1];
                } else {
                    $j("#login_error").html( data ).show();
                }
            }
        });
    }
}

function guestLogin(project_id) {
    var url ="/include/scripts/login_guest";
    
    $j.get(url, function(data) {
        if (data == "login") {
            $j("#login_error_header").dialog("close").css('background', 'none');
            window.location.reload(false);
        } else {
            var parsedResponse = data.split(":");
            if (parsedResponse[0] == "newpage") {
                window.location = parsedResponse[1];
            } else {
                $j("#login_error_header").html( data ).css('background', 'none').dialog("open");;
            }
        }
    });
}

function facebook_data() {
    $j.get('/include/scripts/facebook_data', function(data) {
        // do nothing unless there is a return
        if (data) alert(data);
    });
}   

/****************************************************/
/* ! QUESTIONNAIRE FUNCTIONS                        */
/****************************************************/

function stripe(e) {
    $j(e).children(":visible:odd").addClass("odd").removeClass("even");
    $j(e).children(":visible:even").addClass("even").removeClass("odd");
}

function stripeAllTables() {
    alert('stripeAllTables() is deprecated.');
}

function narrowTable(tbl, searchText) {
    $j(tbl).children(':not(.nosearch)').each( function() {
        if ($j(this).html().toLowerCase().indexOf(searchText.toLowerCase()) != -1) {
            $j(this).show();
        } else {
            $j(this).hide();
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
    $j('#' + table_id + ' textarea, #' + table_id + ' input, #' + table_id + ' select').each( function(i) {
        $j(this).attr('original_value', $j(this).val())
                .removeClass('unsaved')
                .bind( "change keypress", function() {
                    if ($j(this).val() != $j(this).attr('original_value')) {
                        $j(this).addClass('unsaved');
                    } else { 
                        $j(this).removeClass('unsaved');
                    }
                });
                /*
                .dblclick( function() {
                    var inputElement = $j(this);
                    
                    if (inputElement.val() != inputElement.attr('original_value')) {
                        $j('<div />').html('Revert to original value?').dialog({
                            modal: true,
                            position: ['center', 100],
                            buttons: {
                                "Cancel": function() {
                                    $j(this).dialog('close');
                                },
                                "Revert": function() {
                                    inputElement.val(inputElement.attr('original_value'));
                                    inputElement.removeClass('unsaved');
                                    inputElement.trigger('change');
                                    $j(this).dialog('close');
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
    var growlDialog = $j('<div />').attr('title', title).dialog({
        hide: "fade",
        position: pos
    }).fadeOut(0);
    if (interval >= 100) {
        setTimeout(function() { growlDialog.dialog("close"); }, interval);
    }
    
    //return growlDialog;
}


// set up dashboard checkboxes in lists
function dashboard_checkboxes(type) {
    $j(".fav").each( function() {
        $j(this).click( function() {
            var dash_id = $j(this).attr("id").replace("dash", "");
            
            if ($j(this).hasClass("heart")) {
                // remove from dashboard
                $j.get('/res/scripts/dashboard?delete&type=' + type + '&id=' + dash_id, function(data) { if (data != '') alert(data); });
                
                // change hidden label to - for sorting
                $j(this).removeClass("heart").text("-");
            } else {
                // add to dashboard
                $j.get('/res/scripts/dashboard?add&type=' + type + '&id=' + dash_id, function(data) { if (data != '') alert(data); });
                
                // change hidden label to + for sorting
                $j(this).addClass("heart").text('+');
            }
            
            $j(this).blur(); // prevents icon getting stuck in focus state
        });
    });
}

// set up status changer in index pages
function statusChanger(column, theType) {
    var status_menu = '<select class="status_changer"><option value="test">test</option><option value="active">active</option><option value="inactive">inactive</option></select>';
    
    
    $j('table.query tbody tr').each( function(i) {
        var status_cell = $j(this).find('td:nth-child(' + column + ')');
        var the_id = $j(this).find('td:nth-child(2)').find('a').html();
        var the_status = status_cell.html();
        status_cell.wrapInner('<span />').find('span').click(function() {
            $j('select.status_changer').hide().prev('span').show();
            $j(this).hide().next('select').show();
        });
        status_cell.append(status_menu);
        status_cell.find('select').hide().val(the_status).change(function() {
            var $sel = $j(this);
            $j.ajax({
                url: '/res/scripts/status',
                type: 'POST',
                data: {
                    type: theType,
                    status: $sel.val(),
                    id: the_id
                },
                success: function(data) {
                    if (data == 'Status of ' + theType +'_'+the_id+' changed to '+ $sel.val() ) {
                        $sel.hide().prev('span').show().html($sel.val());
                    } else {
                        growl(data, 30);
                        $sel.hide().prev('span').show();
                    }
                }
            });
            
            
        });
    });
}

function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}

function postIt(url, data){

    $j('body').append($j('<form/>', {
      id: 'jQueryPostItForm',
      method: 'POST',
      action: url
    }));

    for(var i in data){
      $j('#jQueryPostItForm').append($j('<input/>', {
        type: 'hidden',
        name: i,
        value: data[i]
      }));
    }

    $j('#jQueryPostItForm').submit();
}

function folderize(json, appendElement) {
    
    appendElement   .unbind('click')                    // remove folderize function
                    .parent('ul').find('li.folder').addClass('closed'); // close all folders at and below this level
    appendElement   .removeClass('closed')              // open folder, since you just clicked on it
                    .find('span').click( function() {   // add a folder opening function when clicked   
                        $j(this).parent().removeClass('closed') // open this folder on click
                                .siblings('li').addClass('closed') // close sibling folders
                                .find('li').addClass('closed'); // close all folders below this level
                    });
    
    var theFolder = $j('<ul />').css('margin-left', appendElement.width()+10);

    $j.each(json, function(folder, contents) {
        var theItem = $j('<li />');

        if (contents.length > 1) {
            // contents are an image name
            var splitName = contents.split('/');
            var shortName = splitName[splitName.length - 1];
            
            theItem .html('<span>' + shortName + '</span>')
                    .attr('url', contents)
                    .addClass('image')
                    .click( function() {
                        $j('#imagebox img').attr('src', $j(this).attr('url'));
                        $j('#imagebox #imageurl').html($j(this).attr('url'));
                        
                        $j('li.image.selected').removeClass('selected');
                        $j(this).addClass('selected');
                        $j(this).siblings('li').addClass('closed').find('li').addClass('closed');
                    });
        } else {
            // contents are more files/folders
            
            var splitName = folder.split('/');
            var shortName = splitName[splitName.length - 1];
            
            theItem .html('<span>' + shortName + '</span>')
                    .addClass('folder closed')
                    .data('contents', contents)
                    .click( function() { 
                        folderize($j(this).data('contents'), $j(this)); 
                    });
        }
        
        theFolder.append( theItem );
    });

    appendElement.append( theFolder );
    $j('#finder > ul').css('margin-left', 0); // fix first ul
}

function sizeToViewport() {
    var new_height = $j(window).height() - $j('#finder').offset().top - $j('#footer').height()-30;
    $j('#finder').height(new_height);
}

-->