<?php
require('includes/application_top.php');
require( DIR_WS_MODULES . '/payment/bitpagos.php');

$bitpagos = New bitpagos;
$bitpagos->ipn_change_order_status();
?>
