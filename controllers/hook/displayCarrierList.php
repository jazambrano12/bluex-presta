<?php
/**
 * Tenemos el Controller BlueExpress Display Carrier List
 * PHP versions 7.x
 *
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BlueexpressDisplayCarrierListController
 * @package   BlueexpressDisplayCarrierList 
 * @Version   0.1.0
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

require_once dirname(__FILE__) . '/../../classes/BxCarrier.php';
require_once dirname(__FILE__) . '/../../classes/BxRelayManager.php';

/**
 * BlueExpress Display Carrier List
 *
 * @category BlueexpressDisplayCarrierListController
 * @package  BlueexpressDisplayCarrierList
 * @author   BlueExpress
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */
class BlueexpressDisplayCarrierListController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
        $this->epack_relay_manager = new BxRelayManager();
    }

    public function run($params)
    {

        $address = new Address($params['cart']->id_address_delivery);
        $state = new State($address->id_state);
        $id_carrier = new BxCarrier();
        $id_carrier = $id_carrier->getrelayCarrier();

        $weight = $this->getCartWeight($params['cart'], $params['cart']->id_carrier);
        $dimensions = BxCartHelper::getCartDimensions($params['cart']);

        $ajax_url = _PS_BASE_URL_ . __PS_BASE_URI__;
        $ajax_url = rtrim($ajax_url, '/') . '/modules/bluex/ajax.php';

        $this->context->smarty->assign('bluex_cart_id', $params['cart']->id);
        $this->context->smarty->assign('bluex_postcode', $address->postcode);
        $this->context->smarty->assign('bluex_weight', $weight);
        $this->context->smarty->assign('bluex_id_carrier_local', $id_carrier);
        $this->context->smarty->assign('bluex_select_relay', true);
        $this->context->smarty->assign('bluex_maps_api_key', 'AIzaSyDuhF23s4P90AFdaW-ffxcAAMgbu-oKDCQ');
        $this->context->smarty->assign('bluex_branch_price', Configuration::get("ENVIOPACK_BRANCH_PRICE"));
        $this->context->smarty->assign('bluex_ajax_url', $ajax_url);

        return $this->module->display($this->file, 'displayCarrierList.tpl');

    }

    public function getCartWeight($cart, $id_carrier)
    {
        $defWeight = Configuration::get("ENVIOPACK_DEF_WEIGHT");

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

}
