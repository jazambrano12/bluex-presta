<?php
/**
 * Tenemos la classe con las conexiones a las Api
 * PHP versions 7.x
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category  BxApiModule
 * @package   BxApi
 * @Version   0.1.0
 * @link      https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

require_once dirname(__FILE__) . '/BxPackage.php';

/**
 * BxApi
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxApiModule
 * @package  BxApi
 * @Version  0.1.0
 * @link     https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 */

class BxApi
{
    protected static $instance;

    protected $idDireccionEnvio;
    protected $url = "https://integraciones.bluex.cl/api/ps/";
    protected $userCode;
    protected $apiKey;
    protected $secretKey;
    protected $token;
    protected $bxKey;

    public function __construct()
    {
    }

    /* Singleton */
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BxApi();
        }

        return self::$instance;
    }

    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function setSecretKey($secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function setUserCode($userCode)
    {
        $this->userCode = $userCode;
    }

    public function setBxKey($bxKey)
    {
        $this->bxKey = $bxKey;
    }

    public function getCarriers()
    {
        $carriers = array();
        $getCarriers = array();
        $carriers["id"] = "BX";
        $carriers["nombre"] = "Blue Express";
        $getCarriers[] = $carriers;

        return $getCarriers;
    }

    public function getServices()
    {
        $response = json_encode([
            [
                "servicio" => "EX",
                "nombre" => "Express",
                "modalidad" => "D"],
            [
                "servicio" => "PY",
                "nombre" => "Prioritario",
                "modalidad" => "D"],
            [
                "servicio" => "MD",
                "nombre" => "Sameday",
                "modalidad" => "D"
            ]
        ]);
        return json_decode($response);
    }

    /* Devuelve el precio que paga el cliente por un envio a provincia */
    public function getCotizacionADomicilio($provincia, $peso, $items, $service = 'N')
    {

        if (!$provincia || !$peso) {
            PrestaShopLogger::AddLog("Bluex: No es posible cotizar un envio sin comuna, dimensiones o peso", 2);
            return false;
        }

        /**
         * OBTENEMOS EL CODIGO DE LA COMUNA
         * SELECCIONADA PARA OBTENER EL COSTO DEL DESPACHO
         */
        $bxGeo = json_decode($this->get('bx-geo/states'), true);
        $comuna = $this->eliminarAcentos($provincia);

        $dadosGeo = [];
        foreach ($bxGeo['data'][0]['states'] as $bxData) {
            foreach ($bxData['ciudades'] as $bxDataC) {
                if (Tools::strtolower($bxDataC['name']) == Tools::strtolower($comuna)) {
                    $dadosGeo['regionCode'] = $bxData['code'];
                    $dadosGeo['cidadeName'] = $bxDataC['name'];
                    $dadosGeo['cidadeCode'] = $bxDataC['code'];
                    $dadosGeo['districtCode'] = $bxDataC['defaultDistrict'];
                }
            }
            if (array_key_exists('cidadeName', $dadosGeo) && $dadosGeo['cidadeName'] == '') {
                foreach ($bxData['ciudades'] as $bxDataC) {
                    foreach ($bxDataC['districts'] as $bxDataD) {
                        if (Tools::strtolower($bxDataD['name']) == Tools::strtolower($comuna)) {
                            $dadosGeo['regionCode'] = $bxData['code'];
                            $dadosGeo['cidadeName'] = $bxDataC['name'];
                            $dadosGeo['cidadeCode'] = $bxDataC['code'];
                            $dadosGeo['districtCode'] = $bxDataC['defaultDistrict'];
                        }
                    }
                }
            }
        }

        /**
         * CONSULTAMOS EL PRECIO SEGUN LA COMUNA SELECCIONADA
         */
        if (!empty($dadosGeo)) {
            $request = [
                "from" => ["country" => "CL", "district" => "RRI"],
                "to" => [
                            "country" => "CL",
                            "state" => $dadosGeo['regionCode'],
                            "district" => $dadosGeo['districtCode']
                        ],
                "serviceType" => $service,
                "datosProducto" => [
                    "producto" => "P",
                    "familiaProducto" => "PAQU",
                    "bultos" => $items,
                ],
            ];

            $bxPrincig = json_decode($this->post('bx-pricing', $request), true);
            if (empty($bxPrincig)) {
                return false;
            }

            if (isset($bxPrincig['data']) && isset($bxPrincig['data']['flete'])) {
                return $bxPrincig['data']['flete'];
            }
        }

        return false;
    }

    public function makeOrder($order_detail)
    {
        $option = array('tracking_number' => "");
        $idOrder = 'id_order =' . $order_detail['order_id'];
        Db::getInstance()->update("order_carrier", $option, $idOrder);
    }

    protected function get($method, $request = array())
    {

        if ($method == 'bx-geo/states') {
            $url = "https://bx-tracking.bluex.cl/" . $method;
            $header = array(
                'content-type: application/json',
                'bx-client_account: ' . $this->apiKey,
                'bx-token: ' . $this->secretKey,
                'bx-usercode: ' . $this->userCode,
            );
        } else {
            $url = rtrim($this->url, '/') . '/' . ltrim($method, '/') . '?' . http_build_query($request);
            $header = array('bx-user-code: ' . $this->userCode);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    protected function sanitize($text)
    {
        return trim($text);
    }

    protected function post($method, $request = array())
    {
        if ($method == 'bx-pricing') {
            $url = "https://qaapigw.bluex.cl/api/legacy/pricing/v1/";
            $datos = json_encode($request);
            $header = array(
                'content-type: application/json',
                'apikey: ' . $this->bxKey,
                'bx-token: ' . $this->secretKey,
            );
        } else {
            $sql =  http_build_query($request);
            $url = rtrim($this->url, '/') . '/' . ltrim($method, '/') . '?' .$sql;
            $header = array(
                'Content-Type: application/json',
                'bx-user-code: ' . $this->userCode
            );
            $datos = $request;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $datos);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    public static function cleanPostcode($postcode)
    {
        return preg_replace("/[^0-9]/", "", $postcode);
    }

    public function getTiposDePaquete()
    {
        return json_decode($this->get('tipos-de-paquetes'), true);
    }
    public function eliminarAcentos($cadena)
    {
        
        //Reemplazamos la A y a
        $cadena = str_replace(
            array(
                'Á', 'À', 'Â', 'Ä', 'á', 'à', 'ä', 'â', 'ª',
                'É', 'È', 'Ê', 'Ë', 'é', 'è', 'ë', 'ê',
                 'Í', 'Ì', 'Ï', 'Î', 'í', 'ì', 'ï', 'î',
                 'Ó', 'Ò', 'Ö', 'Ô', 'ó', 'ò', 'ö', 'ô',
                 'Ú', 'Ù', 'Û', 'Ü', 'ú', 'ù', 'ü', 'û',
                 'Ñ', 'ñ', 'Ç', 'ç'
                ),
            array(
                'A', 'A', 'A', 'A', 'a', 'a', 'a', 'a', 'a',
                'E', 'E', 'E', 'E', 'e', 'e', 'e', 'e',
                'I', 'I', 'I', 'I', 'i', 'i', 'i', 'i',
                'O', 'O', 'O', 'O', 'o', 'o', 'o', 'o',
                'U', 'U', 'U', 'U', 'u', 'u', 'u', 'u',
                'N', 'n', 'C', 'c'
            ),
            $cadena
        );
        return $cadena;
    }

    public function postWebHook($request)
    {
        $url = "https://apigw.bluex.cl/api/integrations/prestashop/v1";
        $header = array(
            'Content-Type: application/json',
            'apikey: ' . $this->bxKey,
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }
}
