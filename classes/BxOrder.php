<?php
/**
 * Tenemos la classe BxOrder
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BxOrderModule
 * @package   BxOrder
 * @Version   0.1.0
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

require_once dirname(__FILE__) . '/BxOrderModel.php';

/**
 * BxOrder
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxOrderModule
 * @package  BxOrder
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */
class BxOrder
{
    protected $order_detail;
    protected $bx_model;
    protected $bx_api;
    protected $bx_order_id;
    public $last_response;

    public function __construct($order_detail, $bx_api)
    {
        $this->order_detail = $order_detail;
        $this->bx_model = new BxOrderModel();
        $this->bx_api = $bx_api;
    }

    public function save()
    {

        $this->last_response = $this->bx_api->makeOrder($this->order_detail);

        if (key_exists('id', $this->last_response)) {
            $this->bx_order_id = $this->last_response['id'];
        } else {
            return -1;
        }
    }

    public function getBxOrderId()
    {
        return $this->bx_order_id;
    }
}
