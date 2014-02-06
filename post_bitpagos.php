
<?php

ini_set('display_errors', 'ON');
error_reporting(1);

require('includes/application_top.php');

$current_page_base = 'checkout_success';

require($template->get_template_dir('html_header.php', DIR_WS_TEMPLATE, $current_page_base,'common'). '/html_header.php');
require( DIR_WS_MODULES . 'payment/bitpagos.php');

$bitpagos = New bitpagos;
$bitpagos->post_order();

require($template->get_template_dir('tpl_main_page.php', DIR_WS_TEMPLATE, $current_page_base,'common'). '/tpl_main_page.php');
require(DIR_WS_INCLUDES . 'application_bottom.php');
?>
 
