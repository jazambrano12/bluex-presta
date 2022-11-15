<?php
/**
 * Tenemos la classe BxOrderModel
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BxOrderModelModule
 * @package   BxOrderModel
 * @Version   0.1.0
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

/**
 * BxOrderModel
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxOrderModelModule
 * @package  BxOrderModel
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

class BxOrderModel
{

    protected $remote_order;

    public function __construct()
    {
        $this->remote_order = _DB_PREFIX_ . "blueexpress_order";
        $this->orderCarrier = _DB_PREFIX_ . "order_carrier";
    }

    public function add(
        $id_ps_order,
        $street = null,
        $number = null,
        $floor = null,
        $department = null,
        $id_shipment = null,
        $id_bx_order = null
    ) {
        try {
            Db::getInstance()->insert(
                "blueexpress_order",
                array( "id_ps_order" => $id_ps_order,
                "id_bx_order" => $id_bx_order,
                "id_shipment" => $id_shipment,
                "street" => $street,
                "number" => $number,
                "floor" => $floor,
                "department" => $department
                )
            );
        } catch (Exception $e) {
            PrestaShopLogger::addLog(__FILE__ . " $e");
        }
    }

    public function get($condition)
    {
        $SQL = "SELECT * FROM " . $this->remote_order . " WHERE " . $condition;
        $order = Db::getInstance()->getRow($SQL, true);

        return $order;
    }

    public function getAll($limit = null, $offset = null)
    {
        $SQL = "SELECT * FROM " . $this->remote_order . " 
        as bxo INNER JOIN " . _DB_PREFIX_ . "
        orders pso ON pso.id_order = bxo.id_ps_order INNER JOIN " . _DB_PREFIX_ . "
        blueexpress_carrier bxc ON pso.id_carrier = bxc.id_local_carrier 
        WHERE id_shipment = 0";

        if ($limit) {
            $SQL .= " LIMIT " . $limit;
        }

        if ($offset) {
            $SQL .= " OFFSET " . $offset;
        }

        $orders = Db::getInstance()->executeS($SQL);

        return $orders;
    }

    public function countAll()
    {
        $SQL = "SELECT count(*) FROM " . $this->remote_order . " 
        as bxo INNER JOIN " . _DB_PREFIX_ . "
        orders pso ON pso.id_order = bxo.id_ps_order INNER JOIN " . _DB_PREFIX_ . "
        enviopack_carrier bxc ON pso.id_carrier = bxc.id_local_carrier 
        WHERE id_shipment = 0";

        return Db::getInstance()->getValue($SQL, false);
    }

    public function delete($condition)
    {
        try {
            Db::getInstance()->delete("blueexpress_order", $condition);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(__FILE__ . " $e");
        }
    }

    public function update($data, $condition)
    {
        try {
            Db::getInstance()->update("blueexpress_order", $data, $condition);
        } catch (Exception $e) {
            PrestaShopLogger::addLog(__FILE__ . " $e");
        }
    }

    public function getOrderCarrier($value, $condition)
    {
        $SQL = "SELECT $value FROM " . $this->orderCarrier . " WHERE " . $condition;
        $val = Db::getInstance()->getValue($SQL);

        return $val;
    }
}
