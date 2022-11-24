<?php
/**
 * Tenemos la classe BxRelayManager
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BxRelayManagerModul
 * @Version   0.1.0
 */
require_once dirname(__FILE__) . '/BxRelayPointModel.php';
require_once dirname(__FILE__) . '/BxCarrierManager.php';
require_once dirname(__FILE__) . '/BxRelayPoint.php';

/**
 * BxRelayManager
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BxRelayManagerModule
 * @Version   0.1.0
 */
class BxRelayManager
{
    private $relaypoint_model;

    public function __construct()
    {
        $this->relaypoint_model = new BxRelayPointModel();
    }

    /* Crea las sucursales */
    public function installRelayPoints($relay_points, $force_carrier_id)
    {
        foreach ($relay_points as $remote_relaypoint) {
            $carrier = new BxCarrier($force_carrier_id);
            if (!$this->relayExist($remote_relaypoint['id'])) {
                $relay_point = new BxRelayPoint();
                $relay_point->id_db_carrier = $carrier->id_db;
                $relay_point->id_remote_relay = $remote_relaypoint['id'];
                $relay_point->id_remote_carrier = $remote_relaypoint['correo']['id'];
                $relay_point->description = $remote_relaypoint['nombre'];
                $relay_point->street = $remote_relaypoint['calle'];
                $relay_point->number = $remote_relaypoint['numero'];
                $relay_point->floor = $remote_relaypoint['piso'];
                $relay_point->department = $remote_relaypoint['depto'];
                $relay_point->postal_code = $remote_relaypoint['codigo_postal'];
                $relay_point->locality = $remote_relaypoint['localidad']['id'];
                $relay_point->latitude = $remote_relaypoint['latitud'];
                $relay_point->longitude = $remote_relaypoint['longitud'];

                $relay_point->add();
            }
        }
    }

    /* Verifica la existencia de un relaypoint localmente */
    private function relayExist($id_remote_relay)
    {
        $relay_point = $this->relaypoint_model->getRelaypoint('id_remote_relay=' . $id_remote_relay);

        if (is_array($relay_point)) {
            return true;
        }

        return false;
    }

    /* Devuelve los relaypoint de un carrier */
    public function getRelayPoints($carrier)
    {
        return $this->relaypoint_model->getRelaypointList($carrier->id_db);
    }

    /* Guarda temporalmente la relación del carrito con el punto de relay */
    public function setShippingRelaypoint($id_cart, $relay)
    {
        if ($relay['office_id'] === -1) {
            $this->relaypoint_model->deleteShippingRelaypoint($id_cart);
        } else {
            if (is_array($this->relaypoint_model->getShippingRelaypoint($id_cart))) {
                $this->relaypoint_model->updateShippingRelaypoint($id_cart, $relay);
            } else {
                $this->relaypoint_model->addShippingRelaypoint($id_cart, $relay);
            }
        }
    }

    /* Elimina la relacion */
    public function deleteShippingRelaypoint($id_cart)
    {
        $this->relaypoint_model->deleteShippingRelaypoint($id_cart);
    }

    /* Devuelve el relaypoint temporal */
    public function getShippingRelaypoint($id_cart)
    {
        $relaypoint_row = $this->relaypoint_model->getShippingRelaypoint($id_cart);
        if (!empty($relaypoint_row)) {
            return $relaypoint_row;
        }
        return null;
    }

    public function getRelayPointById($id)
    {
        return $this->relaypoint_model->getRelaypoint('id_relaypoint=' . $id);
    }

    /* Devuelve las sucursales de una localidad */
    public function getRelayPointByLocality($locality_id, $id_carrier)
    {
        return $this->relaypoint_model->getRelaypoints('locality=' . $locality_id . ' and id_carrier=' . $id_carrier);
    }

    public function getRemoteCarrierId($id_relaypoint)
    {
        return $this->relaypoint_model->getColumn('id_remote_carrier', 'id_relaypoint=' . $id_relaypoint);
    }
}
