<?php
/**
 * Tenemos la classe BxRelayPointModel
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxAddressModule
 * @package  BxRelayPointModel
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

/**
 * BxRelayPointModel
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxAddressModule
 * @package  BxRelayPointModel
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

class BxRelayPointModel
{

    protected $relaypoint;
    protected $relay_temp;

    public function __construct()
    {
        $this->relaypoint = _DB_PREFIX_ . "blueexpress_relaypoint";
        $this->relay_temp = _DB_PREFIX_ . "blueexpressk_shipping_relaypoint";
    }

    public function add($relaypoint_detail)
    {
        try {
            Db::getInstance()->insert("blueexpress_relaypoint", $relaypoint_detail);
        } catch (Exception $e) {
            PrestaShopLogger::AddLog(__FILE__ . " $e");
        }
    }

    public function getRelaypointList($id_db_carrier)
    {
        $list = array();

        $SQL = "SELECT * FROM " . $this->relaypoint . " WHERE id_carrier=" . $id_db_carrier . " ORDER BY description";

        if ($result = Db::getInstance()->executeS($SQL)) {
            foreach ($result as $relay_point) {
                $list[] = $relay_point;
            }
        }

        return $list;
    }

    public function addShippingRelaypoint($cart_id, $relay)
    {
        try {
            Db::getInstance()->insert(
                "blueexpress_shipping_relaypoint",
                array(
                    "id_relaypoint" => $relay["office_id"],
                    "id_cart" => $cart_id,
                    "name" => $relay['office_name'],
                    "service" => $relay['office_service'],
                    "address" => $relay['office_address'],
                    "price" => $relay['office_price'],
                )
            );
        } catch (Exception $e) {
            PrestaShopLogger::AddLog(__FILE__ . " $e ");
        }
    }

    public function updateShippingRelaypoint($cart_id, $relay)
    {
        try {
            Db::getInstance()->update(
                "blueexpress_shipping_relaypoint",
                array(
                    "id_relaypoint" => $relay["office_id"],
                    "name" => $relay['office_name'],
                    "service" => $relay['office_service'],
                    "address" => $relay['office_address'],
                    "price" => $relay['office_price'],
                ),
                "id_cart=" . $cart_id
            );
        } catch (Exception $e) {
            PrestaShopLogger::AddLog(__FILE__ . " $e ");
        }
    }

    public function deleteShippingRelaypoint($cart_id)
    {
        Db::getInstance()->delete("blueexpress_shipping_relaypoint", "id_cart=" . $cart_id);
    }

    public function getShippingRelaypoint($id_cart)
    {
        $SQL = "SELECT * FROM " . $this->relay_temp . " WHERE id_cart=" . $id_cart;
        $relay_point = Db::getInstance()->getRow($SQL);

        return $relay_point;
    }

    public function getRelaypoint($condition)
    {
        $SQL = "SELECT * FROM " . $this->relaypoint . " WHERE " . $condition;

        $relay_point = Db::getInstance()->getRow($SQL);

        return $relay_point;
    }

    public function getRelaypoints($condition)
    {
        $SQL = "SELECT * FROM " . $this->relaypoint . " WHERE " . $condition;

        $relay_point = Db::getInstance()->executeS($SQL);

        return $relay_point;
    }

    public function getColumn($column, $condition)
    {
        $SQL = "SELECT $column FROM " . $this->relaypoint . " WHERE " . $condition;

        $relay_point = Db::getInstance()->getValue($SQL);

        return $relay_point;
    }
}
