<?php
/**
 * Plugin Name: Blue Express Prestashop
 * Description: Plugin oficial de Blue Express
 * Author: Blue Express
 * link: https://github.com/Blue-Express/bx-plugin-ecom-prestashop-shipping
 * Version: 1.0.0
 */ 
if (!defined('_PS_VERSION_')) {
    exit;
}

//Define default api keys
define('BLUEX_DEFAULT_CLIENT', '');
define('BLUEX_DEFAULT_TOKEN', '');
define('BLUEX_DEFAULT_USERCODE', '');
define('BLUEX_DEFAULT_APIKEY', ''); 

class Bluex extends CarrierModule
{
    public $id_carrier;
    private $_html = '';
    private $_postErrors = array();
    private $_moduleName = 'bluex';
    protected $context;
    protected $BXhooks = array(
        'actionCarrierUpdate',
        'displayAfterCarrier',
        'orderConfirmation',
        'actionOrderStatusPostUpdate',
        'actionValidateOrder',
        'actionPaymentConfirmation',
    );

    public function __construct() {
        $this->name = 'bluex';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Blue Express';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->module_key = '2243c80ad49112b0359a82f916607550';

        include_once _PS_MODULE_DIR_ . $this->name . '/classes/BxApi.php';
        include_once _PS_MODULE_DIR_ . $this->name . '/classes/BxConfig.php';
        include_once _PS_MODULE_DIR_ . $this->name . '/classes/BxCarrierManager.php';
        include_once _PS_MODULE_DIR_ . $this->name . '/classes/BxRelayManager.php';

        $this->BxConfig = new BxConfig($this);
        $this->BxApi = BxApi::getInstance();

        $config = Configuration::getMultiple(array('BX_CLIENT', 'BX_TOKEN', 'USERCODE', 'BX_APIKEY'));

        if (!empty($config['BX_CLIENT'])) {
            $this->bx_client = $config['BX_CLIENT'];
        }
        if (!empty($config['BX_TOKEN'])) {
            $this->bx_token = $config['BX_TOKEN'];
        }
        if (!empty($config['BX_USERCODE'])) {
            $this->bx_usercode = $config['BX_USERCODE'];
        }

        if (!empty($config['BX_APIKEY'])) {
            $this->bx_apikey = $config['BX_APIKEY'];
        }

        parent::__construct();

        $this->displayName = $this->l('Blue Express');
        $this->description = $this->l('Blue Express - Lo traemos para ti.');
    }

    public function install() {
        if (parent::install()) {
            foreach ($this->BXhooks as $hook) {
                if (!$this->registerHook($hook)) {
                    return false;
                }
            }
            $sql_file = dirname(__FILE__) . '/install/install.sql';
            if (!$this->loadSQLFile($sql_file)) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function loadSQLFile($sql_file) {
        $sql_content = Tools::file_get_contents($sql_file);

        $sql_content = str_replace('PREFIX_', _DB_PREFIX_, $sql_content);
        $sql_requests = preg_split("/;\s*[\r\n]+/", $sql_content);

        $result = true;
        foreach ($sql_requests as $request) {

            if (!empty($request)) {
                $result &= Db::getInstance()->execute(trim($request));
            }
        }

        return $result;
    }

    public function uninstall() {
        if (parent::uninstall()) {
            $sql_file = dirname(__FILE__) . '/install/uninstall.sql';
            if (!$this->loadSQLFile($sql_file)) {
                return false;
            }

            if (!Configuration::deleteByName('BX_CLIENT')
                || !Configuration::deleteByName('BX_TOKEN')
                || !Configuration::deleteByName('BX_USERCODE')
                || !Configuration::deleteByName('BX_APIKEY')) {
                return false;
            }

            foreach ($this->BXhooks as $hook) {
                if (!$this->unregisterHook($hook)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    public function getContent() {
        $output = $this->BxConfig->processConfiguration();
        $output .= $this->BxConfig->displayConfigurationForm();
        $this->context->controller->addJS(($this->_path) . 'views/js/bluex-config.js');
        return $output;
    }

    public function getHookController($hook_name) {
        require_once dirname(__FILE__) . '/controllers/hook/' . $hook_name . '.php';
        $controller_name = 'BlueExpress' . $hook_name . 'Controller';
        $controller = new $controller_name($this, __FILE__, $this->_path);
        return $controller;
    }

    public function getOrderShippingCostExternal($params) {
        return $this->getOrderShippingCost($params, 0);
    }

    public function getOrderShippingCost($params, $shipping_cost) {

        $controller = $this->getHookController('getOrderShippingCost');
        return $controller->run($params, $shipping_cost);
    }

    public function hookUpdateCarrier($params) {
        $controller = $this->getHookController('updateCarrier');
        return $controller->run($params);
    }

    public function hookActionOrderStatusPostUpdate($params) {
        $controller = $this->getHookController('actionOrderStatusPostUpdate');
        return $controller->run($params);
    }

}
