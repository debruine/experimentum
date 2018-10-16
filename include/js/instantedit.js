<!--
//script by http://www.yvoschaap.com

var changing = false;

function fieldEnter(campo,evt,idfld) {
	evt = (evt) ? evt : window.event;
	if (evt.keyCode == 13) {
		if (campo.value=="") { campo.value='{{blank}}'; }
		elem = $(idfld);
		noLight(elem); //remove glow
		elem.html(unescape(campo.value));
		changing = false;
		return false;
	} else {
		return true;
	}


}

function fieldBlur(campo,idfld) {
	if (campo.value=="") { campo.value='{{blank}}'; }
	
	$('#' + idfld).html(campo.value);
	$('#i_' + idfld).val(escape(campo.value.replace('{{blank}}', '')));

	changing = false;
	return false;
}

//edit field created
function editBox(actual) {
	if(!changing){
		$(actual).html('<textarea name="textarea" id="' + $(actual).attr('id') + '_field" '
			+ 'style="min-width: 5em; width: ' + $(actual).width() + 'px;" '
			+ 'oninput="textarea_expand(this, 0, 0)" '
			+ 'onfocus="highLight(this); textarea_expand(this, 0, 0)" '
			+ 'onblur="noLight(this); return fieldBlur(this,\'' + $(actual).attr('id') + '\');">'
			+ $(actual).html() + '</textarea>');

		changing = true;
		var $inputEl = $('#' + $(actual).attr('id') + '_field');
		
		$inputEl.select();
		$inputEl.keydown( function(e) {
			if (e.which == 9) {
				var $allEditTexts = $('span.editText');
				var index = $allEditTexts.index(actual);
				if (index <= $allEditTexts.length) {
					var $nextEditText = $allEditTexts.eq(index + 1);
				} else {
					var $nextEditText = $allEditTexts.eq(0);
				}
				$(this).blur();
				$nextEditText.click();
				return false;
			}
		});
		$inputEl.focus();
	}
	
}


//find all span tags with class editText and id as fieldname parsed to update script. add onclick function
function editbox_init(){
	$('span.editText').each( function(i) {
		var spanid = $(this).attr('id');
		
		if ($('#i_' + spanid).length == 0) {
			if ($(this).html() == '' || $(this).html() == '&nbsp;') { $(this).html('{{blank}}'); }
		
			$(this).click( function() { editBox($(this)); } ).css('cursor', 'pointer');
			
			var thisValue = $(this).html().replace(/\{\{\w+\}\}/, ''); // remove anything in double brackets

			var hid = '<input type="hidden" class="instantedit" name="i_' + spanid + '" id="i_' + spanid + '" value="' + escape(thisValue) + '" />';
			$(this).parent().prepend(hid);
	
			$(this).click().find('textarea').blur();
		}
	});
		
}

function highLight(span){
	span.style.border = "1px solid red";      
}

function noLight(span){
	span.style.border = "0px";   
}

$(function() { editbox_init(); });
-->