#!/usr/bin/php
<?php

	require_once('config.php');
	
	$st_pzncount = $db->prepare('SELECT * FROM artikel WHERE pzn=?');
	$st_insert = $db->prepare('INSERT INTO artikel (stand,'.implode(',',array_keys($artikelfelder)).') VALUES (FROM_UNIXTIME(?),?'.str_repeat(',?', count($artikelfelder) - 1).')');
	$st_update = $db->prepare('UPDATE artikel SET stand=FROM_UNIXTIME(?), ek=?, vk=? WHERE id=?');
	$st_preis = $db->prepare('SELECT * FROM preise WHERE artikel_id=? AND standort_id=?');
	$st_insertp = $db->prepare('INSERT INTO preise (artikel_id, standort_id, dirty, stand, '.implode(',',array_keys($preisfelder)).') VALUES (?,?,0,FROM_UNIXTIME(?),?'.str_repeat(',?', count($preisfelder) - 1).')');
	$st_updatep = $db->prepare('UPDATE preise SET dirty=0, stand=FROM_UNIXTIME(?), '.implode('=? ,',array_keys($preisfelder)).'=? WHERE id=?');
	$st_setdirty = $db->prepare('UPDATE preise SET dirty=1 WHERE standort_id=?');
	$st_setlastupdate = $db->prepare('UPDATE preise SET stand=FROM_UNIXTIME(?) WHERE id=?');
	$st_setclean = $db->prepare('UPDATE preise SET dirty=0 WHERE id=?');

	
	echo "*** Preisabgleich Import ***\n";
	echo "Lade aus {$csvdir} ..\n";
	
	if($dh = opendir($csvdir)) {
		
		while(false !== ($entry = readdir($dh))) {
			if($entry[0] == '.') continue;
			
			$filepath = $csvdir.'/'.$entry;
			if(is_file($filepath) and is_readable($filepath)) {
				
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
					echo "\nFehler beim Öffnen von {$filepath}!";
					continue;
				}
								
				$st_setdirty->bind_param('i',$standort_id);
				$st_setdirty->execute();

				$firstline = true;
				$csvheader = array();
				$csvbind = array();
	    
				while (($rawdata = fgetcsv($fp, 1000, $csv_separator)) !== FALSE) {
					
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
										
					if($result->num_rows < 1) {
						/* Arikel ist noch nicht in der DB => neu anlegen */
						$values = array();
						foreach($artikelfelder as $k => $v) {
								$values[] = $data[$v];
						}
						$st_insert->bind_param('i'.implode('', array_values($artikelbind)), $lastupdate, $values[0], $values[1], $values[2], $values[3], $values[4], $values[5], $values[6], $values[7], $values[8], $values[9], $values[10], $values[11], $values[12], $values[13], $values[14]);			
						$st_insert->execute();
						++$newcounter;
					} else {
						if($result->num_rows > 1) {
							echo "FEHLER: Es gibt mehr als eine Zeile mit PZN {$pzn}!!\n";
						}
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
				
				fclose($fp);
				echo "..fertig: {$importcounter} Zeilen eingelesen und {$newcounter} Artikel neu eingepflegt!\n";
			} 
			
		}

		closedir($dh);
	} else {
		echo "FEHLER: Kann {$csvdir} nicht auslesen!";
		exit(1);
	}


?>