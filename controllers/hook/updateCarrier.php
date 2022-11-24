<?php
/**
 * Tenemos el Controller BlueExpress Envio pack Update Carrier
 * PHP versions 7.x
 *
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  EnviopackUpdateCarrierController
 * @Version   0.1.0
 */

/**
 * Envio pack Update Carrier
 *
 * @category EnviopackUpdateCarrierController
 * @author   BlueExpress
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @Version  0.1.0
 */
class EnviopackUpdateCarrierController
{
    public function __construct($module, $file, $path)
    {
        $this->file = $file;
        $this->module = $module;
        $this->context = Context::getContext();
        $this->_path = $path;
    }

    public function run($params)
    {
        $old_id_carrier = (int) $params['id_carrier'];
        $new_id_carrier = (int) $params['carrier']->id;

        $carrier_manager = new EpackCarrierManager();
        $carrier_manager->updateCarrierLocal($old_id_carrier, $new_id_carrier);
    }
}
