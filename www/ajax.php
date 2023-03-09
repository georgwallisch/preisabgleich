<?php
	
	$debugmode = get_value('debug', false);
	
	if($debugmode) {
		error_reporting(E_ALL & ~E_NOTICE);
		ini_set("display_errors", 1);
	}

	function get_value($name, $default = null, $isnot = null) {
		if(!array_key_exists($name, $_REQUEST) or $_REQUEST[$name] == $isnot) {
			return $default;
		}
		
		return $_REQUEST[$name];	
	}
	
	function send_reply($arr = null, $die = true) {
		global $reply, $debugmode;
						
		if(is_array($arr)) {
			$reply = array_merge($reply, $arr);
		}
		
		$c = ob_get_contents();
		
		ob_end_clean();
		
		if($debugmode) {
			if(strlen($c) > 0) {
				$reply['ob_content'] = $c;
			}
			header("Content-type: text/plain; charset=utf-8");
			echo "-- DEBUG MODE --\n\n";
			
			echo print_r($_REQUEST);
			echo "\n\n----\n\n";
			echo print_r($reply);
/*		
			echo "\n\n----\n\n";
			global $options, $flatoptions;
			echo print_r($options);
			echo "\n\n----\n\n";
			echo print_r($flatoptions);
			echo "\n\n----\n\n";
*/
		} else {
			header("Content-type: application/json; charset=utf-8");
			echo json_encode($reply);
		}	
		
		if($die) exit;
	}
	
	function get_distinct_list($table, $col) {
		global $db;
		
		$list = array();
		
		$st = $db->prepare('SELECT DISTINCT(`'.$col.'`) FROM `'.$table.'` where `'.$col.'` IS NOT null ORDER BY `'.$col.'`');
		$st->execute();
		
		$res = $st->get_result();
		
		if($res->num_rows > 0) {
			while($row = $res->fetch_row()) {
				$list[] = $row[0];
			}    			
		}
		
		$res->free();
		
		return $list;	
	}
	
	function get_stand($table, $include_count = true) {
		global $db, $db_datetime_format, $db_datetime_format;
		
		$list = array();
		
		$q = 'SELECT DATE_FORMAT(MIN(`stand`),?), DATE_FORMAT(MAX(`stand`),?)';
		
		if($include_count) {
			$q .= ', COUNT(*)';
		}
		
		$q .= ' FROM `'.$table.'`';
		
		$st = $db->prepare($q);

		$st->bind_param('ss', $db_datetime_format, $db_datetime_format);
		
		if($st === false) {
			send_reply(array('error' => 'MySQL Syntax Error', 'MySQL Error Details' => $db->error));
		}

		$st->execute();
		
		$res = $st->get_result();
		
		if($res->num_rows > 0) {
			$row = $res->fetch_row();
			$list[$table.'_min_stand'] = $row[0];
			$list[$table.'_max_stand'] = $row[1];
			if($include_count) {
				$list[$table] = $row[2];
			}
		}
		
		$res->free();
		
		return $list;
	}
	
	/* main code starts here */


	
	ob_start();
	
	require_once '../config.php';
	
	$search  = array('*', '?', ' ');
	$replace = array('%', '_', '%');
	
	$reply = array();
	
	$st = null;
	
	if(!is_null($s = get_value('pzn'))) {
		
		$st = $db->prepare('SELECT *, id as artikel_id FROM artikel WHERE pzn=? ORDER BY name, pm');
		$st->bind_param('i', $s);
		$reply['search_type'] = 'pzn';
	
	} elseif(!is_null($s = get_value('pznmin')) and !is_null($ss = get_value('pznmax'))) {
		
		$st = $db->prepare('SELECT *, id as artikel_id FROM artikel WHERE pzn>=? AND pzn<=? ORDER BY name, pm');
		$st->bind_param('ii', $s, $ss);
		$reply['search_type'] = 'pznrange';
	
	} elseif(!is_null($s = get_value('artikelsuche'))) {
		
		$diff = get_value('diff');
		$abdata = get_value('abdata');
		$arttype = get_value('arttype');
				
		$reply['search_type'] = 'artikelname';
        
		$filter = '';
		
		if(is_array($arttype)) {
			$a = array();
			foreach($arttype as $v) {
				if(array_key_exists($v, $artikeltypen)) {
					$a[] = '`'.$v.'`=TRUE';
				}
			}
			$filter .= ' AND ('.implode($a, ' OR ').')';			
		}
		
		$q = null;
		
		if(!is_null($diff)) {
			$q = 'SELECT * FROM `artikel` INNER JOIN `preise` ON preise.artikel_id=artikel.id WHERE name LIKE UPPER(?)'.$filter;
			if(!is_null($abdata)) {
				$q .= ' AND ABDATA=?';
			}
			$q .= ' GROUP BY preise.artikel_id HAVING COUNT(DISTINCT(preise.'.$diff.')) > 1 ORDER BY artikel.name, artikel.pm';
		} else if(strlen($s) >= $min_search_length) {
			$q = 'SELECT *, id as artikel_id FROM artikel WHERE name LIKE UPPER(?)'.$filter;
			if(!is_null($abdata)) {
				$q .= ' AND ABDATA=?';
			}
			 $q .= ' ORDER BY name, pm';
		} else {
			$reply['error'] = 'Suchanfrage zu kurz!';
		}
			
		if(!is_null($q)) {
			$st = $db->prepare($q);
			$ss = str_replace($search, $replace, $s).'%';
			if(!is_null($abdata)) {
				$st->bind_param('si', $ss, $abdata);	
			} else {
				$st->bind_param('s', $ss);
			}
		}		
	}
	
	if($st === false) {
		send_reply(array('error' => 'MySQL Syntax Error', 'MySQL Error Details' => $db->error));
	} elseif (!is_null($st)) {
		$reply['search'] = $s;
		$st->execute();
		$result = $st->get_result();
		if($result->num_rows < 1) {
			send_reply(array('found' => 0, 'message' => 'Kein Treffer!'));
		} else {
			$reply['found'] = $result->num_rows;
			$hitlist = array();
			while ($row = $result->fetch_assoc()) {
				$hitlist[] = $row;
			}    			
			$reply['hitlist'] = $hitlist;	
		}
		send_reply(null);
	}
	
	if(!is_null($s = get_value('artikeldetails'))) {
		$reply['id'] = $s;
		$ss = $s;
		$st = $db->prepare('SELECT * FROM preise INNER JOIN standorte ON preise.standort_id=standorte.id WHERE artikel_id=? ORDER BY standort_id');
		if($st === false) {
			send_reply(array('error' => 'MySQL Syntax Error', 'MySQL Error Details' => $db->error));
		}
		$st->bind_param('i', $ss);
		$st->execute();
		$result = $st->get_result();
		if($result->num_rows < 1) {
			send_reply(array('found' => 0, 'message' => 'Kein Treffer!'));
		} else {
			$reply['found'] = $result->num_rows;
			$hitlist = array();
			while ($row = $result->fetch_assoc()) {
				$hitlist[] = $row;
			}    			
			$reply['hitlist'] = $hitlist;
			
		}
		send_reply(null);
	}
	$a = get_stand('artikel');
	echo "\n\n=== Artikelinfo ===\n";
	print_r($a);
	echo "\n\n=== Preisinfo ===\n";
	$b = get_stand('preise', false);
	print_r($b);
	$reply = array_merge($reply, $a, $b);
			
	$reply['hersteller'] = get_distinct_list('artikel', 'hersteller');
	$reply['df'] = get_distinct_list('artikel', 'df');
	$reply['kalkulationsmodelle'] = get_distinct_list('preise', 'kalkulationsmodell');
	
	$reply['min_search_length'] = $min_search_length;
	$reply['standorte'] = $standorte;
	$reply['artikeltypen'] = $artikeltypen;
	$reply['artikeltypen_default'] = $artikeltypen_default;
	
	send_reply(null, false);
	
	ob_end_clean();
?>