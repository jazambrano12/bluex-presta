<?php
/**
 * Tenemos la classe BxCartHelper
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BxCartHelperModule
 * @package   BxCartHelper
 * @Version   0.1.0
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

/**
 * BxCartHelper
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxCartHelperModule
 * @package  BxCartHelper
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */
class BxCartHelper
{

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

            $pweight = $product['weight'];
            $cqty = $product['cart_quantity'];
            $weight += (pweight> 0 ? ($pweight * $multiplier) : $defWeight) * $cqty;
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
            $cqty = $product['cart_quantity'];
            $width = $product['width'] ;
            $height = $product['height'];
            $depth = $product['depth'];
            for ($i = 0; $i < $cqty; $i++) {
                if (min($width, $height, $depth) <= 0) {
                    $dimensions[] = array(
                        'width' => Configuration::get("BX_DEF_WIDTH"),
                        'height' => Configuration::get("BX_DEF_HEIGHT"),
                        'depth' => Configuration::get("BX_DEF_DEPTH"),
                    );
                } else {
                    $dimensions[] = array(
                        'width' => $product['width'],
                        'height' => $product['height'],
                        'depth' => $product['depth'],
                    );
                }
            }
        }

        return $dimensions;
    }
}
