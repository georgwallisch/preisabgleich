#!/usr/bin/php
<?php

	require_once('config.php');
	
	/* DB Statements */
	$st_insert = $db->prepare('INSERT INTO artikel (dirty, stand,'.implode(',',array_keys($artikelfelder)).') VALUES (0, FROM_UNIXTIME(?),?'.str_repeat(',?', count($artikelfelder) - 1).')');
	$st_update = $db->prepare('UPDATE artikel SET dirty=0, stand=?, '.implode('=?, ',array_keys($artikelfelder)).'=? WHERE id=? LIMIT 1');
	$st_pzncount = $db->prepare('SELECT * FROM pzns WHERE pzn=?');
	$st_getartikel_by_id = $db->prepare('SELECT * FROM artikel WHERE id=?');
	$st_getartikel_by_props = $db->prepare('SELECT * FROM artikel WHERE name=? AND df=? AND pm=? AND pe=? AND hersteller=?');
	$st_setdirty_preise = $db->prepare('UPDATE preise SET dirty=1 WHERE standort_id=?');
	$st_setdirty_pzns = $db->prepare('UPDATE pzns SET dirty=1 WHERE standort_id=?');
	$st_setdirty_artikel = $db->prepare('UPDATE artikel SET dirty=1 WHERE 1');
	
	
	
	//$st_pzncount = $db->prepare('SELECT * FROM artikel WHERE pzn=?');
	$st_pzncount_abdata = $db->prepare('SELECT * FROM pzns WHERE pzn=?');
	$st_pzncount_eigen = $db->prepare('SELECT * FROM pzns WHERE pzn=? AND standort_id=? ');
	$st_update = $db->prepare('UPDATE artikel SET dirty=0, stand=FROM_UNIXTIME(?), ek=?, vk=? WHERE id=?');
	$st_preis = $db->prepare('SELECT * FROM preise WHERE artikel_id=? AND standort_id=?');
	$st_insertp = $db->prepare('INSERT INTO preise (artikel_id, standort_id, dirty, stand, '.implode(',',array_keys($preisfelder)).') VALUES (?,?,0,FROM_UNIXTIME(?),?'.str_repeat(',?', count($preisfelder) - 1).')');
	$st_updatep = $db->prepare('UPDATE preise SET dirty=0, stand=FROM_UNIXTIME(?), '.implode('=? ,',array_keys($preisfelder)).'=? WHERE id=?');
	$st_setlastupdate = $db->prepare('UPDATE preise SET stand=FROM_UNIXTIME(?) WHERE id=?');
	$st_setclean = $db->prepare('UPDATE preise SET dirty=0 WHERE id=?');
	$st_insert_pzn_abdata = $db->prepare('INSERT INTO pzns (artikel_id,pzn,dirty,stand) VALUES(?,?,0,FROM_UNIXTIME(?))');
	$st_insert_pzn_eigen = $db->prepare('INSERT INTO pzns (artikel_id,standort_id,pzn,dirty,stand) VALUES(?,?,?,0,FROM_UNIXTIME(?))');
			
	function add_artikel($data) {
		global $db, $artikelfelder, $artikelbind, $st_insert, $lastupdate;
		$values = array();
		foreach($artikelfelder as $k => $v) {
				$values[] = $data[$v];
		}
		$st_insert->bind_param('i'.implode('', array_values($artikelbind)), $lastupdate, $values[0], $values[1], $values[2], $values[3], $values[4], $values[5], $values[6], $values[7], $values[8], $values[9], $values[10], $values[11], $values[12], $values[13]);			
		$r = $st_insert->execute();
		if($r) {
			return ($db->insert_id);
		} else {
			echo "FEHLER: Es konnte keine neue Artikel-ROW angelegt werden!!\n";
		}	
		
		return null;
	}
	
	
	function update_artikel_ifchanged($artikel_id, $data) {
		global $db, $artikelfelder, $st_getartikel_by_id;
		
		$st_getartikel_by_id->bind_param('i', $artikel_id);
		$st_getartikel_by_id->execute();
		$result = $st_getartikel_by_id->get_result();
		$row = $result->fetch_assoc();
		$result->free(); 
		/* Prüfen wir, ob alle Felder noch passen */
		$ok = true;							
		foreach($artikelfelder as $k => $v) {
			if($row[$k] != $data[$v]) {
				$ok = false;
				break;
			}
		}
		
		if(!$ok) {
			update_artikel($artikel_id, $data);
		}		
	}
	
	function update_artikel($artikel_id, $data) {
		global $db, $artikelfelder, $artikelbind, $st_update, $lastupdate;
		
		$values = array();
		foreach($artikelfelder as $k => $v) {
				$values[] = $data[$v];
		}
		$st_update->bind_param('i'.implode('', array_values($artikelbind)).'i', $lastupdate, $values[0], $values[1], $values[2], $values[3], $values[4], $values[5], $values[6], $values[7], $values[8], $values[9], $values[10], $values[11], $values[12], $values[13], $artikel_id);			

		return $st_update->execute();;
	}
	
	function add_pzn($pzn, $artikel_id, $standort_id = null) {

		global $db, $st_insert_pzn_abdata, $st_insert_pzn_eigen, $lastupdate;

			if(!is_null($artikel_id) and $artikel_id != 0) {								
				if(is_null($standort_id)) {
					$st_insert_pzn_abdata->bind_param('iii', $artikel_id, $pzn, $lastupdate);
					$r = $st_insert_pzn_abdata-execute();
				} else {
					$st_insert_pzn_eigen->bind_param('iiii', $artikel_id, $standort_id, $pzn, $lastupdate);
					$r = $st_insert_pzn_eigen-execute();
				}
				
				if($r) {
					return ($db->insert_id);
				} else {
					echo "FEHLER: Es konnte keine neue Preis-ROW angelegt werden!!\n";
				}	
			} else {
				echo "FEHLER: Ungültige Artikel-ID!!\n";
			}
				
		return null;
	}
	
/* ### MAIN EXEC STARTS HERE ### */	
	
	echo "*** Preisabgleich Import ***\n";
	echo "Lade aus {$csvdir} ..\n";
	
	
	$dh = opendir($csvdir);
	if($dh === false) {
		echo "FEHLER: Kann {$csvdir} nicht auslesen!";
		exit(1);
	}
	
	$st_setdirty_artikel->execute();
		
	while(false !== ($entry = readdir($dh))) {
		/* BEGIN LOOP THROUGH DIR ENTRIES */
		
		if($entry[0] == '.') continue;
		$filepath = $csvdir.'/'.$entry;
		
		if(!is_file($filepath) or !is_readable($filepath)) continue;
			
		$path_parts = pathinfo($filepath);
		if($path_parts['extension'] != 'csv') continue;
										
		echo "\nBearbeite {$entry} ..\n";
		
		$idf = substr($path_parts['basename'],(-8 - strlen($path_parts['extension'])),7);
		if(!array_key_exists($idf, $standorte)) {
			echo "FEHLER: Unbekannte IDF: {$idf}!\n";
			continue;
		}
		
		$loc = $standorte[$idf];
		echo "Standort: ".$loc['name']." ({$idf})\n";
		$standort_id = $loc['id'];
		
		$importcounter = 0;
		$newcounter = 0;
		
		$lastupdate = filemtime($filepath);
		echo "Datenstand ist ".date ("d.m.Y H:i", $lastupdate)."\n";
		
		$fp = @fopen($filepath, 'r');
		if($fp === false) {
			echo "WARNUNG: Kann {$filepath} nicht auslesen!";
			continue;
		}
						
		$st_setdirty_preise->bind_param('i',$standort_id);
		$st_setdirty_preise->execute();
		$st_setdirty_pzns->bind_param('i',$standort_id);
		$st_setdirty_pzns->execute();

		$firstline = true;
		$csvheader = array();
		$csvbind = array();

		while (($rawdata = fgetcsv($fp, 1000, $csv_separator)) !== FALSE) {
			/* BEGIN LOOP THROUGH CSV FILE */
		
			if($firstline === true) {
				$csvheader = mb_convert_encoding($rawdata, $db_encoding, $csv_encoding);
				$firstline = false;
				$t = false;
				foreach($csvheader as $v) {
					if(($index = array_search($v, $artikelfelder)) !== false) {
						$t = $artikelbind[$index];
					} elseif(($index = array_search($v, $preisfelder)) !== false) {
						$t = $preisbind[$index];
					}
					
					$csvbind[$v] = $t;
				} 
				continue;
			} 
			
			if(++$importcounter % 200 == 0) {
				echo "{$importcounter} Zeilen eingelesen..\n";
			}
			
			$data = array();
		
			foreach($csvheader as $k => $v) {
				if($csvbind[$v] == 'd') {
					$data[$v] = str_replace(',','.',$rawdata[$k]);
				} elseif($csvbind[$v] == 's') {
					$data[$v] = mb_convert_encoding($rawdata[$k], $db_encoding, $csv_encoding);
				} elseif($csvbind[$v] == 'i') {
					if(in_array($v, $bools)) {
							if(substr_compare($rawdata[$k],'ja',0,2,true) == 0 or substr_compare($rawdata[$k],'ABDATA',0,6) == 0) {
								$data[$v] = 1;
							} else {
								$data[$v] = 0;
							}
					} else {
						$data[$v] = $rawdata[$k];
					}
				} else {
					$data[$v] = $rawdata[$k];
				}						
			}
		
			$pzn = $data['PZN'];
			
			$st_pzncount->bind_param('i',$pzn);
			$st_pzncount->execute();
			$result = $st_pzncount->get_result();
			$id = null;
			
			if($result->num_rows < 1) {
				/* Arikel ist noch nicht in der DB => neu anlegen */
				$id = add_artikel($data, $lastupdate);
				if($id > 0) {
					++$newcounter;
					/* Und PZN neu anlegen */
					if($data['ABDATA']) {
						add_pzn($pzn, $id);
					} else {
						add_pzn($pzn, $id, $standort_id);
					}
				}					
			} else {
				/* Aha, Es gibt diese PZN bereits in der Datenbank */
				if($data['ABDATA']) {
					/* ABDATA-Artikel sind überall gleich */
					if($result->num_rows > 1) {
						echo "FEHLER: Es gibt mehr als eine Zeile mit PZN {$pzn} für einen ABDATA-Artikel!!\n";
					}
					$row = $result->fetch_assoc();
					$artikel_id = $result['id'];
					$result->free();
					
					update_artikel_ifchanged($artikel_id, $data);
				
				} else {
					$pzn_standort = null;
					/* Eigenangelegte Artikel können unterschiedliche PZNs je Standort haben */
					while(!is_null($row = $result->fetch_assoc())) {
						if($row['standort_id'] == $standort_id) {
							$pzn_standort = $row['standort_id'];
							break;
						}
					}
					
					if(is_null($pzn_standort)) {
						/* Okay.. die PZN gibt es, aber nicht für den aktuellen Standort */
						$values = array();
						$binds = '';
						foreach($artikelprops as $v) {
								$values[] = $data[$v];
								$binds .= $artikelbind[$v];
						}
						$st_getartikel_by_props->bind_param($binds, $values[0], $values[1], $values[2], $values[3], $values[4]);			
						$st_getartikel_by_props->execute();
						$r = $st_getartikel_by_props->get_result();
						if($r->num_rows > 0) {
							/* Genau diesen Artikel gibt es bereits, wir brauchen nur eine PZN Verknüpfung */
							$row = $result->fetch_assoc();
							$artikel_id = $result['id'];
							$result->free();
							add_pzn($pzn, $artikel_id, $standort_id);
						} else {
							/* Genau diesen Artikel gibt noch nicht */
							$artikel_id = add_artikel($data, $lastupdate);
							if($artikel_id > 0) {
								++$newcounter;
								/* Und PZN neu anlegen */
								add_pzn($pzn, $artikel_id, $standort_id);
							}
					} else {
						update_artikel_ifchanged($artikel_id, $data);
					}
				
				}
			/* !!!*/	
				$row = $result->fetch_assoc();
				$artikel_id = $row['id'];
				
				/* Arikel ist in der DB => ABDA-Preise vergleichen */
				if($row['vk'] != $data[$artikelfelder['vk']] or $row['ek'] != $data[$artikelfelder['ek']]) {
					/* Update, wenn unterschiedlich */
					$st_update->bind_param('iddi', $lastupdate, $data[$artikelfelder['ek']], $data[$artikelfelder['vk']], $artikel_id);			
					$st_update->execute();
				}
				
				/* Preis-Assoziation abfragen */
				$st_preis->bind_param('ii', $artikel_id, $standort_id);
				$st_preis->execute();
				$result = $st_preis->get_result();
				
				$values = array();
				foreach($preisfelder as $k => $v) {
					$values[] = $data[$v];
				}
				
				if($result->num_rows < 1) {
					/* Preis-Assoziation nicht vorhanden => neu einpflegen */
					$st_insertp->bind_param('iii'.implode('', array_values($preisbind)), $artikel_id, $standort_id, $lastupdate, $values[0], $values[1], $values[2], $values[3], $values[4], $values[5], $values[6], $values[7] );
					$st_insertp->execute();
					
				} else {
					$row = $result->fetch_assoc();

					/* Felder prüfen und ggf Änderung einpflegen */
					$somethingchanged = false;
					
					foreach($preisfelder as $k => $v) {
						if($row[$k] != $data[$v]) {
							$somethingchanged = true;
						}						
					}
					
					if($somethingchanged) {
						$st_updatep->bind_param('i'.implode('', array_values($preisbind)).'i', $lastupdate, $values[0], $values[1], $values[2], $values[3], $values[4], $values[5], $values[6], $values[7], $row['id'] );
						$st_updatep->execute();
					} else  {
						$st_setclean->bind_param('i',$row['id']);
						$st_setclean->execute();
					}
				}					
			}					
		}
		
			/* END OF LOOP THROUGH CSV FILE */
		} 
	
		fclose($fp);
		echo "..fertig: {$importcounter} Zeilen eingelesen und {$newcounter} Artikel neu eingepflegt!\n";
		
		/* END OF LOOP THROUGH DIR ENTRIES */
	}

	closedir($dh);
	
	echo "..Ende! Habe fertig.\n";	
?>