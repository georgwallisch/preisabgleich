<?php
/* Default definitions */

	error_reporting(E_ALL & ~E_NOTICE);

	$title = 'Preisabgleich';
	
	$bootstrap_config['local_css'][] = 'css/preisabgleich.css';
	$bootstrap_config['local_js'][]  =  'js/preisabgleich.js';
	$bootstrap_config['local_js'][] = 'https://intern.apotheke-schug.de/js/tablesorter/dist/js/jquery.tablesorter.min.js'; #integrity="sha384-+PEWXCk8F17zxsQsEjkuHjUN4yFMHv03eKxKLrqwDql8FJQM0NeSvHRZFVLfXyn7"
	
?>