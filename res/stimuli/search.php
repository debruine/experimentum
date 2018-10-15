<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/include/main_func.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/include/classes/quest.php';
auth(array('student', 'researcher', 'admin'));

$title = array(
	'/res/' => 'Researchers',
	'/res/stimuli/' => 'Stimuli',
	'' => 'Search'
);

$styles = array( 
	'#img_list li' => 'padding:3px; margin:0; text-overflow: ellipsis-word;',
	'#img_list img' => 'height: 100px; box-shadow: 2px 2px 4px rgba(0,0,0,.5); border: 1px solid ' . THEME,
	'#img_list img:hover' => 'border: 1px solid red;',
	'#img_list img:active' => 'box-shadow: 1px 1px 2px rgba(0,0,0,.5);',
	'#image_search' => 'width: 300px;',
	'.img_column' => 'width: 80px;',
	'#image_toggle a, #list_toggle a' => 'color: #999;',
	'#images_found' => 'float: right; padding-left: 1em;',
	'#control_panel' => 'padding-bottom: .5em;',
	'#image_inspector' => 'width: 200px;',
	'#image_description' => 'width: 100%;',
	'#feature_image, #feature_audio' => 'width: 200px; border: 1px solid ' . THEME,
	"#image_inspector label" => "float: left; clear: left; width: 6em; text-align: right; ",
	"#image_inspector span" => 'margin-left: .5em',
	'#image_path' => 'font-size: 80%;',
	'#image_table tr' => 'background-color: transparent !important;',
	'audio' => 'width: 100px; height: 30px; padding-top: 5px;'
);

/****************************************************/
/* !Get image list */
/****************************************************/

if (array_key_exists('search', $_GET)) {
	$searches = mysql_real_escape_string($_GET['search']); // clean up search string
	
	$search_strings = preg_split("/( AND | OR |\(|\))/", $searches);
	
	$search_terms = array();
	foreach($search_strings as $string) {
		$term = trim($string);
		if (!empty($term)) {
			if (substr($term, 0, 1) =='!') {  // negate a term
				$search_terms[$term] = '(!LOCATE("' . substr($term,1) . '", path))';
			} else {
				$search_terms[$term] = '(LOCATE("' . $term . '", path) OR LOCATE("' . $term . '", description))';
			}
		}
	}
	
	$s = $searches;
	foreach ($search_terms as $term => $locate) {
		$s = str_replace($term, $locate, $s);
	}

	$query = new myQuery('SELECT id, CONCAT(id,":",path,":",type) as path FROM stimuli WHERE ' . $s . ' ORDER BY stimuli.path LIMIT 2000');
	$images = $query->get_assoc(false, 'id', 'path');
	
	// echo 'error ' . $query->get_query(); // debug query
	echo implode(';', $images);
	exit;
} elseif (isset($_GET['id'])) {
	$query = new myQuery('SELECT path, imageset, description, sex, transform, percent, type, race,
		GROUP_CONCAT(DISTINCT exp_id SEPARATOR ",") as exps 
		FROM stimuli 
		LEFT JOIN trial ON (left_img=stimuli.id OR center_img=stimuli.id OR right_img=stimuli.id)
		WHERE stimuli.id=' . intval($_GET['id']) . ' GROUP BY NULL');
	$data = $query->get_one_array();
	
	if (is_array($data)) {
		echo implode(';^;', $data);
	} else {
		echo $query->get_query();
	}
	exit;
}

/****************************************************/
/* !Display Page */
/***************************************************/

$page = new page($title);
$page->set_menu(false);

$page->displayHead($styles);
$page->displayBody();

?>

<div id="control_panel" class="toolbar">
		
	Search: <input type="text" 
		id="image_search" 
		onchange="showImages(this.value,50);" 
		title="Use &ldquo;AND&rdquo; or &ldquo;OR&rdquo; (all caps) to search multiple terms (e.g., &ldquo;composites AND (1ns OR sss)&rdquo;). Use &ldquo;!&rdquo; to remove items with a term (e.g., &ldquo;kdef AND !profile&rdquo;)." />

	<span id="image_list_toggle">
		<input type="radio" id="list_toggle" name="radio" checked="checked" />
		<label for="list_toggle">List</label>
		<input type="radio" id="image_toggle" name="radio" />
		<label for="image_toggle">Images</label>
	</span>
	
	<button id='search-button'>Search</button>
	
	<span id="images_found"></span>
	
	<p class='note fullwidth'>Use &ldquo;AND&rdquo; or &ldquo;OR&rdquo; (all caps) to search multiple terms (e.g., &ldquo;composites AND (1ns OR sss)&rdquo;). Use &ldquo;!&rdquo; to remove items with a term (e.g., &ldquo;kdef AND !profile&rdquo;).</p>
	
</div>


<table id="image_table"><tr>
<td id="image_chooser">
	<ul id="img_list"></ul>
</td>

<td id="image_inspector"><span id="image_path"></span><br />
	<img id="feature_image" />
	<audio id="feature_audio" controls="controls" src=""></audio>
	<br />
	<label for="image_imageset">Imageset:</label> 	<span id="image_imageset"></span><br />
	<label for="image_sex">Sex:</label> 			<span id="image_sex"></span><br />
	<label for="image_transform">Transform:</label> <span id="image_transform"></span><br />
	<label for="image_percent">Percent:</label> 	<span id="image_percent"></span><br />
	<label for="image_type">Type:</label> 			<span id="image_type"></span><br />
	<label for="image_race">Ethnicity:</label> 		<span id="#image_race"></span> <br />
	<textarea id="image_description" title="Description"></textarea>
	In Exp: <span id="image_exps"></span>
</td>
</tr></table>

<!--*************************************************-->
<!-- !Javascripts for this page -->
<!--*************************************************-->

<script>

	$j(function() {
		// add functions to buttons
		$j( "#image_list_toggle" ).buttonset();
		$j( "#list_toggle" ).click(function() { toggleImages(0); });
		$j( "#image_toggle" ).click(function() { toggleImages(1); });

		$j( "#image_inspector" ).hide();
	});

	
	var imgToggle = 0;
	function toggleImages(t) {
		// if t == 0, turn off images and turn on list view in image chooser
		// if t == 1, turn off list view and turn on images in image chooser
		
		imgToggle = t;
		
		// make current option unclickable
		if (imgToggle == 0) {
			$j('#image_toggle').html("<a href='javascript:toggleImages(1);'>images</a>");
			$j('#list_toggle').html('list')
		} else {
			$j('#list_toggle').html("<a href='javascript:toggleImages(0);'>list</a>");
			$j('#image_toggle').html('images');
		}
		
		showImages($j('#image_search').val(), 50);
	}

	function showImages(s, max_images) {
		// exit if no search text is found
		if (s == "") {
			return false;
		}
	
		var url = encodeURI('search?search=' + s);
		
		// retrieve image list asynchronously
		$j.get(url, function(resp) {
				if (resp.substr(0,5) == "error") {
					alert(resp);
				} else {
					$j( "#image_inspector" ).hide();
					$j('#img_list').empty();
					var id_path = resp.split(";");
					var len = id_path.length;
					var plus = '';
					if (len == 2000) { plus = '+'; }
					
					$j('#images_found').html(len + plus + '&nbsp;images&nbsp;found');
					
					for (var i = 0; i<len; ++i ){
						var img = id_path[i].split(":");
						if (imgToggle == 1) {
							if (img[2]=="audio") {
								var shortpath = img[1].split("/");
								$j('#img_list').append('<li id="img' + img[0] + '" title="' + img[1] + '"><audio controls="controls" src="' + img[1] + '.ogg" /></audio> ' + shortpath[(shortpath.length - 1)] + '<br /></li>');
							} else {
								$j('#img_list').append('<li id="img' + img[0] + '" title="' + img[1] + '"><img src="' + img[1] + '" /></li>');
							}
							
							if (i >= max_images) {
								$j('#img_list').append('<a href="javascript:showImages($j(\'#image_search\').val(),'+(max_images+50)+')">View more...</a>');
								break;
								
							}
						} else {
							var shortname = img[1].replace('/images/stimuli', '');
							$j('#img_list').append('<li id="img' + img[0] + '" title="' + img[1] + '"><a>' + shortname + '</a></li>');
						}
					}
					
					// make sure each li displays correctly
					if (imgToggle == 1) $j('#img_list li').css('display','inline');
					if (imgToggle == 0) $j('#img_list li').css('display','block');
					
					// add click function to each image
					$j('#img_list li').click( function() { 
						var id = $j(this).attr('id').replace('img', ''); 
						
						// getimage information
						
						$j.get('search?id=' + id, function(data) { 
							var imagedata = data.split(";^;");
							if (imagedata[6] == "image") {
								$j( "#feature_image" ).attr('src', imagedata[0]).show();
								$j( "#feature_audio" ).hide();
							} else if (imagedata[6] == "audio") {
								$j( "#feature_audio" ).attr('src', imagedata[0]).show();
								$j( "#feature_image" ).hide();
							}
							$j( "#image_path" ).html(imagedata[0]);
							$j( "#image_imageset" ).html(imagedata[1]);
							$j( "#image_description" ).html(imagedata[2]);
							$j( "#image_sex" ).html(imagedata[3]);
							$j( "#image_transform" ).html(imagedata[4]);
							$j( "#image_percent" ).html(imagedata[5]);
							$j( "#image_type" ).html(imagedata[6]);
							$j( "#image_race" ).html(imagedata[7]);
							
							var explist = imagedata[8].split(",");
							$j( "#image_exps" ).empty();
							if (explist.length > 0) {
								for (i=0; i<explist.length; i++) {
									$j( "#image_exps" ).append("<a href='/res/exp/info?id=" + explist[i] + "'>" + explist[i] + "</a>");
									if (i+1 < explist.length) {
										$j( "#image_exps" ).append(", ");
									}
								}
							}

							$j( "#image_inspector" ).show();
						});
						
					});
				}
			});	
		
	}
</script>

<?php

$page->displayFooter();

?>