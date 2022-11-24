<?php
/**
 * Tenemos la classe BxCarrierManager
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BxCarrierManagerModule
 * @Version   0.1.0
 */
require_once dirname(__FILE__) . '/BxCarrierModel.php';
require_once dirname(__FILE__) . '/BxCarrier.php';

/**
 * BxCarrierManager
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxCarrierManagerModule
 * @Version  0.1.0
 */
class BxCarrierManager
{
    private $carrier_model;

    private $services = ['EX' => 'Express', 'MD' => 'SameDay', 'PY' => 'Prioritario'];

    public function __construct($module = null)
    {
        $this->module = $module;
        $this->carrier_model = new BxCarrierModel();
    }

     /* Elimina el carreir */
    public function deleteEpCarrier($id_ps_carrier)
    {
        $bx_carrier = new BxCarrier($id_ps_carrier);

        // Si el carrier es utilizado para elegir sucursales
        // No lo elimino, ya que es comun a ambos metodos de
        // cotizacón: Correo y Provincia
        if ($bx_carrier->modality != 'S') {
            $this->deletePsCarrier($id_ps_carrier);
            $bx_carrier->delete();
        }
    }

    /* Elimina el carreir de prestashop */
    public function deletePsCarrier($id_local_carrier)
    {
        $ps_carrier = new Carrier($id_local_carrier);
        $ps_carrier->delete();
        $this->deleteLogo($id_local_carrier . '.jpg');
    }

    /* Elimina todos los carrier registrados */
    public function inactiveAllActiveCarriers()
    {
        $carrier_list = $this->carrier_model->getActiveCarriers();

        foreach ($carrier_list as $carrier) {
            $this->deleteEpCarrier($carrier['id_local_carrier']);
        }
    }

    /* Desinstala todos los carriers, sin expeciones */
    public function uninstall()
    {
        $carrier_list = $this->carrier_model->getAllCarriers();

        foreach ($carrier_list as $carrier) {
            $ep_carrier = new BxCarrier($carrier['id_local_carrier']);
            $ps_carrier = new Carrier($carrier['id_local_carrier']);

            $ep_carrier->delete();
            $ps_carrier->delete();

            $this->deleteLogo($carrier['id_local_carrier'] . '.jpg');
        }
    }

    /* Agrega un carrier en la estrcutura de prestashop */
    public function addPsCarrier($name, $module_name, $delay)
    {
        if (!$name || !$module_name) {
            return false;
        }

        $carrier = new Carrier();
        $carrier->name = $name;
        $carrier->id_tax_rules_group = 0;
        $carrier->active = 1;
        $carrier->deleted = 0;
        $carrier->url = 'https://www.bluex.cl/seguimiento/?n_seguimiento=@';

        $carrier->delay[1] = $delay;

        $carrier->shipping_handling = false;
        $carrier->range_behavior = 0;
        $carrier->is_module = true;
        $carrier->shipping_external = true;
        $carrier->external_module_name = $module_name;
        $carrier->need_range = true;

        if (!$carrier->add()) {
            return false;
        }

        $groups = Group::getGroups(true);
        foreach ($groups as $group) {
            Db::getInstance()->insert(
                'carrier_group',
                ['id_carrier' => (int) $carrier->id, 'id_group' => (int) $group['id_group']]
            );
        }

        $rangePrice = new RangePrice();
        $rangePrice->id_carrier = $carrier->id;
        $rangePrice->delimiter1 = 0;
        $rangePrice->delimiter2 = 1000;
        $rangePrice->add();

        $rangeWeight = new RangeWeight();
        $rangeWeight->id_carrier = $carrier->id;
        $rangeWeight->delimiter1 = 0;
        $rangeWeight->delimiter2 = 1000;
        $rangeWeight->add();

        $zones = Zone::getZones(true);
        foreach ($zones as $zone) {
            Db::getInstance()->insert(
                'carrier_zone',
                ['id_carrier' => (int) $carrier->id, 'id_zone' => (int) $zone['id_zone']]
            );
            Db::getInstance()->insert(
                'delivery',
                ['id_carrier' => (int) $carrier->id, 'id_range_price' => (int) $rangePrice->id, 'id_range_weight' => null, 'id_zone' => (int) $zone['id_zone'], 'price' => '0']
            );
            Db::getInstance()->insert(
                'delivery',
                ['id_carrier' => (int) $carrier->id, 'id_range_price' => null, 'id_range_weight' => (int) $rangeWeight->id, 'id_zone' => (int) $zone['id_zone'], 'price' => '0']
            );
        }

        return $carrier->id;
    }

    /* Crea los carriers que están en la API */
    public function installRemoteCarriers($carrier_list)
    {
        $generic_carriers = [];
        foreach ($carrier_list as $remote) {
            $services = $this->module->BxApi->getServices();
            foreach ($services as $service) {
                if (!array_key_exists($service->servicio, $this->services)) {
                    continue;
                }
                if (!$this->carrierBxExists($remote['id'], $service->servicio)) {
                    $this->addBxCarrier(
                        '',
                        $remote['id'],
                        0,
                        $service->servicio,
                        'D',
                        $remote['nombre']
                    );

                    $ps_carrier_id = $this->addPsCarrier(
                        'BLUE EXPRESS - ' . $service->servicio,
                        $this->module->name,
                        $service->servicio
                    );
                    $generic_carriers[] = [
                        'ps_id' => $ps_carrier_id,
                        'remote_id' => '',
                        'has_relay' => 1,
                        'service' => $service->servicio,
                        'modality' => 'D',
                        'name' => '',
                        'active' => 1,
                    ];
                }
            }
        }

        return $generic_carriers;
    }

    public function carrierBxExists($bx_carrier_id, $service)
    {
        $id = $this->carrier_model->getValue(
            'id_carrier',
            'id_remote_carrier = ' . $bx_carrier_id . ' and service_type = ' . $service
        );

        $res = false;
        if ($id > 0) {
            $res = true;
        }
        return $res;
    }

    public function carrierGenericExists($service, $modality)
    {
        $id = $this->carrier_model->getValue(
            'id_carrier',
            'id_remote_carrier = "" and service_type = ' . $service . ' and modality = ' . $modality
        );
        $res = false;

        if ($id > 0) {
            $res = true;
        }

        return $res;
    }

    /* Devuelve el ID del carrier generico */
    public function getGenericCarrierId($service, $modality)
    {
        $id = $this->carrier_model->getValue(
            'id_local_carrier',
            'id_remote_carrier = "" and service_type = ' . $service . ' and modality = ' . $modality
        );

        return $id;
    }

    public function addBxCarrier($idL, $idR, $hasR, $sType, $mD, $name, $active = 0)
    {
        $descript = trim($name . ' ' . $this->services[$sType]);
        $Bx_carrier = new BxCarrier();
        $Bx_carrier->id_local = $idL;
        $Bx_carrier->id_remote = $idR;
        $Bx_carrier->has_relaypoint = $hasR;
        $Bx_carrier->service_type = $sType;
        $Bx_carrier->modality = $mD;
        $Bx_carrier->description = $descript;
        $Bx_carrier->add($active);

        return $Bx_carrier;
    }

    public function copyLogo($src_path, $carrier_id, $extension)
    {
        copy($src_path, _PS_SHIP_IMG_DIR_ . '/' . (int) $carrier_id . $extension);
    }

    public function deleteLogo($file_name)
    {
        if (file_exists(_PS_SHIP_IMG_DIR_ . $file_name)) {
            unlink(_PS_SHIP_IMG_DIR_ . $file_name);
        }
    }

    public function getCarrierByRemoteid($remote_id)
    {
        $id_db = $this->carrier_model->getValue(
            'id_carrier',
            'id_remote_carrier = ' . $remote_id
        );

        return new BxCarrier(null, $id_db);
    }

    public function getRemoteCarriers()
    {
        $carrier_list = [];

        $result = $this->carrier_model->getAllCarriers();
        foreach ($result as $carrier) {
            if ($carrier['id_remote_carrier'] != '') {
                $carrier_list[] = $carrier;
            }
        }
        return $carrier_list;
    }

    public function getCarrierLocalId($id_carrier)
    {
        return $this->carrier_model->getValue(
            'id_local_carrier',
            'id_carrier = ' . $id_carrier
        );
    }

    public function updateCarrierLocal($old, $new)
    {
        $model = new BxCarrierModel();
        $model->update('id_local_carrier=$new', 'id_local_carrier=$old');
    }

    public function activeRemoteCarriers()
    {
        $carriers = $this->getRemoteCarriers();

        foreach ($carriers as $carrier) {
            $my_carrier = new BxCarrier(null, $carrier['id_carrier']);

            $ps_carrier_id = $this->addPsCarrier(
                $carrier['description'],
                $this->module->name,
                $this->services[$carrier['service_type']]
            );
            $my_carrier->id_local = $ps_carrier_id;
            $my_carrier->update();
            $lg = $carrier['id_remote_carrier'] . '.png';

            $this->copyLogo(
                'https://www.enviopack.com/imgs/' . $lg,
                $ps_carrier_id,
                '.jpg'
            );

            $my_carrier->activate();
        }
    }

    public function getGenericCarriers()
    {
        $carrier_list = [];
        $result = $this->carrier_model->getAllCarriers();
        foreach ($result as $carrier) {
            if (!$carrier['id_remote_carrier'] and !$carrier['has_relaypoint']) {
                $carrier_list[] = $carrier;
            }
        }

        return $carrier_list;
    }

    public function activeGenericCarriers()
    {
        $carriers = $this->getGenericCarriers();

        foreach ($carriers as $carrier) {
            $my_carrier = new BxCarrier(null, $carrier['id_carrier']);

            $ps_carrier_id = $this->addPsCarrier(
                'Envio a domicilio',
                $this->module->name,
                $this->services[$carrier['service_type']]
            );
            $this->copyLogo(
                dirname(__FILE__) . '/../truck.png',
                $ps_carrier_id, '.jpg'
            );
            $my_carrier->id_local = $ps_carrier_id;
            $my_carrier->update();
            $my_carrier->activate();
        }
    }
}
