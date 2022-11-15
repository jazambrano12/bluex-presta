<?php
/**
 * Tenemos la classe BxRelayPoint
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BxRelayPointModule
 * @package   BxRelayPoint
 * @Version   0.1.0
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

require_once dirname(__FILE__) . '/BxRelayPointModel.php';

/**
 * BxRelayPoint
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxRelayPointModule
 * @package  BxRelayPoint
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

class BxRelayPoint
{
    public $id;
    public $id_db_carrier;
    public $id_remote_relay;
    public $description;
    public $street;
    public $number;
    public $floor;
    public $department;
    public $postal_code;
    public $locality;
    public $latitude;
    public $longitude;
    public $id_remote_carrier;

    protected $relaypoint_model;

    public function __construct($id = null)
    {
        $this->relaypoint_model = new BxRelayPointModel();

        if (!is_null($id)) {
            $my_relay = $this->relaypoint_model->getRelaypoint("id_relaypoint=" . $id);

            if (is_array($my_relay)) {
                $this->id = $id;
                $this->id_db_carrier = $my_relay["id_carrier"];
                $this->id_remote_relay = $my_relay["id_remote_relay"];
                $this->description = $my_relay["description"];
                $this->street = $my_relay["street"];
                $this->number = $my_relay["number"];
                $this->floor = $my_relay["floor"];
                $this->department = $my_relay["department"];
                $this->postal_code = $my_relay["postal_code"];
                $this->locality = $my_relay["locality"];
                $this->latitude = $my_relay["latitude"];
                $this->longitude = $my_relay["longitude"];
                $this->id_remote_carrier = $my_relay["id_remote_carrier"];
            }
        }
    }

    public function add()
    {
        if ($this->id > 0) {
            return;
        }

        $relaypoint_detail = array("id_carrier" => $this->id_db_carrier,
            "id_remote_relay" => $this->id_remote_relay,
            "id_remote_carrier" => $this->id_remote_carrier,
            "description" => $this->description,
            "street" => $this->street,
            "number" => $this->number,
            "floor" => $this->floor,
            "department" => $this->department,
            "postal_code" => $this->postal_code,
            "locality" => $this->locality,
            "latitude" => $this->latitude,
            "longitude" => $this->longitude);

        $this->relaypoint_model->add($relaypoint_detail);
    }
}
