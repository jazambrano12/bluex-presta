<?php
/**
 * Tenemos la classe BxCarrierModel
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BxCarrierModelModule
 * @package   BxCarrierModel
 * @Version   0.1.0
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

/**
 * BxCarrierModel
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxCarrierModelModule
 * @package  BxCarrierModel
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

class BxCarrierModel
{
    protected $carrier;

    public function __construct()
    {
        $this->carrier = _DB_PREFIX_ . "blueexpress_carrier";
        $this->order = _DB_PREFIX_ . "blueexpress_order";
    }

    public function getrelayCarrierId()
    {
        $SQL = "SELECT `id_local_carrier`, `id_carrier`
        FROM " . $this->carrier . " WHERE `modality` = 'S' AND `active` = '1'";
        $db_id = Db::getInstance()->getValue($SQL);
        return $db_id;
    }

    public function getActiveCarriers()
    {
        $carrier_list = array();

        $SQL = "SELECT * FROM " . $this->carrier . " WHERE active=1;";

        if ($results = Db::getInstance()->ExecuteS($SQL)) {
            foreach ($results as $row) {
                $carrier_list[] = $row;
            }
        }

        return $carrier_list;
    }

    public function getAllCarriers()
    {
        $carrier_list = array();

        $SQL = "SELECT * FROM " . $this->carrier;

        if ($results = Db::getInstance()->ExecuteS($SQL)) {
            foreach ($results as $row) {
                $carrier_list[] = $row;
            }
        }

        return $carrier_list;
    }

    public function addCarrier($idL, $idR, $hasRel, $serT, $mod, $desc, $active = 1)
    {
        try {
            Db::getInstance()->insert(
                "blueexpress_carrier",
                array( "id_local_carrier" => $idL,
                "id_remote_carrier" => $idR,
                "has_relaypoint" => $hasRel,
                "service_type" => $serT,
                "modality" => $mod,
                "description" => $desc,
                "active" => $active,
                )
            );
        } catch (Exception $e) {
            PrestaShopLogger::addLog(__FILE__ . " $e");
            return false;
        }

        return true;
    }

    public function getCarrierIdByRemote($id_remote)
    {
        $SQL = "SELECT id_carrier 
        FROM " . $this->carrier . " WHERE id_remote_carrier='" . $id_remote . "'";
        $db_id = Db::getInstance()->getValue($SQL);

        return $db_id;
    }

    public function getCarrierIdForOrder($order_id)
    {
        $SQL = "SELECT carrier_id 
        FROM " . $this->order . " WHERE id_ps_order='" . $order_id . "'";
        $db_id = Db::getInstance()->getValue($SQL);

        return $db_id;
    }

    public function getCarrierRow($id_carrier)
    {
        $SQL = "SELECT * 
        FROM " . $this->carrier . " WHERE id_carrier='" . $id_carrier . "'";
        $res = false;
        if ($results = Db::getInstance()->ExecuteS($SQL)) {
            foreach ($results as $row) {
                $res[] = $row;
            }
        }

        return $res;
    }

    public function getValue($value, $condition)
    {
        $SQL = "SELECT $value FROM " . $this->carrier . " WHERE " . $condition;
        $val = Db::getInstance()->getValue($SQL);

        return $val;
    }

    public function delete($id_db)
    {
        $data = array("active" => 0);
        $this->update($data, "id_carrier=" . $id_db);
    }

    public function update($data, $where)
    {
        try {
            Db::getInstance()->update("blueexpress_carrier", $data, $where);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(__FILE__ . " $e");
        }
    }
}
