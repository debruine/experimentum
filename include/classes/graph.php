<?php

function graph_round($limit, $end = 'high') {

	if ($end == 'low' && $limit >= 0) { return 0; exit; }
	if ($end == 'high' && $limit <= 0) { return 0; exit; }
	
	// get rounding factor depending on size of limit
	$abs = abs($limit);
	if ($abs < 1) {
		$factor = 1;
	} elseif ($abs < 10) {
		$factor = 0;
	} elseif ($abs < 100) {
		$factor = -1;
	} else {
		$factor = -2;
	}
	
	$round = round($limit, $factor);

	if ($end == 'low') {
		if ($round < $limit) {
			return $round; exit;
		} else {
			return $round - pow(10, -1*$factor); exit;
		} 
	} else {
		if ($round > $limit) {
			return $round; exit;
		} else {
			return $round + pow(10, -1*$factor); exit;
		}
	}
}

	class graph {
		// generate a graph from data
		public $variables = array();	// array of variables with category names as keys
		public $xlabel = '';		// x-axis label
		public $ylabel = '';		// y-axis label
		public $width = 600;		// width of the graph
		public $height = 400;		// height of the graph
		public $y_upperlimit;		// maximum y-value, defaults to 105% of maximum data
		public $y_lowerlimit = 0;	// minimum y-value
		public $xcross = 0;			// where the x-axis crosses the y-axis
		public $barborders = '3px solid white';			// borders around data bars
		public $axis_style = '3px solid white';			// style for axes
		public $barcolor = array('#C00', '#CC7800','#FC0', '#060', '#06C', '#00C', '#609');
		public $bgcolor = 'hsl(200,25%,70%)';
		public $textcolor = 'black';
		public $title;				// optional title of the graph
		public $caption;			// option caption of the graph
		
		function __construct($v) {
			$this->set_variables($v);
		}
		
		function set_variables($x = array()) { 
			foreach($x as $key => $value) {
				if (!is_numeric($value)) unset($x[$key]);
			}
			$this->variables = $x;
		}
		function get_variables() { return $this->variables; }
		function set_xlabel($x) { $this->xlabel = $x; }
		function get_xlabel() { return $this->xlabel; }	
		function set_ylabel($x) { $this->ylabel = $x; }
		function get_ylabel() { return $this->ylabel; }
		function set_labels($x, $y) { 
			$this->xlabel = $x;
			$this->ylabel = $y; 
		}
		function set_width($x) { $this->width = $x; }
		function get_width() { return $this->width; }	
		function set_height($x) { $this->height = $x; }
		function get_height() { return $this->height; }
		function set_dimensions($x, $y) { 
			$this->set_width($x);
			$this->set_height($y); 
		}
		function set_y_upperlimit($x) { $this->y_upperlimit = $x; }
		function get_y_upperlimit() { return $this->y_upperlimit; }
		function set_y_lowerlimit($x) { $this->y_lowerlimit = $x; }
		function get_y_lowerlimit() { return $this->y_lowerlimit; }
		function set_y_limits($x, $y) { 
			$this->set_y_upperlimit(max($x, $y));
			$this->set_y_lowerlimit(min($x, $y));
		}
		function set_xcross($x) { $this->xcross = $x; }
		function get_xcross() { return $this->xcross; }
		function set_barborders($x) { $this->barborders = $x; }
		function get_barborders() { return $this->barborders; }
		function set_axis_style($x) { $this->axis_style = $x; }
		function get_axis_style() { return $this->axis_style; }
		function set_barcolor($x) { $this->barcolor = $x; }
		function get_barcolor() { return $this->barcolor; }
		function set_bgcolor($x) { $this->bgcolor = $x; }
		function get_bgcolor() { return $this->bgcolor; }
		function set_textcolor($x) { $this->textcolor = $x; }
		function get_textcolor() { return $this->textcolor; }
		function set_colors($bg, $text, $bars) {
			$this->set_bgcolor($bg);
			$this->set_textcolor($text);
			$this->set_barcolor($bars);
		}
		function set_title($x) { $this->title = $x; }
		function get_title() { return $this->title; }
		function set_caption($x) { $this->caption = $x; }
		function get_caption() { return $this->caption; }
	}
	
	class bargraph extends graph {
		public $yaxis_span;
		public $yaxis_height;
		public $xlabel_width;
		public $xlabel_height;
		public $yaxis_width = 3;
	
		function drawGraph() {
			$max = max($this->variables);
			$min = min($this->variables);
			$this->calcDimensions($min, $max);
			
			// set graph style
			$graph = $this->graphStyle();

			$graph .= $this->get_yaxis();
			
			$graph .= $this->get_xvalues();
			
			$graph .= $this->get_xaxis();
			
			return $graph;
		}
		
		function get_yaxis() {
			// add graph table
			$graph = '<table class="graph">' . ENDLINE;
			
			// optional title
			if (!empty($this->title)) {
				$graph .= '	<tr class="title"><td colspan="100">' . $this->title . '</td></tr>' . ENDLINE;
			}
			
			// y-axis labels
			$graph .= '	<tr class="posbars">' . ENDLINE;
			$graph .= '		<td class="ylabel" rowspan="2">' . ENDLINE;
			
			// y-axis scale
			$graph .= '			<div class="yaxis">' . ENDLINE;
			$graph .= '				<div class="y_top">' . $this->y_upperlimit . '</div>' . ENDLINE;
			if ($this->xcross > $this->y_lowerlimit) {
				$graph .= '				<div class="y_xcross">' . $this->xcross . '</div>' . ENDLINE;
			}
			$graph .= '				<div class="y_bottom">' . $this->y_lowerlimit . '</div>' . ENDLINE;
			
			if (!empty($this->ylabel)) {
				$graph .= '				<span class="verticallabel">' . $this->ylabel . '</span>' . ENDLINE;
			}
			$graph .= '			</div>' . ENDLINE;
			$graph .= '		</td>' . ENDLINE;
			
			return $graph;
		}
		
		function get_graphcolor($key = '') {
			// set bar colour
			if (is_array($this->barcolor) && array_key_exists($key, $this->barcolor)) {
				$graphcolor = $this->barcolor[$key];
			} elseif (is_array($this->barcolor)) {
				// assign graph colour to current element in barcolor
				$graphcolor = current($this->barcolor);
				// increment barcolor array or reset if done
				if (next($this->barcolor) === false) reset($this->barcolor);
			} elseif (in_array($this->barcolor, range(0,7))) {
				$graphcolor = $this->barcolor;
			}
			
			return $graphcolor;
		}
		
		function get_xvalues() {
			$graph = '';
			// values above xcross
			foreach ($this->variables as $key => $value) {
				// check that value is numeric
				if (is_numeric($value)) {
					$graphcolor = $this->get_graphcolor($key);
					
					if ($value >= $this->xcross) {
					// set height of bar
						$h = round( (($value-$this->xcross)/$this->yaxis_span) * $this->yaxis_height );
						
						// display bar cell
						$graph .= '		<td>';
						$graph .= '<img src="/images/stuff/pixel.gif" style="height:' . $h . 'px;' .
									'background-color:' . $graphcolor . '" title="' . apa_round($value) . '" /></td>' . ENDLINE;
					} else {
						$graph .= '		<td></td>' . ENDLINE;
					}
				}
			}
			$graph .= '	</tr>' . ENDLINE;
			
			
			// values below xcross
			$graph .= '	<tr class="negbars">' . ENDLINE;
			
			if(is_array($this->barcolor)) reset($this->barcolor);
			foreach ($this->variables as $key => $value) {
				// check that value is numeric
				if (is_numeric($value)) {
					$graphcolor = $this->get_graphcolor($key);
					
					if ($value < $this->xcross) {
					// set height of bar
						$h = round( (($this->xcross - $value)/$this->yaxis_span) * $this->yaxis_height );
						
						// display bar cell
						$graph .= '		<td>';
						$graph .= '<img src="/images/stuff/pixel.gif" style="height:' . $h . 'px;' .
									'background-color:' . $graphcolor . '" title="' . apa_round($value) . '" /></td>' . ENDLINE;
					} else {
						$graph .= '		<td></td>' . ENDLINE;
					}
				}
			}
			$graph .= '	</tr>' . ENDLINE;
			
			return $graph;
		}
		
		function get_xaxis() {
			// label row
			$graph = '	<tr class="xlabels">' . ENDLINE;
			$graph .= '		<td></td>' . ENDLINE;
			foreach ($this->variables as $label => $value) {
				if (is_array($value)) $value = '';
			
				if (substr($label, 0, 5) == 'empty') {
					$graph .= '		<td title="' . $value . '"></td>' . ENDLINE;
				} else {
					$graph .= '		<td title="' . $value . '">' . $label . '</td>' . ENDLINE;
				}
			}
			$graph .= '	</tr>' . ENDLINE;
			
			if (!empty($this->xlabel)) {
				$graph .= '	<tr class="xlabel"><td></td><td colspan="100">' . $this->xlabel . '</td></tr>' . ENDLINE;
			}
			
			$graph .= '</table>' . ENDTAG;
			
			return $graph;
		}
		
		function calcDimensions($min, $max) {
			// calculate y-axis limits
			if ($this->y_upperlimit < $max) $this->y_upperlimit = graph_round($max, 'high'); 
			if ($this->y_lowerlimit > $min) $this->y_lowerlimit = graph_round($min, 'low');
			
			// calculate dimensions
			$this->yaxis_span = $this->y_upperlimit - $this->y_lowerlimit;
			
			$this->ylabel_width = 40;
			$this->xlabel_height = (empty($this->xlabel)) ? 20 : 40;
			$this->title_height = (empty($this->title)) ? 0 : 20;
			
			$this->yaxis_height = $this->height - $this->xlabel_height - $this->title_height - 20;
			$this->x_width = $this->width - $this->ylabel_width - 20;
			
			if ($this->y_upperlimit < $this->xcross) {
				// all values are above xcross
				$this->yaxis_height_pos = 0;
				$this->yaxis_height_neg = $this->yaxis_height;
			} else if ($this->y_lowerlimit < $this->xcross) {
				// values are above and below xcross
				$this->yaxis_height_pos = round ( $this->yaxis_height * (($this->y_upperlimit - $this->xcross) / $this->yaxis_span) );
				$this->yaxis_height_neg = round ( $this->yaxis_height * (($this->xcross - $this->y_lowerlimit) / $this->yaxis_span) );
			} else {
				// all value are above xcross
				$this->yaxis_height_pos = $this->yaxis_height;
				$this->yaxis_height_neg = 0;
			}
			$this->bar_width = round(($this->width - $this->yaxis_width)/count($this->variables));
		}
		
		function graphStyle() {
			// set classes
			$style =  '<style type="text/css">' 									.ENDLINE;
			$style .= '	.graph {' 													.ENDLINE;
			$style .= '		background-color:' 	. $this->bgcolor . ';' 				.ENDLINE;
			$style .= '		color:' 			. $this->textcolor . ';' 			.ENDLINE;
			$style .= '		width:' 			. $this->width . 'px;' 				.ENDLINE;
			$style .= '	}' 															.ENDLINE;
			$style .= '	.legend {' 													.ENDLINE;
			$style .= '		background-color:' 	. $this->bgcolor . ';' 				.ENDLINE;
			$style .= '	}' 															.ENDLINE;
			$style .= '	.posbars td, .negbars td {' 								.ENDLINE;
			$style .= '		width:' 			. $this->bar_width . 'px;' 			.ENDLINE;
			$style .= '	}' 															.ENDLINE;
			$style .= '	.posbars td {' 												.ENDLINE;
			$style .= '		height:' 			. $this->yaxis_height_pos . 'px;' 	.ENDLINE;
			$style .= '	}' 															.ENDLINE;
			$style .= '	.negbars td {' 												.ENDLINE;
			$style .= '		height:' 			. $this->yaxis_height_neg . 'px;' 	.ENDLINE;
			$style .= '	}' 															.ENDLINE;
			$style .= '	.ylabel, .yaxis {' 											.ENDLINE;
			$style .= '		height:' 			. $this->yaxis_height . 'px;' 		.ENDLINE;
			$style .= '		width:' 			. $this->ylabel_width . 'px;' 		.ENDLINE;
			$style .= '	}' 															.ENDLINE;
			$style .= '	.verticallabel {' 											.ENDLINE;
			$style .= '		width:' 			. $this->yaxis_height . 'px;' 		.ENDLINE;
			$style .= '	}' 															.ENDLINE;
			$style .= '	.y_xcross {' 												.ENDLINE;
			$style .= '		bottom:' 			. $this->yaxis_height_neg . 'px;' 	.ENDLINE;
			$style .= '	}' 															.ENDLINE;												
			
			// border and axis styles
			$style .= '	.posbars img, .negbars img {' 								.ENDLINE;
			$style .= '		border:' 			. $this->barborders . ';' 			.ENDLINE;
			$style .= '	}' 															.ENDLINE;
			$style .= '	.posbars td {' 												.ENDLINE;
			$style .= '		border-bottom:' 	. $this->axis_style . ';' 			.ENDLINE;
			$style .= '	}' 															.ENDLINE;
			$style .= '	.ylabel {' 													.ENDLINE;
			$style .= '		border-right:' 		. $this->axis_style . ';' 			.ENDLINE;
			$style .= '		border-bottom:none !important;' 						.ENDLINE;
			$style .= '	}' 															.ENDLINE;
			$style .= '	.posbars img {' 											.ENDLINE;
			$style .= '		border-bottom:none;' 									.ENDLINE;
			$style .= '	}' 															.ENDLINE;
			$style .= '	.negbars img {' 											.ENDLINE;
			$style .= '		border-top:none;' 										.ENDLINE;
			$style .= '	}' 															.ENDLINE;

			$style .= '</style>' . ENDTAG;
			
			return $style;
		}
	}
	
	class factorialBargraph extends bargraph {
		function __construct() {}
	
		function set_variables($x = array()) { 
			$group = $x['factorial_group'];
			
			foreach($x as $key => $value) {
				if (!is_numeric($value)) {
					unset($x[$key]);
				} else {
					$this->variables[$key][$group] = $value;
				}
			}
			
		}
		
		function drawGraph() {
			foreach ($this->variables as $v) {
				$max_array[] = max($v);
				$min_array[] = min($v);
			}
			$max = max($max_array);
			$min = min($min_array);
			$this->calcDimensions($min, $max);
			
			// set graph style
			$graph = $this->graphStyle();
			
			$graph .= $this->get_legend();

			$graph .= $this->get_yaxis();
			
			$graph .= $this->get_xvalues();
			
			$graph .= $this->get_xaxis();
			
			return $graph;
		}
		
		function get_xvalues() {
			$graph = '';
			// values above xcross
			foreach ($this->variables as $key => $values) {
				$w = round((75 - 2*(count($values)-1)) / count($values), 1);
				$graph .= '		<td>';
				
				foreach ($values as $group => $value) {
					// check that value is numeric
					if (is_numeric($value)) {
						$graphcolor = $this->get_graphcolor($group);
						
						if ($value >= $this->xcross) {
						// set height of bar
							$h = round( (($value-$this->xcross)/$this->yaxis_span) * $this->yaxis_height );
							
							// display bar
							$graph .= '<img src="/images/stuff/pixel.gif" style="height:' . $h . 'px; width:' . $w . 'px; ' .
										'background-color:' . $graphcolor . '" title="' . $group . ' ' . apa_round($value) . '" />' . ENDLINE;
						} else {
							// placeholder bar
							$graph .= '<img src="/images/stuff/pixel.gif" style="width:' . $w . 'px;" class="placeholder" />' . ENDLINE;
						}
					}
				}
				$graph .= '</td>' . ENDLINE;
			}
			$graph .= '	</tr>' . ENDLINE;
			
			
			// values below xcross
			$graph .= '	<tr class="negbars">' . ENDLINE;
			
			if(is_array($this->barcolor)) reset($this->barcolor);
			foreach ($this->variables as $key => $values) {
				$w = round((75 - 2*(count($values)-1)) / count($values), 1);
				$graph .= '		<td>';
				
				foreach ($values as $group => $value) {
					// check that value is numeric
					if (is_numeric($value)) {
						$graphcolor = $this->get_graphcolor($group);						
						if ($value < $this->xcross) {
						// set height of bar
							$h = round( (($this->xcross - $value)/$this->yaxis_span) * $this->yaxis_height );
							
							// display bar
							$graph .= '<img src="/images/stuff/pixel.gif" style="height:' . $h . 'px; width:' . $w . 'px; ' .
										'background-color:' . $graphcolor . '" title="' . $group . ' '  . apa_round($value) . '" />' . ENDLINE;
						} else {
							// placeholder bar
							$graph .= '<img src="/images/stuff/pixel.gif" style="width:' . $w . 'px;" class="placeholder" />' . ENDLINE;
						}
					}
				}
				$graph .= '</td>' . ENDLINE;
			}
			$graph .= '	</tr>' . ENDLINE;
			
			return $graph;
		}
		
		function get_legend() {
			$graph  = '<ul class="legend">' . ENDLINE;
			$keys = array_keys($this->variables);
			$vars = $this->variables[$keys[0]];
			foreach ($vars as $group => $value) {
				$color = $this->get_graphcolor($group);
				$graph .= "<li style='color:{$color};'>{$group}</li>" . ENDLINE;
			}
			$graph .= '</ul>' . ENDLINE;
			
			return $graph;
		}
	}
?>