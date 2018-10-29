<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
auth($RES_STATUS);

$title = array(
	'/res/' => 'Researchers',
	'/res/exp/' => 'Experiment',
	'' => 'Edit Adaptation Trials'
);

$styles = array(
	'.trial_builder' => 'border: 2px solid ' . THEME . '; padding: 0.5em;',
	'.trial_builder, #image_chooser' => 'font-size: 80%; overflow:auto;',
	'.trial_builder img' => 'display: inline-block; min-width: 80px; min-height: 30px;',
	'.trial_builder img.trialimg, #img_list img' => 'width: 80px; border: 1px solid ' . THEME . '; margin: 3px;',
	'.trial_builder span.imgname' => 'display: none;',
	'.trial_builder.list img' => 'display: none;',
	'.trial_builder.list span.imgname' => 'display: inline-block; border: 1px solid ' . THEME . '; min-height: 1em; width: 170px; padding: 5px; margin: 2px;',
	'#img_list img' => 'box-shadow: 2px 2px 4px rgba(0,0,0,.5);',
	'#img_list li:hover' => 'cursor: move;',
	'#img_list li' => 'padding:1px; margin:0; border:1px solid rgba(0,0,0,0); max-width: 30em;',
	'.ui-selected, .ui-selecting' => 'border: 1px solid red;',
	'#image_search' => 'width: 300px;',
	'#listfill' => 'width: 100%; height: 150px;',
	'.img_column' => 'width: 80px;',
	'#image_toggle a, #list_toggle a' => 'color: #999;',
	'#images_found' => 'float: right; padding-left: 1em;',
	'#search-bar' => 'float: right; width: 48%; text-align: right;',
	'#search-bar input' => 'width: 70%;',
	'.drop_hover' => 'border: 1px solid red;',
	'.trial' => 'border-bottom: 3px solid ' . THEME,
	'.trial p' => 'margin:0; padding:0; clear: left;',
);

function imgname($src) {
	$name = str_replace('/images/stimuli', '', $src);
	$name = str_replace('.jpg', '', $name);
	$name = str_replace('.ogg', '', $name);
	$name = str_replace('.mp3', '', $name);
	return $name;
}

/***************************************************/
/* !Save trials */
/***************************************************/
 
if (array_key_exists('save', $_GET)) {	
	$exp_id = intval($_GET['exp_id']);
	$q = new myQuery('DELETE FROM adapt_trial WHERE exp_id=' . $exp_id);
	$q = new myQuery('DELETE FROM versions WHERE exp_id=' . $exp_id);

	foreach ($_POST as $version => $vdata) {
		$vquery = sprintf('INSERT INTO versions (exp_id, version, name, notes, question) VALUES (%d, %d, "%s", "%s", "%s")',
			$exp_id,
			intval($version),
			my_clean($vdata['name']),
			my_clean($vdata['notes']),
			my_clean($vdata['question'])
		);
		
		$vq = new myQuery($vquery);
	
		foreach($vdata['trials'] as $trial => $tdata) {
			if (!empty($tdata['limg']) && !empty($tdata['cimg']) && !empty($tdata['rimg'])) {
				$insertlist = 'left_img, center_img, right_img,';
				$idlist = 'limg.id, cimg.id, rimg.id';
				$imagelist = 'stimuli as limg, stimuli AS cimg, stimuli AS rimg';
				$where = 'limg.path = "' . $tdata['limg'] . '" AND cimg.path = "' . $tdata['cimg'] . '" AND rimg.path = "' . $tdata['rimg'] . '"';
			} else if (!empty($tdata['limg']) && empty($tdata['cimg']) && !empty($tdata['rimg'])) {
				$insertlist = 'left_img, right_img,';
				$idlist = 'limg.id, rimg.id';
				$imagelist = 'stimuli as limg, stimuli AS rimg';
				$where = 'limg.path = "' . $tdata['limg'] . '" AND rimg.path = "' . $tdata['rimg'] . '"';
			} else if (empty($tdata['limg']) && !empty($tdata['cimg']) && empty($tdata['rimg'])) {
				$insertlist = 'center_img,';
				$idlist = 'cimg.id';
				$imagelist = 'stimuli as cimg';
				$where = 'cimg.path = "' . $tdata['cimg'] . '"';
			} else if (empty($tdata['limg']) && empty($tdata['cimg']) && empty($tdata['rimg'])) {
				$insertlist = 'center_img,';
				$idlist = 'NULL';
				$imagelist = 'stimuli';
				$where = 'stimuli.id=1';
			}
		
			$query = sprintf('INSERT INTO adapt_trial (exp_id, trial_n, exposure,  
				%s version) 
				SELECT %d, %d, %d, %s, %d
				FROM %s 
				WHERE %s',
				$insertlist,
				$exp_id,
				$trial,
				$tdata['exposure'],
				$idlist,
				$version,
				$imagelist,
				$where
			);

			$q = new myQuery($query);
		}
	}
	
	echo 'Trials saved.';

	exit;
} 

/***************************************************/
/* !Get trial information */
/***************************************************/

$exp_id=$_GET['id'];

if (!validID($exp_id)) { header('Location: /'); exit; }

// get experiment info
$query = new myQuery('SELECT res_name, version, versions.name, notes, versions.question FROM exp LEFT JOIN versions ON exp.id=exp_id WHERE id=' . $exp_id . ' ORDER BY version');
$exp_info = $query-> get_assoc();
$title[] = $exp_info[0]['res_name'];

// get existing trial info
$query = new myQuery('SELECT trial_n, exposure, version,
		li.path as limg,
		ci.path as cimg,
		ri.path as rimg
	FROM adapt_trial
	LEFT JOIN stimuli AS li ON (li.id=left_img)
	LEFT JOIN stimuli AS ci ON (ci.id=center_img)
	LEFT JOIN stimuli AS ri ON (ri.id=right_img)
	WHERE exp_id=' . $exp_id . ' ORDER BY version, trial_n');
	
$trials = $query->get_assoc();

if (count($trials) == 0) {
	// no trials exist, set up trials
	$trials = array();
	$exp_info[0]['version'] = 1;
	
	$maxtrials = 10;
	ifEmpty($_GET['images'], 'c');
	
	for ($i=0; $i<$maxtrials; $i++) {
		$trials[$i] = array(
			'trial_n' => ($i+1),
			'version' => 1,
			'exposure' => 2000
		);
		
		if (strpos($_GET['images'], 'l') !== false) $trials[$i]['limg'] = '/images/stimuli/blankface.jpg';
		if (strpos($_GET['images'], 'c') !== false) $trials[$i]['cimg'] = '/images/stimuli/blankface.jpg';
		if (strpos($_GET['images'], 'r') !== false) $trials[$i]['rimg'] = '/images/stimuli/blankface.jpg';
	}
} elseif (isset($_GET['images'])) {
	// update image if $_GET['images'] is inconsistent with existing images
	$to_update = array();
	if (strpos($_GET['images'], 'l') !== false && empty($trials[0]['limg'])) $to_update['limg'] = 'add';
	if (strpos($_GET['images'], 'c') !== false && empty($trials[0]['cimg'])) $to_update['cimg'] = 'add';
	if (strpos($_GET['images'], 'r') !== false && empty($trials[0]['rimg'])) $to_update['rimg'] = 'add';
	if (strpos($_GET['images'], 'l') === false && !empty($trials[0]['limg'])) $to_update['limg'] = 'delete';
	if (strpos($_GET['images'], 'c') === false && !empty($trials[0]['cimg'])) $to_update['cimg'] = 'delete';
	if (strpos($_GET['images'], 'r') === false && !empty($trials[0]['rimg'])) $to_update['rimg'] = 'delete';
	
	if (count($to_update)>0) {
		foreach($trials as $i => $trial) {
			foreach($to_update as $img => $action) {
				if ($action == 'add') $trials[$i][$img] = '/images/stimuli/blankface';
				if ($action == 'delete') unset($trials[$i][$img]);
			}
		}
	}
}

// set up table width and margins to display trials and image chooser correctly
$tablewidth = (((empty($trials[0]['limg'])) ? 0 : 90) +
			  ((empty($trials[0]['cimg'])) ? 0 : 90) +
			  ((empty($trials[0]['rimg'])) ? 0 : 90) + 25);
			  
if ($tablewidth > 500) $tablewidth = 500;
if ($tablewidth < 100) $tablewidth = 100;
			  
$styles['.trial_builder'] = 'float: left; width: ' . $tablewidth . 'px;';
$styles['#image_chooser'] = 'margin-left: ' . ($tablewidth+20) * $exp_data['versions'] . 'px; min-width: 100px;';
$styles['.trial_builder.list'] = 'max-width: 50%; width: '. ($tablewidth*2) .'px;';
$styles['.trial_builder.list + #image_chooser'] = 'margin-left: ' . ($tablewidth*2+20) * $exp_data['versions'] . 'px;';

/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<div class="toolbar">
	<div class="toolbar-line">
		<button id="save-trials" class='.ui-state-active'>Save Adaptation Trials</button>
		<span style="padding-left: 1em;">Experiment:</span>
		<button id="start-exp">Start</button>
		<button id="edit-exp">Edit</button>
		<button id="exp-info">Info</button>
		
		<span id="search-bar">
			<input type="text" 
				placeholder="Search for images"
				id="image_search" 
				name="image_search" 
				onchange="showImages(50);"  />
	
			<span id="image_list_toggle">
				<input type="radio" id="list_toggle" name="radio" checked="checked" />
				<label for="list_toggle">List</label>
				<input type="radio" id="image_toggle" name="radio" />
				<label for="image_toggle">Images</label>
			</span>
		</span>
	</div>
	
	<div class="toolbar-line">	
		Versions: <span id="add-delete-version">
			<button id="delete-version">Delete Version</button>
			<button id="add-version">Add Version</button>	
		</span>	
		Images: <span id="add-delete-image">
			<button id="delete-image">Delete Image</button>
			<button id="add-image">Add Image</button>	
		</span>
		<button id="fill-from-list">Fill From List</button>
		Common Path: <span id="common_path"></span>
		
		<span id="images_found"></span>
	</div>
</div>



<div class="trial_builder list">Version <span class="version">1</span>
<br />Name: <span class="editText" id="v1_name"><?= $exp_info[0]['name'] ?></span>
<br />Notes: <span class="editText" id="v1_notes"><?= $exp_info[0]['notes'] ?></span>
<br />Question: <span class="editText" id="v1_question"><?= $exp_info[0]['question'] ?></span>

<?php


$version = 1;

foreach ($trials as $trial) {
	
	if ($trial['version'] != $version) {
		$version = $trial['version'];
		echo '</div><div class="trial_builder">Version <span class="version">' . $version . '</span>';
		echo '<br />Name: <span class="editText" id="v' . $version . '_name">' . $exp_info[$version-1]['name'] . '</span>';
		echo '<br />Notes: <span class="editText" id="v' . $version . '_notes">' . $exp_info[$version-1]['notes'] . '</span>';
		echo '<br />Question: <span class="editText" id="v' . $version . '_question">' . $exp_info[$version-1]['question'] . '</span>';
	}

	echo '<div id="v' . $version . '_trial_' . $trial['trial_n'] . '" class="trial">'. ENDLINE;
	
	echo '	<p>' . $trial['trial_n'] . ': <span id="v' . $version . '_exposure_' . $trial['trial_n'] . '" class="editText" title="Click to edit!">' . $trial['exposure' ] . '</span>ms</p>' . ENDLINE;
	
	if (!empty($trial['limg'])) { 
		$imagelist[] = $trial['limg']; // add image to master image list
		echo '<img class="trialimg" id="v' . $version . '_limg_' . $trial['trial_n'] . '" 
			src="' . $trial['limg'] . '" 
			title="' . $trial['limg'] . '" />' . ENDLINE .
			'<span class="imgname">' . imgname($trial['limg']) . '</span>' . ENDLINE; 
	}
	if (!empty($trial['cimg'])) { 
		$imagelist[] = $trial['cimg']; // add image to master image list
		echo '<img class="trialimg" id="v' . $version . '_cimg_' . $trial['trial_n'] . '" 
			src="' . $trial['cimg'] . '" 
			title="' . $trial['cimg'] . '" />' . ENDLINE .
			'<span class="imgname">' . imgname($trial['cimg']) . '</span>' . ENDLINE; 
	}
	if (!empty($trial['rimg'])) { 
		$imagelist[] = $trial['rimg']; // add image to master image list
		echo '<img class="trialimg" id="v' . $version . '_rimg_' . $trial['trial_n'] . '" 
			src="' . $trial['rimg'] . '" 
			title="' . $trial['rimg'] . '" />' . ENDLINE .
			'<span class="imgname">' . imgname($trial['rimg']) . '</span>' . ENDLINE; 
	}
	echo '</div>' . ENDLINE;
}

?>

</div>

<div id="image_chooser">
	<ul id="img_list"></ul>
</div>

<div id="dialog-form-fill" class="modal" title="Fill fields from list">
	<p>Paste an Excel column or type in a list of exposure times. If you only type one exposure time, all times will be changed to that one.</p>
	<textarea id="listfill"></textarea>
</div>

<div id='dialog-saver' class="modal" title='Trial Saver'></div>

<div id="help" title="Trial Builder Help">
	<h1>Searching for Images</h1>
	<ul>
		<li>Type into the search box and press Return to search the image database.</li>
		<li>Both the full image name (e.g., <kbd>/images/stimuli/imagesets/canada2003/sexdim/female/fem/white</kbd>) and the description (if set) are searched.</li>
		<li>Use <kbd>AND</kbd> or <kbd>OR</kbd> to search multiple terms (e.g., <kbd>composites AND (1ns OR sss)</kbd>).</li>
		<li>Use <kbd>!</kbd> to remove items with a term (e.g., <kbd>kdef AND !profile</kbd>).</li>
	</ul>
	
	<h1>Setting the Trials</h1>
	<ul>
		<li>Double-click an image in the trial builder on the left to fill all following images from the list on the right.</li>
		<li>You can set individual images by dragging images or image names from the list on the right.</li>
	</ul>
</div>


<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>

	function imgname(src) {
		var name = src.replace('/images/stimuli', '');
		name = name.replace('.jpg', '');
		name = name.replace('.ogg', '');
		name = name.replace('.mp3', '');
		return name;
	}

	$(function() {
		
		$(".trial_builder img.trialimg, .trial_builder span.imgname").droppable({
			tolerance: "pointer",
			hoverClass: "drop_hover",
			drop: function( event, ui ) {
				if ($(this).hasClass('imgname')) {
					var theImg = $(this).prev('img.trialimg');
					var theSpan = $(this);
				} else {
					var theSpan = $(this).next('span.imgname');
					var theImg = $(this);
				}
			
				var theSrc = $(ui.draggable).attr('title');
				
				theImg.attr({
					'src': theSrc,
					'title': theSrc
				});
				theSpan.html(imgname(theSrc));
			}
		});
		
		$(".trial_builder img.trialimg, .trial_builder span.imgname").dblclick( function() {
			if ($(this).hasClass('imgname')) {
				var coldata = $(this).prev('img.trialimg').attr('id').split('img_');
			} else {
				var coldata = $(this).attr('id').split('img_');
			}

			fill(coldata[0], coldata[1]);
		});
		
		// resize lists to window height
		$(window).resize(resizeContent);
		resizeContent();
		
		// add common path to common_path
		var common_path = "<?= str_replace('/images/stimuli', '', common_path($imagelist)) ?>";
		$("#common_path").html(common_path);
		

		// add functions to buttons
		$( "#image_list_toggle" ).buttonset();
		$( "#list_toggle" ).click(function() { toggleImages(0); });
		$( "#image_toggle" ).click(function() { toggleImages(1); });
		
		$('#start-exp').button().click( function() {
			window.location = '/exp?id=<?= $exp_id ?>';
		});
		
		$('#edit-exp').button().click( function() {
			window.location = '/res/exp/builder?id=<?= $exp_id ?>';
		});
		
		$( "#exp-info" ).button().click( function() {
			window.location.href='/res/exp/info?id=<?= $exp_id ?>'; 
		});
		
		$( "#delete-version" ).button({text: false, icons: { primary: 'ui-icon-minusthick' }}).click( function() {
			$('.trial_builder:last').remove();
		});
		
		$( "#add-version" ).button({text: false, icons: { primary: 'ui-icon-plusthick' }}).click( function() {
			var newVersion = $('.trial_builder:last').clone(true);
			var n = parseInt(newVersion.find('.version').text());
			newVersion.find('.version').text(n+1)
			newVersion.find('*[id*="v' + n + '_"]').each( function() {
				var newID = $(this).attr('id').replace('v' + n + '_', 'v' + (n+1) + '_');
				$(this).attr('id', newID);
			});
			newVersion.find('*[name*="v' + n + '_"]').each( function() {
				var newID = $(this).attr('name').replace('v' + n + '_', 'v' + (n+1) + '_');
				$(this).attr('name', newID);
			});
			$('.trial_builder:last').after(newVersion);
		});
		
		$('#add-delete-version').buttonset();
		
		$( "#delete-image" ).button({text: false, icons: { primary: 'ui-icon-minusthick' }}).click( function() {
			$('.trial_builder').each( function() {
				if ($(this).find('.trial').length > 1) {
					$(this).find('.trial:last').remove();
				}
			});
		});
		
		$( "#add-image" ).button({text: false, icons: { primary: 'ui-icon-plusthick' }}).click( function() {
			$('.trial_builder').each( function() {
				var newImg = $(this).find('.trial:last').clone(true);
				var n = $(this).find('.trial').length;
				newImg.attr('id', newImg.attr('id').replace('trial_' + n, 'trial_' + (n+1)));
				newImg.find('*[id$="_' + n + '"]').each( function() {
					var newID = $(this).attr('id').replace('_' + n, '_' + (n+1));
					$(this).attr('id', newID);
				});
				newImg.find('*[name$="_' + n + '"]').each( function() {
					var newID = $(this).attr('name').replace('_' + n, '_' + (n+1));
					$(this).attr('name', newID);
				});
				newImg.html(newImg.html().replace(n+': ', (n+1) + ': '));
				
				$(this).find('.trial:last').after(newImg);
			});
		});
		
		$('#add-delete-image').buttonset();
		
		$( "#save-trials" ).button().click(function() {
			var dataArray = {};
	
			$('.trial_builder').each( function() {
				var v = $(this).find('.version').text();
				dataArray[v] = {};
				dataArray[v]['name'] = $('#i_v' + v + '_name').val();
				dataArray[v]['notes'] = $('#i_v' + v + '_notes').val();
				dataArray[v]['question'] = $('#i_v' + v + '_question').val();
				dataArray[v]['trials'] = {};
				
				$(this).find('div.trial').each( function() {
					var n = this.id.replace('v' + v + '_trial_', '');			
					dataArray[v]['trials'][n] = {};
					
					dataArray[v]['trials'][n]['exposure'] = $('#v' + v + '_exposure_' + n).html();
					if ($('#v' + v + '_limg_' + n).length > 0) dataArray[v]['trials'][n]['limg'] = $('#v' + v + '_limg_' + n).attr('title');
					if ($('#v' + v + '_cimg_' + n).length > 0) dataArray[v]['trials'][n]['cimg'] = $('#v' + v + '_cimg_' + n).attr('title');
					if ($('#v' + v + '_rimg_' + n).length > 0) dataArray[v]['trials'][n]['rimg'] = $('#v' + v + '_rimg_' + n).attr('title');
				});
			});
			
			//$('<div />').html(dataArray.toSource()).dialog({width: 1200});
				
			$.ajax({
				type: 'POST',
				url: './adapt?save&exp_id=<?= $exp_id ?>',
				data: dataArray,
				success: function(response) {
					$('#dialog-saver').html(response).dialog('open');
				}
			});
		});
		
		$('#dialog-saver').dialog({
			autoOpen: false,
			show: "scale",
			hide: "scale",
			height: 200,
			width: 350,
			modal: true,
			buttons: {
				'Start Exp': function() { window.location = '/exp?id=<?= $exp_id ?>'; },
				'Exp Info': function() { window.location = '/res/exp/info?id=<?= $exp_id ?>'; },
			}
		});
		
		$( "#fill-from-list" ).button().click(function() { $( "#dialog-form-fill" ).dialog( "open" ); });	
		$( "#dialog-form-fill" ).dialog({
			autoOpen: false,
			show: "scale",
			hide: "scale",
			height: 400,
			width: 400,
			modal: true,
			buttons: {
				"Fill Exposure": function() {
					var rows = $('#listfill').val().split("\n");
					var i = 0;
					
					$('.trial_builder *[id*="_exposure_"]').each( function() {
						if (rows.length <= i ) { i = 0; }
						$(this).html(rows[i]);
						i++
					});
					$( this ).dialog( "close" );
				},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			},
		});
	});
	
	function fill(column, start) {
		// auto-fill image columns with selected images
	
		var i = 1;
		// get list of images
		var imagelist = [];
		$('#img_list li').each( function() {
			imagelist[i] = $(this).attr('title');
			i++
		});
		
		if (i == 1) {
			$('<div />').html('Search for images by typing part of the image folder name into the search box above.').dialog('open');
			return false;
		}
		
		// add images to the trial builder
		i = 1;
		var lastTrial = $('.trial_builder:first div').length;
		
		for (n = start; n <= lastTrial; n++) {
			var theimage = $('#' + column + 'img_' + n);
			
			if (theimage.length == 0) return false; // stop iterating when trials are done
			if (i >= imagelist.length) i = 1; // restart image list if more trials remain
			
			theimage.attr('src', imagelist[i]);
			theimage.attr('title', imagelist[i]);
			theimage.next('span.imgname').html(imgname(imagelist[i]));
			
			i++
		}		
	}
	
	function resizeContent() {
		var content_height = $(window).height() - $('.trial_builder').offset().top - $('#footer').height()-30;
		$('.trial_builder').height(content_height);
		$('#image_chooser').height(content_height);
	}
	
	function addDraggable() {
		$('#img_list li').draggable({
			helper: "clone",
			cursorAt: { top: 0, left: 0 }
		});
	}
	
	var imgToggle = 0;
	function toggleImages(t) {
		// if t == 0, turn off images and turn on list view in image chooser
		// if t == 1, turn off list view and turn on images in image chooser
		
		imgToggle = t;
		
		// make current option unclickable so you dont keep searching
		if (imgToggle == 0) {
			$('#image_toggle').html("<a href='javascript:toggleImages(1);'>images</a>");
			$('#list_toggle').html('list');
			$('.trial_builder').addClass('list');
		} else {
			$('#list_toggle').html("<a href='javascript:toggleImages(0);'>list</a>");
			$('#image_toggle').html('images');
			$('.trial_builder').removeClass('list');
		}
		
		showImages(50);
	}

	function showImages(max_images) {
		// exit if no search text is found
		if ($('#image_search').val() == "") {
			return false;
		}
	
		// retrieve image list asynchronously
		$.ajax({
			url: 'trials?search', 
			type: 'POST',
			data: $('#image_search').serialize(),
			success: function(resp) {
				if (resp.substr(0,5) == "error") {
					alert(resp);
				} else {
					$('#img_list').empty();
					var id_path = resp.split(";");
					var len = id_path.length;
					var plus = '';
					if (len == 2000) { plus = '+'; }
					
					$('#images_found').html(len + plus + '&nbsp;images&nbsp;found');
					
					for (var i = 0; i<len; ++i ){
						var img = id_path[i].split(":");
						if (imgToggle == 1) {
							$('#img_list').append('<li id="img' + img[0] + '" title="' + img[1] + '"><img src="' + img[1] + '" /></li>');
							
							if (i >= max_images) {
								$('#img_list').append('<a href="javascript:showImages('+(max_images+50)+')">View more...</a>');
								break;
								
							}
						} else {
							var shortname = img[1].replace('/images/stimuli', '');
							$('#img_list').append('<li id="img' + img[0] + '" title="' + img[1] + '">' + shortname + '</li>');
						}
					}
					
					// make sure each li displays correctly and is draggable
					if (imgToggle == 1) $('#img_list li').css('display','inline');
					if (imgToggle == 0) {
						$('#img_list li').css('display','block');
						$('#img_list li:odd').addClass('odd');
						$('#img_list li:even').addClass('even');
					}
					addDraggable();
					$('#img_list').selectable();
				}
			}
		});	
		
	}
</script>

<!-- enable instant edits -->
<script src="/include/js/instantedit.js" type="text/javascript"></script>

<?php

$page->displayFooter();

?>