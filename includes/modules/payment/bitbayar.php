<?php

class bitbayar
{
	var $code, $title, $description, $enabled;

	function bitbayar ()
	{
		global $order;

		$this->signature = 'bitbayar|bitbayar_oc|2.0|2.3';
		$this->api_version = '1.0';

		$this->code        = 'bitbayar';
		$this->title       = MODULE_PAYMENT_BITBAYAR_TEXT_TITLE;
		$this->description = MODULE_PAYMENT_BITBAYAR_TEXT_DESCRIPTION;
		$this->sort_order  = MODULE_PAYMENT_BITBAYAR_SORT_ORDER;
		$this->enabled     = ((MODULE_PAYMENT_BITBAYAR_STATUS == 'True') ? true : false);

		if ((int)MODULE_PAYMENT_BITBAYAR_ORDER_STATUS_ID > 0)
		{
			$this->order_status = MODULE_PAYMENT_BITBAYAR_ORDER_STATUS_ID;
			$payment='bitbayar';
		}
		else if ($payment=='bitbayar')
		{
			$payment='';
		}

		if (is_object($order))
		{
			$this->update_status();
		}

		$this->email_footer = MODULE_PAYMENT_BITBAYAR_TEXT_EMAIL_FOOTER;
	}


	function update_status () {
		global $order;

		// check that api key is not blank
		if (!MODULE_PAYMENT_BITBAYAR_APITOKEN OR !strlen(MODULE_PAYMENT_BITBAYAR_APITOKEN))
		{
			$this->description = '<div class="secWarning"> API Token Error</div>' . $this->description;
			$this->enabled = false;
		}
	}


	function javascript_validation ()
	{
		return false;
	}


	function selection ()
	{
		return array('id' => $this->code, 'module' => $this->title);
	}


	function pre_confirmation_check ()
	{
		return false;
	}


	function confirmation ()
	{
		return false;
	}


	function process_button ()
	{
		return false;
	}


	function before_process ()
	{
		return false;
	}


	function after_process ()
	{
		global $insert_id, $order, $currencies;
		require_once 'bitbayar/bb_lib.php';
		
		$bitbayar_currency = 'IDR';
		$total_amount = $order->info['total'];

		$idr_rate = $currencies->currencies[$bitbayar_currency]['value'];

		if($idr_rate==NULL)
			tep_redirect(tep_href_link(FILENAME_CHECKOUT_PAYMENT, 'payment_error=' . $this->code.'&error='.urlencode('IDR currency require'), 'NONSSL', true, false));

		// change order status to value selected by merchant
		tep_db_query("update ". TABLE_ORDERS. " set orders_status = " . intval(MODULE_PAYMENT_BITBAYAR_UNPAID_STATUS_ID) . " where orders_id = ". intval($insert_id));

		$bitbayar_url = 'https://bitbayar.com/api/create_invoice';
		$dataPost=array(
			'token'=>MODULE_PAYMENT_BITBAYAR_APITOKEN,
			'invoice_id'=>$insert_id,
			'rupiah'=>round($total_amount*$idr_rate),
			'memo'=>'Invoice #'.$insert_id.' - Oscommerce',
			'callback_url'=>tep_href_link('bitbayar_callback.php', '', 'SSL', true, true),
			'url_success'=>tep_href_link(FILENAME_ACCOUNT),
			'url_failed'=>tep_href_link(FILENAME_ACCOUNT)
		);
		$bb_pay = bbcurlPost($bitbayar_url, $dataPost);
		$result = json_decode($bb_pay);

		if($result->success){
			tep_redirect($result->payment_url);
		}
		else
		{
			tep_redirect(tep_href_link(FILENAME_SHOPPING_CART, 'error_message=' . urlencode($result->error_message), 'SSL'));
		}

		return false;
	}


	function get_error ()
	{
		return false;
	}


	function check ()
	{
		if (!isset($this->_check))
		{
			$check_query  = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_BITBAYAR_STATUS'");
			$this->_check = tep_db_num_rows($check_query);
		}

		return $this->_check;
	}


	function install ()
	{
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) "
			."values ('Enable BitBayar Module', 'MODULE_PAYMENT_BITBAYAR_STATUS', 'False', 'Do you want to accept bitcoin payments via bitbayar.com?', '6', '0', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now());");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
			."values ('API Key', 'MODULE_PAYMENT_BITBAYAR_APITOKEN', '', 'Enter your API Token from your BitBayar Merchant', '6', '0', now());");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) "
			."values ('Unpaid Order Status', 'MODULE_PAYMENT_BITBAYAR_UNPAID_STATUS_ID', '" . intval(DEFAULT_ORDERS_STATUS_ID) .  "', 'Automatically set the status of unpaid orders to this value.', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) "
			."values ('Paid Order Status', 'MODULE_PAYMENT_BITBAYAR_PAID_STATUS_ID', '2', 'Automatically set the status of paid orders to this value.', '6', '0', 'tep_cfg_pull_down_order_statuses(', 'tep_get_order_status_name', now())");
		tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) "
			."values ('Sort Order of Display.', 'MODULE_PAYMENT_BITBAYAR_SORT_ORDER', '0', 'Sort order of display. Lowest is displayed first.', '6', '2', now())");
	}


	function remove ()
	{
		tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
	}

	function keys()
	{
		return array(
			'MODULE_PAYMENT_BITBAYAR_STATUS',
			'MODULE_PAYMENT_BITBAYAR_APITOKEN',
			'MODULE_PAYMENT_BITBAYAR_UNPAID_STATUS_ID',
			'MODULE_PAYMENT_BITBAYAR_PAID_STATUS_ID',
			'MODULE_PAYMENT_BITBAYAR_SORT_ORDER');
	}
}