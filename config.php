<?php
	require_once('defaults.php');
	require_once('db.php');
	
	$csvdir = dirname(realpath(__FILE__)).'/csv';
	
	$standorte = array();
	/* DB-Feld => CSV-Spalte */
	$artikelfelder = array('pzn' => 'PZN', 'name' => 'Artikelname', 'df' => 'DF', 'pm' => 'PM', 'pe' => 'PE', 'hersteller' => 'Herstellerkürzel', 'ek' => 'EK', 'vk' => 'VK', 'AM' => 'AM', 'DC' => 'DC', 'MP' => 'MP', 'LM' => 'LM', 'KM' => 'KM', 'ABDATA' => 'ABDATA');
	$artikelbind = array('pzn' => 'i', 'name' => 's', 'df' => 's', 'pm' => 's', 'pe' => 's', 'hersteller' => 's', 'ek' => 'd', 'vk' => 'd', 'AM' => 'i', 'DC' => 'i', 'MP' => 'i', 'LM' => 'i', 'KM' => 'i', 'ABDATA' => 'i');
	$preisfelder = array('eek' => 'EEK', 'evk' => 'EVK', 'avk' => 'AVK', 'lager' => 'Lager', 'bestand' => 'Bestand', 'preisaktion' => 'Preisaktion', 'ap' => 'AP', 'kalkulationsmodell' => 'Kalkulationsmodell');
	$preisbind = array('eek' => 'd', 'evk' => 'd', 'avk' => 'd', 'lager' => 'i', 'bestand' => 'i', 'preisaktion' => 's', 'ap' => 'd', 'kalkulationsmodell' => 's');
	
	$bools = array('lager','AM', 'DC', 'MP','LM','KM', 'ABDATA');
	
	$artikeltypen = array('AM' => 'Arzneimittel', 'DC' => 'Drogen/Chemikalien', 'MP' => 'Medizinprodukte','LM' => 'Lebensmittel','KM' => 'Kosmetika');
	$artikeltypen_default = array('AM', 'MP', 'LM', 'KM');
	
	$csvfelder = array('Artikelsortiername', 'PZN', 'Artikelname', 'DF', 'PM', 'PE', 'Herstellerkürzel', 'Lager', 'Bestand', 'EK', 'EEK', 'AVK', 'VK', 'EVK', 'Preisaktion', 'AP', 'Kalkulationsmodell', 'AM', 'DC', 'MP', 'LM', 'KM', 'ABDATA');
		
	$csv_separator = ';';
	$csv_encoding = 'Windows-1252';
	$db_encoding = 'UTF-8';
	
	$db_datetime_format = '%d.%m.%Y %H:%i'; 
	
	$min_search_length = 3;
			
	$result = $db->query('SELECT * FROM standorte ORDER BY idf');
	
	$gp_types = array('St' => 'piece', 'ml' => 'volume', 'mg' => 'mass', 'm' => 'length', 'l' => 'volume', 'kg' => 'mass', 'g' => 'mass');
	$gp_factors = array('St' => 1, 'ml' => 1000, 'mg' => 1000000, 'm' => 1, 'l' => 1, 'kg' => 1, 'g' => 1000);
	$gp_base_unit = array('piece' => 'St', 'volume' => 'L', 'mass' => 'kg', 'length' => 'm');

	if($result == false) {
		die('No Result from DB!');
	} elseif ($result->num_rows < 1) {
		die('Keine Standorte in der DB gefunden!');
	} 
	
	while ($row = $result->fetch_assoc()) {
		$standorte[$row['idf']] = $row;
	}
/*	
	print_r($artikelfelder);
	print_r($preisfelder);
	exit(1);
*/

?>