<?php

require('includes/application_top.php');
require_once(dirname(__FILE__) . "/includes/modules/payment/CoinGate/init.php");
require_once(dirname(__FILE__) . "/includes/modules/payment/CoinGate/version.php");

$token = MODULE_PAYMENT_COINGATE_CALLBACK_SECRET;
if ($token == '' || $_GET['token'] != $token)
  throw new Exception('Token: ' . $_GET['token'] . ' do not match');

global $db;

$order_id = $_REQUEST['order_id'];

$order = $db->Execute("select orders_id from " . TABLE_ORDERS . " where orders_id = '" . intval($order_id) . "' limit 1");

if (!$order || !$order->fields['orders_id'])
  throw new Exception (strip_tags(('Order #' . $order_id . ' does not exists'));

$coingate_order = \CoinGate\Merchant\Order::findOrFail($_REQUEST['id'], array(), array(
  'app_id' => MODULE_PAYMENT_COINGATE_APP_ID,
  'api_key' => MODULE_PAYMENT_COINGATE_API_KEY,
  'api_secret' => MODULE_PAYMENT_COINGATE_API_SECRET,
  'environment' => MODULE_PAYMENT_COINGATE_TEST == "True" ? 'sandbox' : 'live',
  'user_agent' => 'CoinGate - ZenCart Extension v' . COINGATE_ZENCART_EXTENSION_VERSION));

switch ($coingate_order->status) {
  case 'paid':
    $cg_order_status = MODULE_PAYMENT_COINGATE_PAID_STATUS_ID;
    break;
  case 'canceled':
    $cg_order_status = MODULE_PAYMENT_COINGATE_CANCELED_STATUS_ID;
    break;
  case 'expired':
    $cg_order_status = MODULE_PAYMENT_COINGATE_EXPIRED_STATUS_ID;
    break;
  case 'invalid':
    $cg_order_status = MODULE_PAYMENT_COINGATE_INVALID_STATUS_ID;
    break;
  case 'refunded':
    $cg_order_status = MODULE_PAYMENT_COINGATE_REFUNDED_STATUS_ID;
    break;
  default:
    $cg_order_status = NULL;
}

if ($cg_order_status)
  $db->Execute("update ". TABLE_ORDERS. " set orders_status = " . $cg_order_status . " where orders_id = ". intval($coingate_order->order_id));

echo 'OK';
