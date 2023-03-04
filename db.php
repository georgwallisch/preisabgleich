<?php

$db = new mysqli('localhost', 'preis', 'preis#123', 'preis');

if(mysqli_connect_error()) {
    die('Connect Error (' . mysqli_connect_errno() . ') '
            . mysqli_connect_error());
}

?>
