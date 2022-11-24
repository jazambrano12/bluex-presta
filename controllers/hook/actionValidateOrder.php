<?php
/**
 * Tenemos el Controller BlueExpress Action Validate Order
 * PHP versions 7.x
 *
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BlueexpressActionValidateOrderController
 * @Version   0.1.0
 */
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxApi.php';
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxOrder.php';
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxOrderModel.php';
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxCarrier.php';
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxAddress.php';
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxRelayManager.php';
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxShipment.php';

/**
 * BlueExpress Action Validate Order
 *
 * @category BlueexpressActionValidateOrderController
 * @author   BlueExpress
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @Version  0.1.0
 */
class BlueexpressActionValidateOrderController
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
        $id_carrier = $params['order']->id_carrier;
        $carrier = new BxCarrier($id_carrier);
        if ($carrier->id_db > 0 && $carrier->modality === 'S') {
            $order = $params['order'];
            $cart = $params['cart'];

            // Retrieve default order address and fetch its ID
            $address = new Address($cart->id_address_delivery);
            $id_address_delivery = (int) $address->id;

            // Retrieve DPD Pickup point selection
            $relay_manager = new BxRelayManager();
            $relay_address = $relay_manager->getShippingRelaypoint($cart->id);

            // DPD Pickup address will become one of customer's
            if (!empty($relay_address)) {
                $new_address = new Address();
                $new_address->id_customer = $address->id_customer;
                $new_address->lastname = $address->lastname;
                $new_address->firstname = $address->firstname;
                $new_address->company = $relay_address['name'];
                $new_address->address1 = $relay_address['address'];
                $new_address->address2 = '';
                $new_address->postcode = $address->postcode;
                $new_address->city = $address->city;
                $new_address->phone = $address->phone;
                $new_address->phone_mobile = $address->phone_mobile;
                $new_address->id_country = $address->id_country;
                $new_address->alias = 'Sucursal de envío';
                $new_address->deleted = 1;
                $new_address->add();
                $id_address_delivery = (int) $new_address->id;
            }

            // Update order
            $order->id_address_delivery = $id_address_delivery;
            $order->update();
        }
    }
}
