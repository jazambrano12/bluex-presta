<?php
/**
 * Tenemos la classe BxShipment
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BxShipmentModule
 * @Version   0.1.0
 */
require_once dirname(__FILE__) . '/BxShipmentModel.php';
require_once dirname(__FILE__) . '/BxRelayManager.php';
require_once dirname(__FILE__) . '/BxPackage.php';
require_once dirname(__FILE__) . '/BxCarrier.php';
require_once dirname(__FILE__) . '/BxRelayPoint.php';

/**
 * BxShipment
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxShipmentModule
 * @Version  0.1.0
 */
class BxShipment
{
    public $bx_api;

    /* Datos generales del envio */
    public $pedido;
    public $direccion_envio;
    public $destinatario;
    public $observaciones;
    public $servicio;
    public $correo;
    public $confirmado;
    public $paquetes;
    public $modalidad;

    /* Envio a domicilio */
    public $calle;
    public $piso;
    public $numero;
    public $depto;
    public $referencia_domicilio;
    public $codigo_postal;
    public $provincia;
    public $localidad;

    /* Envio a sucursal */
    public $sucursal;

    public function __construct($BxApi)
    {
        $this->bx_api = $BxApi;
    }

    private function generalData()
    {
        $data = [
            'pedido' => $this->pedido,
            'direccion_envio' => $this->direccion_envio,
            'destinatario' => $this->destinatario,
            'observaciones' => $this->observaciones,
            'servicio' => $this->servicio,
            'correo' => $this->correo,
            'confirmado' => $this->confirmado,
            'paquetes' => $this->paquetes,
            'modalidad' => $this->modalidad,
        ];

        if (empty($data['pedido'])) {
            $data['pedido'] = '';
        }
        if (Tools::strlen($data['destinatario']) > 50) {
            $data['destinatario'] = '';
        }
        if (empty($data['modalidad'])) {
            $data['modalidad'] = '';
        }

        return $data;
    }

    private function homeData()
    {
        $generalData = $this->generalData();

        $data = [
            'calle' => $this->calle,
            'numero' => $this->numero,
            'piso' => $this->piso,
            'depto' => $this->depto,
            'referencia_domicilio' => $this->referencia_domicilio,
            'codigo_postal' => filter_var($this->codigo_postal, FILTER_SANITIZE_NUMBER_INT),
            'provincia' => $this->provincia,
            'localidad' => $this->localidad,
        ];

        if (empty($data['calle']) || Tools::strlen($data['calle']) > 30) {
            $data['calle'] = '';
        }
        if (empty($data['numero']) || Tools::strlen($data['numero']) > 5) {
            $data['numero'] = '';
        }
        if (Tools::strlen($data['piso']) > 6) {
            $data['piso'] = '';
        }
        if (Tools::strlen($data['depto']) > 4) {
            $data['depto'] = '';
        }
        if (Tools::strlen($data['referencia_domicilio']) > 30) {
            $data['referencia_domicilio'] = '';
        }
        if (!preg_match('/^\d{4}$/', $data['codigo_postal'], $res)) {
            $data['codigo_postal'] = '';
        }
        if (empty($data['provincia'])) {
            $data['provincia'] = '';
        }
        if (empty($data['localidad']) || Tools::strlen($data['localidad']) > 50) {
            $data['localidad'] = '';
        }

        return array_merge($generalData, $data);
    }

    private function relaypointData()
    {
        $generalData = $this->generalData();

        $data = ['sucursal' => (int) $this->sucursal];

        return array_merge($generalData, $data);
    }

    public function send()
    {
        if ($this->modalidad == 'S') {
            $data = $this->relaypointData();
        } else {
            $data = $this->homeData();
        }

        return $this->bx_api->create_shipment($data);
    }
}
