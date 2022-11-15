<?php
/**
 * Tenemos el Controller BlueExpress Get Order Shipping Cost
 * PHP versions 7.x
 *
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BlueExpressGetOrderShippingCostController
 * @package   BlueExpressGetOrderShippingCost 
 * @Version   0.1.0
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

require_once dirname(__FILE__) . '/../../classes/BxCarrier.php';
require_once dirname(__FILE__) . '/../../classes/BxRelayManager.php';
require_once dirname(__FILE__) . '/../../classes/BxCartHelper.php';

/**
 * BlueExpress Get Order Shipping Cost
 *
 * @category BlueExpressGetOrderShippingCostController
 * @package  BlueExpressGetOrderShippingCost
 * @author   BlueExpress
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */
class BlueExpressGetOrderShippingCostController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    public function run($cart, $shipping_cost)
    {
        foreach ($cart->getProducts() as $indice => $product) {

            $itemCart[] = [
                'largo' => $product['depth'],
                'ancho' => $product['width'],
                'alto' => $product['height'],
                'pesoFisico' => $product['weight'],
                'cantidad' => (int) $product['cart_quantity'],
            ];
        }

        $address = new Address($cart->id_address_delivery);
        $carrier = new BxCarrier($this->module->id_carrier);
        if (!empty($address) && $address->city) {
            $city = $address->city;
        } else {
            $city = '';
        }

        $cart_weight = $this->getCartWeight($cart, $carrier->id_local);
        $dimensions = BxCartHelper::getCartDimensions($cart);
        $state = new State($address->id_state);

        $this->module->BxApi->setUserCode(Configuration::get('BX_USERCODE'));
        $this->module->BxApi->setApiKey(Configuration::get('BX_CLIENT'));
        $this->module->BxApi->setSecretKey(Configuration::get('BX_TOKEN'));
        $this->module->BxApi->setBxKey(Configuration::get('BX_APIKEY'));
        /***
         * ENVIAMOS LOS DATOS PARA CONSULTAR EL PRECIO DEL DESPACHO
         */
        $cost = $this->module->BxApi->getCotizacionADomicilio(
            $city,
            $cart_weight, 
            $carrier->service_type, 
            $itemCart
        );
        if ($shipping_cost > 0) {
            return $shipping_cost + $cost;
        }

        return $cost;
    }

    private function get_current_relaycost($id_cart)
    {
        $relay_manager = new BxRelayManager();
        $cost = $relay_manager->getShippingRelaypoint($id_cart);
        return isset($cost['price']) ? $cost['price'] : Configuration::get("BX_BRANCH_PRICE");
    }

    private function get_relaycost($cart, $province_id, $cart_weight, $products_dimensions)
    {
        $relay_manager = new BxRelayManager();
        $relaypoint = $relay_manager->getShippingRelaypoint($cart->id);

        // Se establece un valor alto para que el envio a sucursal siempre quede ultimo
        if ($this->context->controller->php_self === 'order') {
            $cost = 0;
        } else {
            $cost = false;
        }

        if (!empty($relaypoint->id)) {
            $cost = Configuration::get('BX_DEF_PRICE');
            // TODO: Agregar locality_id
            $response = $this->module->BxApi->getCotizacionSucursal(
                $relaypoint->id_remote_carrier,
                $province_id,
                $locality_id,
                $cart_weight,
                $products_dimensions,
                $cart->getOrderTotal(true, 4)
            );

            if (!empty($response)) {
                if (array_key_exists('valor', $response[0])) {
                    $cost = $response[0]['valor'];
                }
            }
        }

        return $cost;
    }

    public static function getCartWeight($cart, $id_carrier)
    {
        $defWeight = Configuration::get("BX_DEF_WEIGHT");

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
    public static function getCartDimensions($cart)
    {
        $products = $cart->getProducts();
        $dimensions = array();

        foreach ($products as $product) {
            if (min($product['width'], $product['height'], $product['depth']) <= 0) {
                $dimensions[] = array(
                    'width' => Configuration::get("ENVIOPACK_DEF_WIDTH"),
                    'height' => Configuration::get("ENVIOPACK_DEF_HEIGHT"),
                    'depth' => Configuration::get("ENVIOPACK_DEF_DEPTH"),
                );
            } else {
                $dimensions[] = array(
                    'width' => $product['width'],
                    'height' => $product['height'],
                    'depth' => $product['depth'],
                );
            }
        }

        return $dimensions;
    }
}
