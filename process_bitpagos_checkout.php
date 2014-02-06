<?php

require('includes/application_top.php');

$current_page_base = 'checkout_success';

require($template->get_template_dir('html_header.php',DIR_WS_TEMPLATE, $current_page_base,'common'). '/html_header.php');

$body_code = 'process_bitpagos_checkout_btn.php';

require($template->get_template_dir('tpl_main_page.php',DIR_WS_TEMPLATE, $current_page_base,'common'). '/tpl_main_page.php');
require(DIR_WS_INCLUDES . 'application_bottom.php');
?>