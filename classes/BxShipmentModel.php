<?php
/**
 * Tenemos la classe BxShipmentModel
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BxShipmentModelModule
 * @package   BxShipmentModel
 * @Version   0.1.0
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

/**
 * BxShipmentModel
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxShipmentModelModule
 * @package  BxShipmentModel
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

class BxShipmentModel
{
    protected $shipment;
    protected $shipment_address;
    protected $shipment_relay;

    public function __construct()
    {
        $this->shipment = _DB_PREFIX_ . "blueexpress_shipment";
        $this->shipment_address = _DB_PREFIX_ . "blueexpress_shipment_address";
        $this->shipment_relay = _DB_PREFIX_ . "blueexpress_shipment_relay";
    }

    public function addShipment($shipment_detail)
    {
        try {
            Db::getInstance()->insert("blueexpress_shipment", array(
                "id_remote_order" => $shipment_detail["pedido"],
                "source_address" => $shipment_detail["direccion_envio"],
                "receiver" => $shipment_detail["destinatario"],
                "comments" => $shipment_detail["observaciones"],
                "service" => $shipment_detail["servicio"],
                "carrier_id" => $shipment_detail["correo"],
                "confirmated" => $shipment_detail["confirmado"],
                "package" => serialize($shipment_detail["paquetes"]),
                "modality" => $shipment_detail["modalidad"],
                "status" => $shipment_detail["status"]
            ));

            return Db::getInstance()->insert_id();
        } catch (Exception $e) {
            PrestaShopLogger::AddLog(__FILE__ . " $e");
        }
    }

    // TODO: actualizar las direcciones de los pedidos pendientes
    // cuando el usuario actualiza su direccion
    public function addAddress($address)
    {
        try {
            Db::getInstance()->insert(
                "blueexpress_shipment_address",
                array(
                    "id_shipment" => $address["id_shipment"],
                    "street" => $address["calle"],
                    "number" => $address["numero"],
                    "floor" => $address["piso"],
                    "department" => $address["depto"],
                    "postal_code" => $address["codigo_postal"],
                    "state" => $address["provincia"],
                    "locality" => $address["localidad"],
                )
            );
        } catch (Exception $e) {
            PrestaShopLogger::AddLog(__FILE__ . " $e");
        }
    }

    public function addRelay($relaypoint)
    {
        try {
            Db::getInstance()->insert(
                "blueexpress_shipment_relay",
                array(
                    "id_shipment" => $relaypoint['id_shipment'],
                    "id_relaypoint" => $relaypoint['id_relaypoint']
                )
            );
        } catch (Exception $e) {
            tools::p($e);
            PrestaShopLogger::AddLog(__FILE__ . " $e");
        }
    }

    public function getShipment($remote_id)
    {
        $SQL = "SELECT * FROM " . $this->shipment . " WHERE id_remote_order=" . $remote_id;
        $shipment = Db::getInstance()->getRow($SQL);

        return $shipment;
    }

    public function setStatus($status, $id_shipment)
    {
        try {
            Db::getInstance()->update(
                "blueexpress_shipment",
                array("status" => $status),
                "id_shipment=" . $id_shipment
            );
        } catch (Exception $e) {
            PrestaShopLogger::AddLog(__FILE__ . " $e");
        }
    }
}
