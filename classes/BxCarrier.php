<?php
/**
 * Tenemos la classe BxCarrier
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BxCarrierModule
 * @package   BxCarrier
 * @Version   0.1.0
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

require_once dirname(__FILE__) . '/BxCarrierModel.php';

/**
 * BxCarrier
 * @author   Blue Express
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxCarrierModule
 * @package  BxCarrier
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

class BxCarrier
{
    public $id_local; /* ID de Obj Carrier en PS */
    public $id_remote; /* ID de Carrier en Enviopack */
    public $has_relaypoint; /* Indica si tiene sucursales */
    public $id_db; /* ID en tabla: enviopack_carrier */
    public $service_type; /* Tipo de servicio: N/P/R/C */
    public $modality; /* Modalidad: D/S */
    public $description; /* Nombre a mostrar en el combo del admin */

    private $model;

    public function __construct($id_local_carrier = null, $id_db = null)
    {
        $this->model = new BxCarrierModel();

        if ($id_local_carrier > 0) {
            $this->id_local = $id_local_carrier;
            $this->id_remote = $this->model->getValue(
                "id_remote_carrier",
                "id_local_carrier=" . $id_local_carrier
            );
            $this->id_db = $this->model->getValue(
                "id_carrier",
                "id_local_carrier=" . $id_local_carrier
            );

            if ($this->id_db <= 0) {
                return;
            }

            $this->modality = $this->model->getValue(
                "modality",
                "id_carrier=" . $this->id_db
            );
            $this->service_type = $this->model->getValue(
                "service_type",
                "id_carrier=" . $this->id_db
            );
            $this->description = $this->model->getValue(
                "description",
                "id_carrier=" . $this->id_db
            );
        } elseif ($id_db > 0) {
            $this->id_db = $id_db;
            $this->id_remote = $this->model->getValue(
                "id_remote_carrier",
                "id_carrier=" . $id_db
            );
            $this->id_local = $this->model->getValue(
                "id_local_carrier",
                "id_carrier=" . $id_db
            );

            $this->modality = $this->model->getValue(
                "modality",
                "id_carrier=" . $this->id_db
            );
            $this->service_type = $this->model->getValue(
                "service_type",
                "id_carrier=" . $this->id_db
            );
            $this->description = $this->model->getValue(
                "description",
                "id_carrier=" . $this->id_db
            );
        }
    }

    public function add($active = 0)
    {
        $idL = $this->id_local;
        $idR = $this->id_remote;
        $hR  = $this->has_relaypoint;
        $sT  = $this->service_type;
        $md  = $this->modality;

        if (isset($idL) && isset($idR) && isset($hR) && isset($sT) && isset($md)) {
            $this->model->addCarrier(
                $this->id_local,
                $this->id_remote,
                $this->has_relaypoint,
                $this->service_type,
                $this->modality,
                $this->description,
                $active
            );

            return true;
        }

        return false;
    }

    public function update()
    {
        $data = array(
            "id_local_carrier" => $this->id_local,
            "id_remote_carrier" => $this->id_remote,
            "has_relaypoint" => $this->has_relaypoint,
            "service_type" => $this->service_type,
            "modality" => $this->modality,
        );

        $this->model->update($data, "id_carrier=" . $this->id_db);
    }

    public function delete()
    {
        $this->model->delete($this->id_db);
    }

    public function hasRelayPoints()
    {
        $has_relay = $this->model->getValue(
            "has_relaypoint",
            "id_carrier=" . $this->id_db
        );
        return $has_relay;
    }

    public function getCarrierIdForOrder($order_id)
    {
        $carrier_id = $this->model->getCarrierIdForOrder($order_id);
        return $carrier_id;
    }

    public function getCarrierRow($carrier_id)
    {
        $carrier_row = $this->model->getCarrierRow($carrier_id);
        return $carrier_row;
    }

    public function getrelayCarrier()
    {
        $relay = $this->model->getrelayCarrierId();
        return $relay;
    }

    public function activate()
    {
        $data = array("active" => 1);
        $this->model->update($data, "id_carrier=" . $this->id_db);
    }
}
