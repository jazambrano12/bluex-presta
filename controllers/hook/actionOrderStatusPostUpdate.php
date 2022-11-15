<?php
/**
 * Tenemos el Controller BlueExpress Action Order Status Post Update
 * PHP versions 7.x 
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BlueExpressActionOrderStatusPostUpdateController
 * @package   BxAddressActionOrderStatusPostUpdate 
 * @Version   0.1.0
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

require_once _PS_MODULE_DIR_ . '/bluex/classes/BxOrderModel.php';
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxCarrier.php';
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxAddress.php';
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxApi.php';
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxOrder.php';
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxRelayManager.php';
require_once _PS_MODULE_DIR_ . '/bluex/classes/BxShipment.php';

/**
 * Order Status Post Update 
 * @category BlueExpressActionOrderStatusPostUpdateController
 * @package  BxAddressActionOrderStatusPostUpdate
 * @author   BlueExpress
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

class BlueExpressActionOrderStatusPostUpdateController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
        $this->palabraClavesPrevias = array('de', 'calle', 'pje', 'pje.', 'pasaje', 'prov', 'prov.', 'provincial', 'interprovincial', 'diag', 'diag.', 'diagonal', 'ruta', 'av', 'av.', 'avenida', 'peat', 'peat.', 'peatonal', 'entre', 'y', 'regimiento', 'esquina', 'esq', 'esq.');
        $this->palabraClavesPosteriores = array('de', 'km', 'y');
        $this->BxApi = BxApi::getInstance();
        $this->BxApi->setApiKey(Configuration::get('BX_CLIENT'));
        $this->BxApi->setSecretKey(Configuration::get('BX_TOKEN'));
        $this->BxApi->setUserCode(Configuration::get('BX_USERCODE'));
        $this->BxApi->setBxKey(Configuration::get('BX_APIKEY'));
    }

    public function run($params)
    {
        $config_state = Configuration::get('BX_PAID_STATE');

        if (trim($params['newOrderStatus']->id) == $config_state) {

            $bx_ordermodel = new BxOrderModel();

            $order_id = $params['id_order'];
            $order = new Order($order_id);

            $carrier = new BxCarrier($order->id_carrier);
            if ($carrier->id_db > 0) {

                $delivery_address = new Address($order->id_address_delivery);

                $splitAddress = BxAddress::getInstance()->splitAddress($delivery_address);

                $street = $splitAddress['calle'];
                $number = $splitAddress['numero'];
                $floor = $splitAddress['piso'];
                $depto = $splitAddress['depto'];

                $order_check = $bx_ordermodel->get("id_ps_order=" . $order_id);

                if (empty($order_check)) {
                    $bx_ordermodel->add($order_id, $street, $number, $floor, $depto);
                }

            }

            // Now we process this order
            $order_extradata = $bx_ordermodel->get("id_ps_order=" . $order->id);
            $cart = new Cart($order->id_cart);
            $shipping = $order->getShipping();
            $products = $order->getProducts();
            $customer = new Customer($cart->id_customer);

            $package = [];

            foreach ($products as $pr) {
                $package[] = [
                    'name' => $pr['product_name'],
                    'width' => $pr['width'],
                    'length' => $pr['depth'],
                    'weight' => $pr['weight'],
                    'height' => $pr['height'],
                    'quantity' => $pr['product_quantity'],
                ];
            }
            $delivery_state = new State($delivery_address->id_state);
            $currency = new Currency($order->id_currency);
            $idOrderCarrier = $bx_ordermodel->getOrderCarrier("id_order_carrier ", "id_order=" . $order->id);

            if ($order_extradata['id_bx_order'] < 1) {
                /**
                 *ENVIO EL DETALLE DE LA ORDEN AL WEBHOOK 
                 */
                $orderDetail = [
                    "Domain" => "Prestashop",
                    "order_id" => $order->id,
                    "idCarrier" => $order->id_carrier,
                    "idOrderCarrier" => $idOrderCarrier,
                    "name" => $delivery_address->firstname,
                    "lastname" => $delivery_address->lastname,
                    "email" => $customer->email,
                    "price" => round($order->total_paid, 2),
                    "paid_out" => true,
                    "service_type" => $shipping[0]['carrier_name'],
                    "service_cost" => $shipping[0]['shipping_cost_tax_incl'],
                    "address" => $delivery_address->address1,
                    "currency" => $currency->iso_code,
                    "package" => json_encode($package),
                    "storeDomain" => _PS_BASE_URL_ . __PS_BASE_URI__,
                ];

                /**
                 * ENVIO EL DETALLE DE LA ORDEN AL WEBHOOK
                 */
                $webHook = $this->module->BxApi->postWebHook(json_encode($orderDetail));
            }

        }
    }
}
