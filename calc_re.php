#!/usr/bin/php
<?php

	require_once('config.php');
	
	$st_artikel = $db->prepare('SELECT * FROM artikel');
	$st_preis = $db->prepare('SELECT * FROM preise WHERE artikel_id=?');
	$st_update = $db->prepare('UPDATE preise SET re_ek=?, re_eek=? WHERE id=? LIMIT 1');
	
	$counter = 0;
		
	echo "*** Preisabgleich Update Rohertag ***\n";
			
	$st_artikel->execute();
	$result_artikel = $st_artikel->get_result();
	
	if($result_artikel->num_rows < 1) {
		echo "FEHLER: Es gibt keine Artikel in der Datenbank!!\n";		
	} else {
		while ($artikel = $result_artikel->fetch_assoc()) {
			$st_preis->bind_param('i', $artikel['id']);
			$st_preis->execute();
			$result_preis = $st_preis->get_result();
			if($result_preis->num_rows < 1) {
				echo "FEHLER: Der Artikel ".$artikel['id']." hat keine Preisverknüpfungen in der Datenbank!!\n";
			} else {
				$mwst = $mwst_satz[array_search($artikel['MWST'], $mwst_arten)];
				$mwst_f = (100 - $mwst)/100;
				while ($preis = $result_preis->fetch_assoc()) {
					$netto = round($preis['avk'], 2);
					$re_ek = $netto - $artikel['ek'];
					$re_eek = $netto - $preis['eek'];	
					$st_update->bind_param('ddi', $re_ek, $re_eek, $preis['id']);
					$st_update->execute();
					//echo "Artikel ".$artikel['id']." Mwst: $mwst; Netto: $netto; RE-EK: $re_ek; RE-EEK: $re_eek;\n";  
				}
			}
			$result_preis->free();
			if(++$counter % 200 == 0) {
				echo "{$counter} Artikel bearbeitet..\n";
			}
		}	
	}
	$result_artikel->free();
?>