<?php
/**
 * Tenemos el Controller BlueExpress Action Payment Confirmation
 * PHP versions 7.x
 *
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BlueexpressActionPaymentConfirmationController
 * @package   BlueexpressActionPaymentConfirmation
 * @Version   0.1.0
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

require_once _PS_MODULE_DIR_ . '/bluex/classes/BxOrderModel.php';
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxCarrier.php';
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxAddress.php';

/**
 * BlueExpress Action Payment Confirmation
 *
 * @category BlueexpressActionPaymentConfirmationController
 * @package  BlueexpressActionPaymentConfirmation
 * @author   BlueExpress
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

class BlueexpressActionPaymentConfirmationController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    public function run($params)
    {
        $order_model = new BxOrderModel();

        $order_id = $params['id_order'];
        $order = new Order($order_id);

        $carrier = new BxCarrier($order->id_carrier);

        if ($carrier->id_db > 0) {

            $address = new Address($order->id_address_delivery);

            $splitAddress = BxAddress::getInstance()->splitAddress($address);

            $street = $splitAddress['calle'];
            $number = $splitAddress['numero'];
            $floor = $splitAddress['piso'];
            $depto = $splitAddress['depto'];
            $order_model->add($order_id, $street, $number, $floor, $depto);
        }
    }
}
