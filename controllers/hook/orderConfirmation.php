<?php
/**
 * Tenemos el Controller BlueExpress Order Confirmation
 * PHP versions 7.x
 *
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BlueexpressOrderConfirmationController
 * @package   BlueexpressOrderConfirmation
 * @Version   0.1.0
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

require_once dirname(__FILE__) . '/../../classes/BxRelayManager.php';
require_once dirname(__FILE__) . '/../../classes/BxCarrier.php';

/**
 * BlueExpress Order Confirmation
 *
 * @category BlueexpressOrderConfirmationController
 * @package  BlueexpressOrderConfirmation
 * @author   BlueExpress
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */
class BlueexpressOrderConfirmationController
{
    private $file;
    private $module;
    private $context;
    private $_path;

    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    public function run($params)
    {
        $relay_manager = new BxRelayManager();

        $carrier = new BxCarrier($params['order']->id_carrier);
        $relay_manager->deleteShippingRelaypoint($params['order']->id_cart);

    }
}
