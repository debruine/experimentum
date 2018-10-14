<!--

/* NO PROTOTYPE */

jQuery.noConflict(); var $j = jQuery;

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

function radioChange(img, name, myvalue) {
    alert('This function is deprecated.');
/*
    $j("#" + name).val(myvalue);
    $j('#' + name + '_row td img.radio').attr("src", '/images/icons/my/radio_unselected');
    $j('#' + name + '_row td img.radio').attr("alt", 'o');
    img.src = '/images/icons/my/radio_selected';
    img.alt = 'x';
    
    $j("#" + name + "_row").removeClass('emptyAlert');
*/
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

-->