<?php
/**
 * Tenemos la classe BlueExpress update Carrier Controller
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @Version   0.1.0
 * @category  BlueExpressupdateCarrierController
 * @package   BlueExpressupdateCarrierController
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

require_once dirname(__FILE__) . '/BxCarrierModel.php';
require_once dirname(__FILE__) . '/BxCarrier.php';

/**
 * BlueExpress update Carrier Controller
 * @author   Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @Version  0.1.0
 * @category BlueExpressupdateCarrierController
 * @package  BlueExpressupdateCarrierController
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

class BlueExpressupdateCarrierController
{
    private $carrier_model;

    private $services = array(
        "EX" => "Express",
        "PY" => "Prioritario",
        "MD" => "SameDay",
    );

    public function __construct($module = null)
    {
        $this->module = $module;
        $this->carrier_model = new BxCarrierModel();
    }
    public function run()
    {

        $maxID = Db::getInstance()->Tools::getValue("SELECT MAX(id_carrier) FROM `" . _DB_PREFIX_ . "carrier`");
        $option = array('id_local_carrier' => $maxID);
        $option2 = 'id_local_carrier = ' . Tools::getValue('id_carrier');
        Db::getInstance()->update("blueexpress_carrier", $option, $option2);
    }
}
