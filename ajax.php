<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/classes/BxRelayManager.php';
require_once dirname(__FILE__) . '/classes/BxApi.php';
require_once dirname(__FILE__) . '/classes/BxOrderModel.php';
require_once dirname(__FILE__) . '/classes/BxCartHelper.php';

$relay_manager = new BxRelayManager();

$BxApi = BxApi::getInstance();

if (Configuration::get('BX_CLIENT') && Configuration::get('BX_TOKEN') && Configuration::get('BX_USERCODE') && Configuration::get('BX_APIKEY')) {
    $BxApi->setApiKey(Configuration::get('BX_CLIENT'));
    $BxApi->setSecretKey(Configuration::get('BX_TOKEN'));
    $BxApi->setUserCode(Configuration::get('BX_USERCODE'));
    $BxApi->setBxKey(Configuration::get('BX_APIKEY'));
}

switch (Tools::getValue('method')) {
    /* Devuelve la lista de sucursales, dependiendo de la localidad */
    case 'getRelayPoints':
        $locality = Tools::getValue('locality');
        $id_carrier = Tools::getValue('id_carrier');
        $weight = Tools::getValue('weight');

        $cart = Context::getContext()->cart;
        $dimesions = BxCartHelper::getCartDimensions($cart);

        $address = new Address($cart->id_address_delivery);
        $state = new State($address->id_state);
        $province = $state->iso_code;

        $relaypoints = $relay_manager->getRelayPointByLocality($locality, $id_carrier);
        $relaypoint_list = [];
        foreach ($relaypoints as $relaypoint) {
            $response = $BxApi->getCotizacionSucursal($relaypoint['id_remote_carrier'], $province, $locality, $weight, $dimesions, $cart->getOrderTotal(true, 4));

            if (!empty($response)) {
                if (array_key_exists('valor', $response[0])) {
                    $cost = $response[0]['valor'];
                    $time = $response[0]['horas_entrega'];

                    $relaypoint['cost'] = $cost;
                    $relaypoint['time'] = $time;
                    $relaypoint_list[] = $relaypoint;
                }
            }
        }

        echo json_encode($relaypoint_list);
        break;

        /* Devuelve la sucursal (temporal) a donde realizar el envio */
    case 'getRelayPoint':
        $id_cart = Tools::getValue('enviopack_cart_id');

        $relay = $relay_manager->getShippingRelaypoint($id_cart);

        echo json_encode(['status' => 'ok', 'data' => $relay]);

        break;

        /* Establece la sucursal a donde realizar el envio */
    case 'setRelayPoint':
        $id_cart = Tools::getValue('enviopack_cart_id');
        $relay = [
            'office_id' => Tools::getValue('office_id'),
            'office_address' => Tools::getValue('office_address'),
            'office_service' => Tools::getValue('office_service'),
            'office_price' => Tools::getValue('office_price'),
            'office_name' => Tools::getValue('office_name'),
        ];

        $relay_manager->setShippingRelaypoint($id_cart, $relay);

        echo json_encode(['status' => 'ok']);

        break;
        /* Actualiza la direccion de un pedido */
    case 'updateOrder':
        $orderModel = new BxOrderModel();

        $key = Tools::getValue('key');
        $val = Tools::getValue('val');
        $id = Tools::getValue('id');

        $data = [$key => '$val'];

        $orderModel->update($data, 'id_ps_order=' . $id);

        break;
        /* Actualiza la direccion de un pedido */
    case 'setOrderCarrier':
        $orderModel = new BxOrderModel();

        $carrier = Tools::getValue('carrier');
        $id = Tools::getValue('id');

        $data = ['carrier_id' => $carrier];
        $orderModel->update($data, 'id_order=' . $id);

        break;

        /* Obtiene el la etiqueta de un pedido */
    case 'getOrderLabel':
        $ids = Tools::getValue('selected');
        header('Content-type: application/pdf', 'Content-Disposition: attachment; filename = downloaded.pdf');

        echo $BxApi->get_labels($ids);

        break;

        /* Webhook para cambiar el estado */
        /* DEPRECADO */
    case 'shipmentProcessed':
        $id = Tools::getValue('id');

        if ($id > 0) {
            $orderModel = new BxOrderModel();

            $order_row = $orderModel->get('id_shipment=' . $id);
            $order = new Order($order_row['id_order']);
            $order->setCurrentState((int) Configuration::get('BX_DEF_STATE'));

            $response = $BxApi->getShipment($id);
            if (is_array($response)) {
                $order->setWsShippingNumber($response['tracking_number']);
            } else {
                echo $response;
            }
        }
        break;

    case 'webhook':
        $id = Tools::getValue('id');
        $tipo = Tools::getValue('tipo');

        if ($id > 0 && ($tipo == 'envio-procesado' || $tipo == 'envio-cambio-condicion')) {
            $orderModel = new BxOrderModel();

            $order_row = $orderModel->get('id_shipment=' . $id);
            $order_id = isset($order_row['id_ps_order']) ? $order_row['id_ps_order'] : $order_row['id_order'];
            $order = new Order($order_id);
            if ($tipo == 'envio-procesado') {
                $order->setCurrentState((int) Configuration::get('BX_DEF_STATE'));
            }

            $response = $BxApi->getShipment($id);
            if (is_array($response)) {
                $order->setWsShippingNumber($response['tracking_number']);
            } else {
                echo $response;
            }
        }

        break;

        /* Establece el carrier a un pedido */
    case 'setCarrier':
        $orderModel = new BxOrderModel();

        $id_order = Tools::getValue('order');
        $id_carrier = Tools::getValue('carrier');

        $data = ['carrier_id' => $id_carrier];

        $orderModel->update($data, 'id_ps_order=' . $id_order);

        echo json_encode(['status' => 'ok']);
        break;

        /* Set a relay to an order */
    case 'RegisterRelay':
        echo json_encode(['status' => 'cagate']);
        break;

    default:
        break;
}

function getCartWeight($cart, $id_carrier)
{
    $defWeight = Configuration::get('BX_DEF_WEIGHT');

    $products = $cart->getProducts();
    $weight = 0;

    switch (Configuration::get('PS_WEIGHT_UNIT')) {
        case 'lb':
            $multiplier = 0.453592;
            break;
        case 'g':
            $multiplier = 0.001;
            break;
        case 'kg':
        default:
            $multiplier = 1;
            break;
    }
    foreach ($products as $product) {
        $productObj = new Product($product['id_product']);
        $carriers = $productObj->getCarriers();
        $isProductCarrier = false;

        foreach ($carriers as $carrier) {
            if (!$id_carrier || $carrier['id_carrier'] == $id_carrier) {
                $isProductCarrier = true;
                continue;
            }
        }

        if ($product['is_virtual'] or (count($carriers) && !$isProductCarrier)) {
            continue;
        }

        $weight += ($product['weight'] > 0 ? ($product['weight'] * $multiplier) : $defWeight) * $product['cart_quantity'];
    }

    return $weight;
}

/**
 * Devuelve un array con las dimensiones de cada productos del carrito
 * @param  [Cart ] $cart
 * @return [array]
 */
function getCartDimensions($cart)
{
    $products = $cart->getProducts();
    $dimensions = [];
    foreach ($products as $product) {
        for ($i = 0; $i < $product['cart_quantity']; ++$i) {
            if (min($product['width'], $product['height'], $product['depth']) <= 0) {
                $dimensions[] = [
                    'width' => Configuration::get('BX_DEF_WIDTH'),
                    'height' => Configuration::get('BX_DEF_HEIGHT'),
                    'depth' => Configuration::get('BX_DEF_DEPTH'),
                ];
            } else {
                $dimensions[] = [
                    'width' => $product['width'],
                    'height' => $product['height'],
                    'depth' => $product['depth'],
                ];
            }
        }
    }

    return $dimensions;
}
exit;
