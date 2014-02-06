<?php 
require('includes/modules/payment/bitpagos.php');
require('includes/languages/english/modules/payment/bitpagos.php');

$reference_id = $_SESSION['order_summary']['order_number'];
$amount = $_SESSION['order_summary']['order_total'];
$currency = $_SESSION['currency'];
$bitpagos = New bitpagos;
$data = $bitpagos->get_btn_data( $reference_id );

$url = MODULE_PAYMENT_BITPAGOS_STORE_URL . MODULE_PAYMENT_BITPAGOS_POST;
$ipn = MODULE_PAYMENT_BITPAGOS_STORE_URL . MODULE_PAYMENT_BITPAGOS_IPN;
?>

<div style="text-align: center">
	<form action="<?php echo $url ?>" method="post">
		<p>Thank you for your order, please click the button below to pay with BitPagos.</p>
		<script src='https://www.bitpagos.net/public/js/partner/m.js' class='bp-partner-button' data-role='checkout' data-account-id="<?php echo $data['credentials']['account_id'] ?>" data-reference-id='<?php echo $reference_id?>' data-title='<?php echo $data['store_name']?>' data-amount='<?php echo $amount ?>' data-currency='<?php echo $currency ?>' data-description='<?php echo join(",", $data['products']) ?>' data-ipn="<?php echo $ipn ?>"></script>
	</form>
</div>