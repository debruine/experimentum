<?php

/****************************************************
 * Statistical classes
 ***************************************************/
 
// GENERAL FUNCTIONS
	
	function mean($values) {
		// return the mean of an array of values
		$n = count($values);
		if (0 == $n) return false;
		$mean = array_sum($values) / $n;
		
		return $mean;	
	}
	
	function stdev($v1, $v2 = NULL) {
		// returns the SD of an array of values
		
		if (is_null($v2)) {
			// calculate SD for one array
			$values = $v1;
		} else {
			// calculate SD for difference between 2 arrays
			$values = array();
			foreach ($v1 as $k => $v) {
				$values[] = $v1[$k] - $v2[$k];
			}
		}
		
		$n = count($values);
		if (0 == $n) return false;
		
		$mean = mean($values);
		foreach ($values as $x) {
			$d2[] = ($mean - $x) * ($mean - $x);
		}
		$sum_squares = array_sum($d2);
		$stdev = sqrt($sum_squares/($n-1));
		
		return $stdev;
	}
	
	function sterror($values) {
		// returns the standard error of the mean for an array of values
		
		$n = count($values);
		if (0 == $n) return false;
		
		$sd = stdev($values);
		$sterror = $sd / sqrt($n);
		
		return $sterror;
	}
	
	 function z_to_p($myz) {
		// returns the 2-tailed p-value for a z-score
		// code adapted from http://faculty.vassar.edu/lowry/ch6apx.html
		
		$z = abs($myz);
		if($z>5) return 0;
	
		$a = 0.000005383;
		$b = 0.0000488906;
		$c = 0.0000380036;
		$d = 0.0032776263;
		$e = 0.0211410061;
		$f = 0.049867347;
	
		$p = ((((($a*$z+$b)*$z+$c)*$z+$d)*$z+$e)*$z+$f)*$z+1;
	
		$p = pow($p, -16);
	
		return $p;
	}
	
	function r_to_z($r) {
		// Fisher's r to z
		
		$z = .5 * (log((1+$r)/(1-$r)));
		return $z;
	}
	
	function normsinv($y) { 
		// code adapted from http://www.ozgrid.com/forum/showthread.php?t=20261
		if ($y < 1.0e-20) return -5.0; 
		if ($y >= 1.0) return 5.0; 
		$x = 0.0; 
		$incr = $y - 0.5; 
		while (abs($incr) > 0.0000001) { 
			if (abs($incr) < 0.0001 && ($x <= -5.0 || $x >= 5.0)) break; 
			$x += $incr; 
			$tst = normsdist($x); 
			if (($tst > $y && $incr > 0) || ($tst < $y && $incr < 0)) $incr *= -0.5; 
		} 
		return $x; 
	} 
	
	function normsdist($X) {
		// Z-score distribution function used in normsinv
		// code adapted from http://forums.microsoft.com/MSDN/ShowPost.aspx?PostID=531675&SiteID=1
		
		$L = abs($X);
		$K = 1.0 / (1.0 + 0.2316419 * $L);
	
		$a1 = 0.31938153;
		$a2 = -0.356563782;
		$a3 = 1.781477937;
		$a4 = -1.821255978;
		$a5 = 1.330274429;
	
		$dCND = 1.0 - 1.0 / sqrt(2 * pi()) * exp(-$L * $L / 2.0) * ($a1 * $K + $a2 * $K * $K + $a3 * pow($K, 3.0) + $a4 * pow($K, 4.0) + $a5 * pow($K, 5.0));
	
		if ($X < 0) {
			return 1.0 - $dCND;
		} else {
			return $dCND;
		}
	}
	
// STATS CLASSES
	
	class two_vars {
	// generic class for stats dealing with two variables
	// correlations and paired- one- and independent samples t-tests
	
		public $var1 = array();
		public $var2 = array();
		public $vname1;
		public $vname2 = 'Variable 2';
		private $t;
		private $df;
		private $d;
		
		function __construct($v1 = array(), $v2 = array(), $vn1 = 'Variable 1', $vn2 = 'Variable 2') {
			$this->set_var1($v1);
			$this->set_var2($v2);
			$this->set_vname1($vn1);
			$this->set_vname2($vn2);
			
			$this->calculate();
		}
		
		function set_var1($x) { $this->var1 = $x; $this->calculate(); }
		function get_var1() { return $this->var1; }
		function set_var2($x) { $this->var2 = $x; $this->calculate(); }
		function get_var2() { return $this->var2; }
		function set_vname1($x) { $this->vname1 = $x; }
		function get_vname1() { return $this->vname1; }
		function set_vname2($x) { $this->vname2 = $x; }
		function get_vname2() { return $this->vname2; }
		function set_vnames($x1, $x2) {
			$this->set_vname1($x1);
			$this->set_vname2($x2);
		}
		function set_t($x) { $this->t = $x; }
		function get_t() { return $this->t; }
		function set_df($x) { $this->df = $x; }
		function get_df() { return $this->df; }
		function set_d($x) { $this->d = $x; }
		function get_d() { return $this->d; }
		
		function set_vars_from_query($q, $vn1 = 0, $vn2 = 1, $g=null, $g1=null, $g2=null) {
			// get paired data from query
			// $vn1 is the name of the column for condition 1 - defaults to column 0
			// $vn2 is the name of the column for condition 2 - defaults to column 1
			// $g, $g1 and $g2 are placeholders for subclasses
			
			$query = new myQuery($q, true);
			$data = $query->get_array();
			
			if (count($data) == 0) return false;
			
			// check that named vars do exists, 
			// if not, use first and second columns
			$v1 = (array_key_exists($vn1, $data[0])) ? $vn1 : 0;
			$v2 = (array_key_exists($vn2, $data[0])) ? $vn2 : 1;
			
			// delete existing vars and add new data
			$this->var1 = array();
			$this->var2 = array();
			foreach ($data as $d) {
				if (!is_null($d[$v1]) && !is_null($d[$v2])) {
					$this->var1[] = $d[$v1];
					$this->var2[] = $d[$v2];
				}
			}
			
			$this->calculate();
		}
		
		function calculate() { 
			// placeholder function
		}
	
		function StatCom($q, $i, $j, $b) {
			// function needed for calculating student T test
		    $zz = 1; 
			$z = $zz; 
			for ($k=$i; $k<=$j; $k+=2) { 
				$zz = ($zz*$q*$k)/($k-$b); 
				$z += $zz; 
			}
		    return $z;
		}
		
		function get_p($t = NULL, $df = NULL) {
			// returns the p-value for a given t-value and degrees of freedom
			
			// set t and df if not explicitly set
			if (is_null($t)) $t = $this->t;
			if (is_null($df)) $df = $this->df; 
			
			// cancel if df=0 or t or df not set
			if ($df == 0 || is_null($t)) return false;
			
			$PI = pi();
			
			$t = abs($t);
			$w = $t/sqrt($df); 
			$th = atan($w);
		   
			if ($df==1) { return 1-($th/($PI/2)); }
		   
			$sth = sin($th); 
			$cth = cos($th);
			
		    if (($df % 2) == 1) {
		        return 1-($th + $sth * $cth * $this->StatCom($cth*$cth,2,$df-3,-1))/($PI/2);
			} else {
		        return 1-$sth*$this->StatCom($cth*$cth,1,$df-3,-1);
		    }
		}
	}
	
	class paired_t extends two_vars {
		// run a paired-samples t-test 
		
		function calculate() {
			// check that vars have equal numbers and are >1
			$n1 = count($this->var1);
			$n2 = count($this->var2);
			if ($n1 != $n2 || $n1 < 2) { return false; }
				
			// calculate difference scores and squared difference scores
			$d = array();
			$d2 = array();
			foreach($this->var1 as $key => $value) {
				$diff = $this->var1[$key] - $this->var2[$key];
				$d[] = $diff;
				$d2[] = pow($diff, 2);
			}
		
			$sum_d = array_sum($d);
			$sum_squares = array_sum($d2);
			
			$denom = ($sum_squares - (($sum_d * $sum_d)/$n1)) / ($n1 * ($n1-1));
		
			$t = ($denom === 0) ? NULL : ($sum_d/$n1) / sqrt($denom);
			$this->set_t($t);
			$this->set_df($n1 - 1);
			$this->set_d(abs(mean($d)/stdev($d)));
		}
		
		function get_apa_stats() {
			$p = apa_round($this->get_p());
			$pvalue = ($p < .001) ? '< .001' : "= $p";
			
			$m1 = apa_round(mean($this->get_var1()));
			$m2 = apa_round(mean($this->get_var2()));
			$sd1 = apa_round(stdev($this->get_var1()));
			$sd2 = apa_round(stdev($this->get_var2()));
			
			$sd = apa_round(stdev($this->get_var1(), $this->get_var2()));
			
			$stats  = '<i>M</i><sub>' . $this->get_vname1() . '</sub> = ' . $m1 . ', ';
			$stats .= '<i>SD</i><sub>' . $this->get_vname1() . '</sub> = ' . $sd1 . '; ';
			$stats .= '<i>M</i><sub>' . $this->get_vname2() . '</sub> = ' . $m2 . ', ';
			$stats .= '<i>SD</i><sub>' . $this->get_vname2() . '</sub> = ' . $sd2 . '; ';
			$stats .= '<i>M</i><sub>dif</sub> = ' . apa_round($m1-$m2) . ', ';
			$stats .= '<i>SD</i><sub>dif</sub> = ' . $sd . '<br />';
			$stats .= '<i>t</i><sub>' . $this->get_df() . '</sub> = ' . apa_round($this->get_t()) . ', ';
			$stats .= '<i>p</i> ' . $pvalue . ', ';
			$stats .= '<i>d</i> = ' . apa_round($this->get_d());
			
			return $stats;
		}
	}
	
	class one_t extends paired_t {
		public $comparison;
		
		function set_comparison($x) { $this->comparison = $x; }
		function get_comparison() { return $this->comparison; }
		
		function set_vars_from_query($q, $vn1=null, $vn2=null, $g=null, $g1=null, $g2=null) {
			// get one-sample data from query
			// $vn1 is the name of the variable to test
			// $vn2, $g, $g1 and $g2 are placeholders for subclasses
			
			$query = new myQuery($q, true);
			$data = $query->get_array();
			if (count($data) == 0) return false;
			
			// check that named vars do exists, 
			// if not, use first column
			$var = (array_key_exists($vn1, $data[0])) ? $vn1 : 0;
			
			// delete existing vars and add new data
			$this->var1 = array();
			foreach ($data as $d) {
				if (!is_null($d[$var])) $this->var1[] = $d[$var];
			}
			// fill variable 2 with the comparison number
			$this->var2 = array_fill(0, count($this->var1), $this->comparison);
			
			$this->calculate();
		}
		
		function get_apa_stats() {
			$p = apa_round($this->get_p());
			$pvalue = ($p < .001) ? '< .001' : "= $p";
			
			$m1 = apa_round(mean($this->get_var1()));
			$sd = apa_round(stdev($this->get_var1()));
			
			$stats  = '<i>M</i><sub>' . $this->get_vname1() . '</sub> = ' . $m1 . ', ';
			$stats .= 'compared to ' . $this->comparison . '; ';
			$stats .= '<i>M</i><sub>dif</sub> = ' . apa_round($m1-$this->comparison) . ', ';
			$stats .= '<i>SD</i><sub>dif</sub> = ' . $sd . '<br />';
			$stats .= '<i>t</i><sub>' . $this->get_df() . '</sub> = ' . apa_round($this->get_t()) . ', ';
			$stats .= '<i>p</i> ' . $pvalue . ', ';
			$stats .= '<i>d</i> = ' . apa_round($this->get_d());
			
			return $stats;
		}
	}


	class independent_t extends paired_t {
		// run an independent-samples t-test on two columns from a query
		
		function set_vars_from_query($q, $vn1=null, $vn2=null, $g=null, $g1=null, $g2=null) {
			// get independent data from query
			// $vn1 is the name of the variable to test
			// $vn2 is a placeholder
			// $g is the name of the grouping variable
			// $g1 and $g2 are the names of the first and second groups
			
			$query = new myQuery($q, true);
			$data = $query->get_array();
			if (count($data) == 0) return false;
			
			// check that named vars do exists, 
			// if not, use first and second columns
			$var = (array_key_exists($vn1, $data[0])) ? $vn1 : 0;
			$group = (array_key_exists($g, $data[0])) ? $g : 1;
			
			// delete existing vars and add new data
			$this->var1 = array();
			$this->var2 = array();
			foreach ($data as $d) {
				if ($d[$g] == $g1 && !is_null($d[$vn1])) { 
					$this->var1[] = $d[$vn1]; 
				} else if ($d[$g] == $g2 && !is_null($d[$vn1])) { 
					$this->var2[] = $d[$vn1]; 
				}
			}
			
			$this->calculate();
		}
		
		function calculate() {
			// get vars
			$v1 = $this->get_var1();
			$v2 = $this->get_var2();
		
			// check that vars have n>1
			$n1 = count($v1);
			$n2 = count($v2);
			if ($n1 <2 || $n2 < 2) { return false; }
			
			$sum_squares1 = 0;
			foreach ($v1 as $v) {
				$sum_squares1 += ($v * $v);
			}
			$sum_squares2 = 0;
			foreach ($v2 as $v) {
				$sum_squares2 += ($v * $v);
			}

			
			$sum1 = array_sum($v1);
			$sum2 = array_sum($v2);
			$m1 = $sum1/$n1;
			$m2 = $sum2/$n2;
		
			$df = ($n1-1) + ($n2-1);
			
			$x = $sum_squares1 - ($sum1 * $sum1)/$n1;
			$y = $sum_squares2 - ($sum2 * $sum2)/$n2;
			
			$denom = (($x+$y)/$df) * (1/$n1 + 1/$n2);
			
			$t = ($m1-$m2) / sqrt($denom);
			
			$this->set_t($t);
			$this->set_df($df);
			$mean_diff = mean($v1) - mean($v2);
			$mean_sd = (stdev($v1) + stdev($v2))/2;
			$this->set_d(abs($mean_diff)/$mean_sd);
		}
		
		function get_apa_stats() {
			$p = apa_round($this->get_p());
			$pvalue = ($p < .001) ? '< .001' : "= $p";
			
			$m1 = apa_round(mean($this->get_var1()));
			$m2 = apa_round(mean($this->get_var2()));
			
			$sd1 = apa_round(stdev($this->get_var1()));
			$sd2 = apa_round(stdev($this->get_var2()));
			
			$n1 = count($this->get_var1());
			$n2 = count($this->get_var2());
			
			$stats  = '<i>M</i><sub>' . $this->get_vname1() . '</sub> = ' . $m1 . ', ';
			$stats .= '<i>SD</i><sub>' . $this->get_vname1() . '</sub> = ' . $sd1 . ', ';
			$stats .= '<i>N</i><sub>' . $this->get_vname1() . '</sub> = ' . $n1 . '; ';
			$stats .= '<i>M</i><sub>' . $this->get_vname2() . '</sub> = ' . $m2 . ', ';
			$stats .= '<i>SD</i><sub>' . $this->get_vname2() . '</sub> = ' . $sd2 . ', ';
			$stats .= '<i>N</i><sub>' . $this->get_vname2() . '</sub> = ' . $n2 . '<br />';
			$stats .= '<i>t</i><sub>' . $this->get_df() . '</sub> = ' . apa_round($this->get_t()) . ', ';
			$stats .= '<i>p</i> ' . $pvalue . ', ';
			$stats .= '<i>d</i> = ' . apa_round($this->get_d());
			
			return $stats;
		}
	}

	class correlation extends two_vars {
		// correlate two columns from a query
			
		public $r;
		
		function set_r($x) { $this->r = $x; }
		function get_r() { return $this->r; }	
			
		function calculate() {
			$x = $this->var1;
			$y = $this->var2;
			
			// check that vars have n>1
			$n1 = count($x);
			$n2 = count($y);
			if ($n1 <2 || $n2 < 2) { return false; }
				
			$xy = array();
			$x2 = array();
			$y2 = array();
			foreach ($x as $k => $v) {
				$xy[] = $x[$k] * $y[$k];
				$x2[] = pow($x[$k], 2);
				$y2[] = pow($y[$k], 2);
			}
		
			$sumx = array_sum($x);
			$sumy = array_sum($y);
			$sumxy = array_sum($xy);
			$sumx2 = array_sum($x2);
			$sumy2 = array_sum($y2);
			
			$numer = ($n1 * $sumxy) - ($sumx * $sumy);
			$denom = (($n1 * $sumx2) - ($sumx * $sumx)) * (($n1 * $sumy2) - ($sumy * $sumy));
		
			$r = $numer / sqrt($denom);
			
			$t = $r * sqrt(($n1-2)/(1-($r*$r)));
			
			$this->set_t($t);
			$this->set_r($r);
			$this->set_df($n1);
		}
		
		function get_apa_stats() {
			$p = apa_round($this->get_p());
			$pvalue = ($p < .001) ? '< .001' : "= $p";
			
			$stats  = '<i>r</i> = ' . apa_round($this->r) . ', ';
			$stats .= '<i>n</i> = ' . $this->get_df() . ', ';
			$stats .= '<i>p</i> ' . $pvalue;
			
			return $stats;
		}
	}	
?>