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
	
	/* main code starts here */


	
	ob_start();
	
	require_once '../config.php';	
	
	$reply = array();
	
	if(!is_null($s = get_value('artikelsuche'))) {
		$reply['search'] = $s;
		$diff = get_value('diff');
			
		if(strlen($s) >= $min_search_length) {
			if(!is_null($diff)) {
				$st = $db->prepare('SELECT * FROM `artikel` INNER JOIN `preise` ON preise.artikel_id=artikel.id WHERE name LIKE UPPER(?) GROUP BY preise.artikel_id HAVING COUNT(DISTINCT(preise.'.$diff.')) > 1 ORDER BY artikel.name, artikel.pm');
			} else {
				$st = $db->prepare('SELECT *, id as artikel_id FROM artikel WHERE name LIKE UPPER(?) ORDER BY name, pm');
			}
			if($st === false) {
				send_reply(array('error' => 'MySQL Syntax Error', 'MySQL Error Details' => $db->error));
			}
			$search  = array('*', '?', ' ');
			$replace = array('%', '_', '%');
			$ss = str_replace($search, $replace, $s).'%';
			$st->bind_param('s', $ss);
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
		} else {
			$reply['error'] = 'Suchanfrage zu kurz!';
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
	
	$st = $db->prepare('SELECT COUNT(*), DATE_FORMAT(MIN(`stand`),?), DATE_FORMAT(MAX(`stand`),?) FROM `artikel`');
	$st->bind_param('ss', $db_datetime_format, $db_datetime_format);
	$st->execute();
	$res = $st->get_result();
	$row = $res->fetch_row();
	$reply['artikel'] = $row[0];
	$reply['artikel_min_stand'] = $row[1];
	$reply['artikel_max_stand'] = $row[2];
	$res->free();
	
	$st = $db->prepare('SELECT DATE_FORMAT(MIN(`stand`),?), DATE_FORMAT(MAX(`stand`),?) FROM `preise`');
	$st->bind_param('ss', $db_datetime_format, $db_datetime_format);
	$st->execute();
	$res = $st->get_result();
	$row = $res->fetch_row();
	$reply['preise_min_stand'] = $row[0];
	$reply['preise_max_stand'] = $row[1];
	$res->free();
	
	$reply['min_search_length'] = $min_search_length;
	$reply['standorte'] = $standorte;
	
	send_reply(null, false);

?>