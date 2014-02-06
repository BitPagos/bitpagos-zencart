<?php

ini_set('display_errors', 'ON');
error_reporting(1);

class bitpagos {
	
	public function bitpagos() {		
        
        global $order;

        $this->code = 'bitpagos';
        $this->version = '1.1.0';
        $this->api_version = '2';
        $this->title = MODULE_PAYMENT_BITPAGOS_TITLE;
        $this->public_title = MODULE_PAYMENT_BITPAGOS_TITLE;
        
        if (defined('MODULE_PAYMENT_BITPAGOS_STATUS')) {

            $this->enabled = ((MODULE_PAYMENT_BITPAGOS_STATUS == 'True') ? true : false);
            $this->sort_order = MODULE_PAYMENT_BITPAGOS_SORT_ORDER;
            $this->api_key = '';            
            $this->account_id = '';
            $this->label = ((MODULE_PAYMENT_BITPAGOS_LABEL == 'True') ? true : false);
            if ((int) MODULE_PAYMENT_BITPAGOS_ORDER_STATUS_ID > 0) {
                $this->order_status = MODULE_PAYMENT_BITPAGOS_ORDER_STATUS_ID;
            }
                        
            if ($this->logging) {}

        }
        
        if ( empty( $this->api_key ) || empty( $this->account_id ) ) {            
            $this->set_bitpagos_credentials();
        }

	}

    private function set_bitpagos_credentials() {
        global $db;
        $sql = 'SELECT configuration_key, configuration_value FROM ' . TABLE_CONFIGURATION;
        $sql .= ' WHERE configuration_key IN ("MODULE_PAYMENT_BITPAGOS_APIKEY", "MODULE_PAYMENT_BITPAGOS_ACCOUNTID")';
        $result = $db->Execute( $sql );
        while( !$result->EOF ) {
            if ( $result->fields['configuration_key'] === "MODULE_PAYMENT_BITPAGOS_APIKEY" ) {
                $this->api_key = $result->fields['configuration_value'];
            } else {
                $this->account_id = $result->fields['configuration_value'];
            }
            $result->MoveNext();
        }
    }

    private function get_bitpagos_credentials() {
        return array('account_id' => $this->account_id, 'api_key' => $this->api_key);
    }

    public function get_btn_data( $order_id ) {        
        
        global $db;
        $data = array();

        $sql = 'SELECT configuration_value FROM ' . TABLE_CONFIGURATION . ' WHERE configuration_key = "STORE_NAME"';
        $result = $db->Execute( $sql );        
        while( !$result->EOF ) {
            $data['store_name'] = $result->fields['configuration_value'];
            $result->MoveNext();
        }

        $sql = 'SELECT products_name FROM ' . TABLE_ORDERS_PRODUCTS . ' WHERE orders_id = ' . (int)$order_id;
        $result = $db->Execute( $sql );
        while( !$result->EOF ) {
            $data['products'][] = $result->fields['products_name'];
            $result->MoveNext();
        }

        $data['credentials'] = $this->get_bitpagos_credentials();
        return $data;

    }

    /**
    * Triggered from ipn
    *
    * @return array
    */
    public function post_order() {

        global $db;

        if ( sizeOf( $_POST ) == 0 ) { return; }

        $reference_id = (int)$_POST['referenceId'];
        $transaction_id = filter_var( $_POST['transactionId'], FILTER_SANITIZE_STRING );        

        if ( $this->complete_order($reference_id, $transaction_id) ) {
            zen_redirect( zen_href_link(FILENAME_CHECKOUT_SUCCESS, zen_get_all_get_params(array('action')), 'SSL', false) );
        }
    }

    public function complete_order($reference_id, $transaction_id) {

        global $db;

        $url = "https://www.bitpagos.net/api/v1/transaction/" . $transaction_id . "/?api_key=" . $this->api_key . "&format=json";

        $cbp = curl_init( $url );
        curl_setopt($cbp, CURLOPT_RETURNTRANSFER, TRUE);
        $response = curl_exec( $cbp );
        curl_close( $cbp );
        $response = json_decode($response);

        if ( $reference_id != $response->reference_id ) {
            return false;
        }

        if ( $response->status == 'PA' || $response->status == 'CO' ) {

            $sql = 'SELECT configuration_key, configuration_value FROM ' . TABLE_CONFIGURATION . ' WHERE configuration_key IN ("STORE_NAME", "MODULE_PAYMENT_BITPAGOS_TRANS_ORDER_STATUS_ID")';
            $result = $db->Execute( $sql );
            while( !$result->EOF ) {
                $data[$result->fields['configuration_key']] = $result->fields['configuration_value'];
                $result->MoveNext();
            }            
            
            $order_id = (int)$reference_id;
            $sql = 'UPDATE ' . TABLE_ORDERS . ' SET orders_status = ' . (int)$data["MODULE_PAYMENT_BITPAGOS_TRANS_ORDER_STATUS_ID"] . ' WHERE orders_id = ' . $order_id;
            return $db->Execute( $sql );

        } else {
            return true;
        }

    }

    /**
    * Triggered from ipn
    *
    * @return array
    */
    public function ipn_change_order_status() {

        global $db;

        if ( sizeOf( $_POST ) == 0 ) { 
            header("HTTP/1.1 500 EMPTY_POST ");
            return false;
        }

        if (!isset( $_POST['transaction_id'] ) || 
            !isset( $_POST['reference_id'] ) ) {
            header("HTTP/1.1 500 BAD_PARAMETERS");
            return false;
        }

        $reference_id = (int)$_POST['reference_id'];
        $transaction_id = filter_var( $_POST['transaction_id'], FILTER_SANITIZE_STRING );

        if ( $this->complete_order($reference_id, $transaction_id) ) {
            header("HTTP/1.1 200 OK");
        } else {
            header("HTTP/1.1 500 BAD_REFERENCE_ID");
        }

    }

    /**
    * Check if module is intalled
    *
    * @return array
    */
	public function check() {
        global $db;
        if (!isset($this->_check)) {
            $check_query = $db->Execute("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_BITPAGOS_STATUS'");
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;    
    }

    /**
    * Not needed
    *
    * @return bool
    */
    public function javascript_validation() {
        return false;
    }

    /**
    * Not needed
    *
    * @return bool
    */
    public function pre_confirmation_check() {
        return true;
    }

    public function process_button() {}

    public function before_process() {}

    /**
    * Triggered after order is created
    *
    * @return void
    */
    public function after_process() {
        zen_redirect('process_bitpagos_checkout.php');
    }

    public function after_order_create() {}

    public function confirmation() {}

    /**
    * Displays payment method name on the Checkout Payment Page
    *
    * @return array
    */
    public function selection() {
        return array(
            'id' => $this->code,
            'module' => MODULE_PAYMENT_BITPAGOS_TITLE,
            'icon' => MODULE_PAYMENT_BITPAGOS_LOGO
        );
    }

    /**
    * Install module
    *
    * @return void
    */
	public function install() {
        
        global $db;

        $db->Execute("DROP TABLE IF EXISTS `" . TABLE_BITPAGOS . "`");
        
        $db->Execute(
            "CREATE TABLE IF NOT EXISTS `" . TABLE_BITPAGOS . "` (
            	`id` int(11) NOT NULL AUTO_INCREMENT, 
            	`reference_id` text NOT NULL,
            	`transaction_id` text NOT NULL,
            	`order_id` smallint(5) NOT NULL,
            	`date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            	PRIMARY KEY (`id`)
        	) AUTO_INCREMENT=1"
        );

        include(DIR_FS_CATALOG . DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/payment/bitpagos.php');
        
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('" . MODULE_PAYMENT_BITPAGOS_STATUS_TITLE . "', '" . MODULE_PAYMENT_BITPAGOS_STATUS_DESC . "', 'MODULE_PAYMENT_BITPAGOS_STATUS', 'True', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");        
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, set_function, date_added) VALUES ('" . MODULE_PAYMENT_BITPAGOS_LABEL_TITLE . "', '" . MODULE_PAYMENT_BITPAGOS_LABEL_DESC . "', 'MODULE_PAYMENT_BITPAGOS_LABEL', 'False', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('" . MODULE_PAYMENT_BITPAGOS_SORT_ORDER_TITLE . "', '" . MODULE_PAYMENT_BITPAGOS_SORT_ORDER_DESC . "', 'MODULE_PAYMENT_BITPAGOS_SORT_ORDER', '0', '6', '0', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('" . MODULE_PAYMENT_BITPAGOS_APIKEY_TITLE . "', '" . MODULE_PAYMENT_BITPAGOS_APIKEY_DESC . "', 'MODULE_PAYMENT_BITPAGOS_APIKEY', '0', '6', '0', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('" . MODULE_PAYMENT_BITPAGOS_STORE_URL_TITLE . "', '" . MODULE_PAYMENT_BITPAGOS_STORE_URL_DESC . "', 'MODULE_PAYMENT_BITPAGOS_STORE_URL', '0', '6', '0', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, date_added) VALUES ('" . MODULE_PAYMENT_BITPAGOS_ACCOUNTID_TITLE . "', '" . MODULE_PAYMENT_BITPAGOS_ACCOUNTID_DESC . "', 'MODULE_PAYMENT_BITPAGOS_ACCOUNTID', '0', '6', '0', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) values ('" . MODULE_PAYMENT_BITPAGOS_ORDER_STATUS_ID_TITLE . "', '" . MODULE_PAYMENT_BITPAGOS_ORDER_STATUS_ID_DESC . "', 'MODULE_PAYMENT_BITPAGOS_ORDER_STATUS_ID', '0',  '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, set_function, use_function, date_added) values ('" . MODULE_PAYMENT_BITPAGOS_TRANS_ORDER_STATUS_ID_TITLE . "', '" . MODULE_PAYMENT_BITPAGOS_TRANS_ORDER_STATUS_ID_DESC . "', 'MODULE_PAYMENT_BITPAGOS_TRANS_ORDER_STATUS_ID', '1', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
        $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . " (configuration_title, configuration_description, configuration_key, configuration_value, configuration_group_id, sort_order, use_function, set_function, date_added) values ('" . MODULE_PAYMENT_BITPAGOS_ZONE_TITLE . "', '" . MODULE_PAYMENT_BITPAGOS_ZONE_DESC . "', 'MODULE_PAYMENT_BITPAGOS_ZONE', '0', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");

    }

    /**
    * Remove module
    *
    * @return void
    */
    function remove() {

        global $db;
        $db->Execute("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
        $db->Execute("DROP TABLE IF EXISTS " . TABLE_BITPAGOS);

    }
    
    /**
    * What to show in the admin -> Modules -> Payments > BitPagos
    *
    * @return array
    */    
	public function keys() {
        return array(
            'MODULE_PAYMENT_BITPAGOS_STATUS',
            'MODULE_PAYMENT_BITPAGOS_LABEL',
            'MODULE_PAYMENT_BITPAGOS_APIKEY',
            'MODULE_PAYMENT_BITPAGOS_ACCOUNTID',
            'MODULE_PAYMENT_BITPAGOS_ORDER_STATUS_ID',
            'MODULE_PAYMENT_BITPAGOS_TRANS_ORDER_STATUS_ID',
            'MODULE_PAYMENT_BITPAGOS_SORT_ORDER',
            'MODULE_PAYMENT_BITPAGOS_STORE_URL'
        );
    }

}
?>