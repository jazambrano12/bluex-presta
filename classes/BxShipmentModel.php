<?php
/**
 * Tenemos la classe BxShipmentModel
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BxShipmentModelModule
 * @Version   0.1.0
 */

/**
 * BxShipmentModel
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxShipmentModelModule
 * @Version  0.1.0
 */
class BxShipmentModel
{
    protected $shipment;
    protected $shipment_address;
    protected $shipment_relay;

    public function __construct()
    {
        $this->shipment = _DB_PREFIX_ . 'blueexpress_shipment';
        $this->shipment_address = _DB_PREFIX_ . 'blueexpress_shipment_address';
        $this->shipment_relay = _DB_PREFIX_ . 'blueexpress_shipment_relay';
    }

    public function addShipment($shipment_detail)
    {
        try {
            Db::getInstance()->insert(
                'blueexpress_shipment',
                [
                    'id_remote_order' => pSQL($shipment_detail['pedido']),
                    'source_address' => pSQL($shipment_detail['direccion_envio']),
                    'receiver' => pSQL($shipment_detail['destinatario']),
                    'comments' => pSQL($shipment_detail['observaciones']),
                    'service' => pSQL($shipment_detail['servicio']),
                    'carrier_id' => (int) $shipment_detail['correo'],
                    'confirmated' => pSQL($shipment_detail['confirmado']),
                    'package' => pSQL(serialize($shipment_detail['paquetes'])),
                    'modality' => pSQL($shipment_detail['modalidad']),
                    'status' => pSQL($shipment_detail['status']),
                ]
            );

            return Db::getInstance()->insert_id();
        } catch (Exception $e) {
            PrestaShopLogger::AddLog(__FILE__ . ' $e');
        }
    }

    // TODO: actualizar las direcciones de los pedidos pendientes
    // cuando el usuario actualiza su direccion
    public function addAddress($address)
    {
        try {
            Db::getInstance()->insert(
                'blueexpress_shipment_address',
                [
                    'id_shipment' => (int) $address['id_shipment'],
                    'street' => pSQL($address['calle']),
                    'number' => pSQL($address['numero']),
                    'floor' => pSQL($address['piso']),
                    'department' => pSQL($address['depto']),
                    'postal_code' => pSQL($address['codigo_postal']),
                    'state' => pSQL($address['provincia']),
                    'locality' => pSQL($address['localidad']),
                ]
            );
        } catch (Exception $e) {
            PrestaShopLogger::AddLog(__FILE__ . ' $e');
        }
    }

    public function addRelay($relaypoint)
    {
        try {
            Db::getInstance()->insert(
                'blueexpress_shipment_relay',
                ['id_shipment' => (int) $relaypoint['id_shipment'], 'id_relaypoint' => (int) $relaypoint['id_relaypoint']]
            );
        } catch (Exception $e) {
            tools::p($e);
            PrestaShopLogger::AddLog(__FILE__ . ' $e');
        }
    }

    public function getShipment($remote_id)
    {
        $SQL = 'SELECT * FROM ' . $this->shipment . ' WHERE id_remote_order=' . $remote_id;
        $shipment = Db::getInstance()->getRow($SQL);

        return $shipment;
    }

    public function setStatus($status, $id_shipment)
    {
        try {
            Db::getInstance()->update(
                'blueexpress_shipment',
                ['status' => $status],
                'id_shipment=' . $id_shipment
            );
        } catch (Exception $e) {
            PrestaShopLogger::AddLog(__FILE__ . ' $e');
        }
    }
}
