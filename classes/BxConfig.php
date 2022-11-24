<?php
/**
 * BxConfig
 * @author   BlueExpress
 * @copyright 2022 Blue Express
 * @license  https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 * @category BxConfigModule
 * @Version  0.1.0
 */
class BxConfig
{
    protected $module;
    protected $BxCarrierManager;
    protected $BxRelayManager;

    public const ESTIMATION_SUM_DIMS = 1;
    public const ESTIMATION_MAX_DIMS = 2;
    public const ESTIMATION_DEFAULT_PACKET = 3;

    public function __construct($module)
    {
        $this->module = $module;
        $this->BxCarrierManager = new BxCarrierManager($module);
        $this->BxRelayManager = new BxRelayManager();
    }

    public function processConfiguration()
    {
        $output = null;

        if (Tools::isSubmit('bxAuth')) {
            $output .= $this->processAuth();
        } elseif (Tools::isSubmit('bxGeneral')) {
            $output .= $this->processConfig();
        } elseif (Tools::isSubmit('bxFail')) {
            $output .= $this->processFail();
        }

        return $output;
    }

    public function displayConfigurationForm()
    {
        $fields_form = [];
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');
        $fields_form[0] = $this->authForm();

        if ((Configuration::get('BX_CLIENT') && Configuration::get('BX_TOKEN')
            && Configuration::get('BX_USERCODE') && Configuration::get('BX_APIKEY'))
            || (defined(BLUEX_DEFAULT_CLIENT) && defined(BLUEX_DEFAULT_TOKEN)
            && defined(BLUEX_DEFAULT_USERCODE) && defined(BLUEX_DEFAULT_APIKEY)
            && (!empty(BLUEX_DEFAULT_CLIENT) && !empty(BLUEX_DEFAULT_TOKEN)
            && !empty(BLUEX_DEFAULT_USERCODE) && !empty(BLUEX_DEFAULT_APIKEY)))
        ) {
            $fields_form[1] = $this->generalConfig();
        }

        $helper = new HelperForm();

        $helper->module = $this->module;
        $helper->name_controller = $this->module->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->module->name;

        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        $helper->title = $this->module->displayName;
        $helper->show_toolbar = false;
        $helper->toolbar_scroll = false;
        $helper->submit_action = 'submit' . $this->module->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->module->l('Save'),
                'href' => AdminController::$currentIndex .
                '&configure=' . $this->module->name .
                '&save' . $this->module->name .
                '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
        ];

        // Load current value
        $bPrice = Configuration::get('BX_BRANCH_PRICE');
        $pMethod = Configuration::get('BX_PACKET_ESTIMATION_METHOD');
        $pEDefault = Configuration::get('BX_PACKET_ESTIMATION_DEFAULT');
        $helper->fields_value['BX_CLIENT'] = Configuration::get('BX_CLIENT') ?
        Configuration::get('BX_CLIENT') : BLUEX_DEFAULT_CLIENT;
        $helper->fields_value['BX_TOKEN'] = Configuration::get('BX_TOKEN') ?
        Configuration::get('BX_TOKEN') : BLUEX_DEFAULT_TOKEN;
        $helper->fields_value['BX_USERCODE'] = Configuration::get('BX_USERCODE') ?
        Configuration::get('BX_USERCODE') : BLUEX_DEFAULT_USERCODE;
        $helper->fields_value['BX_APIKEY'] = Configuration::get('BX_APIKEY') ?
        Configuration::get('BX_APIKEY') : BLUEX_DEFAULT_APIKEY;
        $helper->fields_value['BX_HOOK_URL'] = Configuration::get('BX_HOOK_URL');
        $helper->fields_value['BX_BRANCH_PRICE'] = $bPrice;
        $helper->fields_value['BX_DEF_STATE'] = Configuration::get('BX_DEF_STATE');
        $helper->fields_value['BX_PAID_STATE'] = Configuration::get('BX_PAID_STATE');
        $helper->fields_value['BX_DEF_WEIGHT'] = Configuration::get('BX_DEF_WEIGHT');
        $helper->fields_value['BX_DEF_DEPTH'] = Configuration::get('BX_DEF_DEPTH');
        $helper->fields_value['BX_DEF_WIDTH'] = Configuration::get('BX_DEF_WIDTH');
        $helper->fields_value['BX_DEF_HEIGHT'] = Configuration::get('BX_DEF_HEIGHT');
        $helper->fields_value['BX_DEF_PRICE'] = Configuration::get('BX_DEF_PRICE');
        $helper->fields_value['BX_PACKET_ESTIMATION_METHOD'] = $pMethod;
        $helper->fields_value['BX_PACKET_ESTIMATION_DEFAULT'] = $pEDefault;

        return $helper->generateForm($fields_form);
    }

    private function generalConfig()
    {
        $default_lang = (int) Configuration::get('PS_LANG_DEFAULT');

        $objState = new OrderStateCore();
        $state_list = $objState->getOrderStates($default_lang);
        $status_list = [];

        foreach ($state_list as $state) {
            $status_list[] = [
                'id_option' => $state['id_order_state'],
                'name' => $state['name'],
            ];
        }

        $packet_estimation_options = [
            ['id_option' => self::ESTIMATION_SUM_DIMS, 'name' => 'Estimar dimensiones en base a la sumatoria de las dimensiones de los productos'],
            ['id_option' => self::ESTIMATION_MAX_DIMS, 'name' => 'Estimar en base a la dimension mas alta de cada producto'],
        ];

        $config_options = [
            'BX_DEF_STATE' => $status_list,
            'BX_PAID_STATE' => $status_list,
            'BX_PACKET_ESTIMATION_METHOD' => $packet_estimation_options,
        ];
        // Guardo los valores default si no los tiene guardados
        $this->saveDefaultConfigValues($config_options);
        $desc = 'Estado que será asignado al pedido una vez que haya sido procesado por Blue Express';

        $form = [
            'form' => [
                'legend' => [
                    'title' => $this->module->l('Configuración general'),
                    'icon' => 'icon-envelope',
                ],
                'description' => $this->module->l(''),
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Estado'),
                        'name' => 'BX_DEF_STATE',
                        'desc' => $desc,
                        'options' => [
                            'query' => $status_list,
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                        'required' => true,
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Estado pagado'),
                        'name' => 'BX_PAID_STATE',
                        'desc' => 'Estado que se utiliza para indicar que un pedido
                         se encuentra pago y listo para ser enviado',
                        'options' => [
                            'query' => $status_list,
                            'id' => 'id_option',
                            'name' => 'name',
                        ],
                        'required' => true,
                    ],
                ],
                'submit' => [
                    'name' => 'blueexpressGeneral',
                    'title' => $this->module->l('Save'),
                    'class' => 'btn btn-default pull-right',
                ],
            ],
        ];

        return $form;
    }

    private function getURLSite()
    {
        $url = Tools::htmlentitiesutf8(
            ((bool) Configuration::get('PS_SSL_ENABLED') ? 'https://' : 'http://')
            . $_SERVER['HTTP_HOST'] . __PS_BASE_URI__
        );
        return $url;
    }

    /**
     * Este metodo guarda los valores default la primera
     * vez cuando no estan inicializados
     */
    private function saveDefaultConfigValues($config_options)
    {
        $protocol_link = (Configuration::get('PS_SSL_ENABLED') || Tools::usingSecureMode()) ? 'https://' : 'http://';
        $useSSL = (Configuration::get('PS_SSL_ENABLED') || Tools::usingSecureMode()) ? true : false;
        $protocol_content = ($useSSL) ? 'https://' : 'http://';
        $link = new Link($protocol_link, $protocol_content);

        if (!Configuration::get('BX_HOOK_URL')) {
            $ipn_url = $link->getModuleLink(
                'blueexpress',
                'notification',
                [],
                null,
                null,
                Configuration::get('PS_SHOP_DEFAULT')
            );
            Configuration::updateValue('BX_HOOK_URL', $ipn_url);
        }

        if (!Configuration::get('BX_BRANCH_PRICE')) {
            Configuration::updateValue('BX_BRANCH_PRICE', 120);
        }
        if (!Configuration::get('BX_DEF_STATE')) {
            foreach ($config_options['BX_DEF_STATE'] as $values) {
                if ($values['id_option'] == 3) {
                    // Preparación en proceso
                    Configuration::updateValue('BX_DEF_STATE', 3);
                    break;
                }
            }
        }
        if (!Configuration::get('BX_PAID_STATE')) {
            foreach ($config_options['BX_PAID_STATE'] as $values) {
                if ($values['id_option'] == 2) {
                    // Pago aceptado
                    Configuration::updateValue('BX_PAID_STATE', 2);
                    break;
                }
            }
        }

        if (!Configuration::get('BX_PACKET_ESTIMATION_METHOD')) {
            Configuration::updateValue(
                'BX_PACKET_ESTIMATION_METHOD',
                self::ESTIMATION_SUM_DIMS
            );
        }
    }

    private function processConfig()
    {
        $output = null;

        $default_state = (string) Tools::getValue('BX_DEF_STATE');
        $paid_state = (string) Tools::getValue('BX_PAID_STATE');
        $packet_estimation = (string) Tools::getValue('BX_PACKET_ESTIMATION_METHOD');
        $packet_default = (string) Tools::getValue('BX_PACKET_ESTIMATION_DEFAULT');
        $branch_price = (string) Tools::getValue('BX_BRANCH_PRICE');

        if (!$default_state) {
            $output .= $this->module->displayError(
                $this->module->l(
                    'Debe elegir un estado para los
                    pedidos procesados por Blue Express'
                )
            );
        } elseif (!$paid_state) {
            $output .= $this->module->displayError(
                $this->module->l(
                    'Debe elegir un estado para indicar que
                    el pedido está pagado y listo para ser enviado'
                )
            );
        } elseif ($default_state == $paid_state) {
            $output .= $this->module->displayError(
                $this->module->l(
                    'El estado pagado no debe ser igual al
                    estado de pedidos procesados por Blue Express'
                )
            );
        } elseif ($packet_estimation == self::ESTIMATION_DEFAULT_PACKET && !$packet_default) {
            $output .= $this->module->displayError(
                $this->module->l(
                    'Debe elegir el tamaño del paquete para estimar
                    con la modalidad `Paquete default`'
                )
            );
        } elseif (!preg_match('/^\d*$/', $branch_price, $res)) {
            $output .= $this->module->displayError(
                $this->module->l(
                    'Debe ingresar solo caracteres
                    numéricos para el valor de envío a sucursal'
                )
            );
        } else {
            Configuration::updateValue('BX_DEF_STATE', $default_state);
            Configuration::updateValue('BX_PAID_STATE', $paid_state);
            Configuration::updateValue(
                'BX_PACKET_ESTIMATION_METHOD',
                $packet_estimation
            );
            Configuration::updateValue(
                'BX_PACKET_ESTIMATION_DEFAULT',
                $packet_default
            );
            Configuration::updateValue('BX_BRANCH_PRICE', $branch_price);
        }

        return $output;
    }

    private function authForm()
    {
        $form = [
            'form' => [
                    'legend' => [
                        'title' => $this->module->l('Datos de acceso'),
                        'icon' => 'icon-lock',
                    ],
                    'description' => $this->module->l(
                        'Para configurar tus preferencias de
                        envío ingresá a tu cuenta de Blue Express '
                    ),
                    'input' => [
                        [
                            'type' => 'text',
                            'label' => $this->module->l('BX CLIENT_ACCOUNT'),
                            'name' => 'BX_CLIENT',
                            'size' => 40,
                            'required' => true,
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->module->l('BX TOKEN'),
                            'name' => 'BX_TOKEN',
                            'size' => 40,
                            'required' => true,
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->module->l('BX USER CODE'),
                            'name' => 'BX_USERCODE',
                            'size' => 40,
                            'required' => true,
                        ],
                        [
                            'type' => 'text',
                            'label' => $this->module->l('BX API KEY'),
                            'name' => 'BX_APIKEY',
                            'size' => 40,
                            'required' => true,
                        ],
                    ],
                    'submit' => [
                        'name' => 'bxAuth',
                        'title' => $this->module->l('Save'),
                        'class' => 'btn btn-default pull-right',
                    ],
            ],
        ];

        return $form;
    }

    private function processAuth()
    {
        $output = null;

        $api_key = (string) Tools::getValue('BX_CLIENT');
        $secret_key = (string) Tools::getValue('BX_TOKEN');
        $user_code = (string) Tools::getValue('BX_USERCODE');
        $bxkey = (string) Tools::getValue('BX_APIKEY');
        if (!$api_key || !$secret_key || !$user_code || !$bxkey) {
            $output .= $this->module->displayError(
                $this->module->l(
                    'Los datos proporcionados no son válidos'
                )
            );
        } else {
            Configuration::updateValue('BX_CLIENT', $api_key);
            Configuration::updateValue('BX_TOKEN', $secret_key);
            Configuration::updateValue('BX_USERCODE', $user_code);
            Configuration::updateValue('BX_APIKEY', $bxkey);

            $this->module->BxApi->setApiKey($api_key);
            $this->module->BxApi->setSecretKey($secret_key);
            $this->module->BxApi->setUserCode($user_code);
            $this->module->BxApi->setBxKey($bxkey);

            $output .= $this->module->displayConfirmation(
                $this->module->l('Credenciales guardadas correctamente')
            );

            $dbinstance = Db::getInstance();
            $tablaBd = 'SELECT `id_local_carrier` FROM `' . _DB_PREFIX_ .
            'blueexpress_carrier` WHERE `modality` = `D`';
            $results = $dbinstance->ExecuteS($tablaBd);
            foreach ($results as $row) {
                $id_local_carrier = $row['id_local_carrier'];
                $dbinstance->delete('carrier', 'id_carrier = ' . $id_local_carrier, 1);
            }

            $carriers_list = $this->module->BxApi->getCarriers();

            $generic_carriers = $this->BxCarrierManager->installRemoteCarriers(
                $carriers_list
            );

            foreach ($generic_carriers as $generic_carrier) {
                if (!$this->BxCarrierManager->getGenericCarrierId(
                    $generic_carrier['service'],
                    $generic_carrier['modality']
                )
                ) {
                    $this->BxCarrierManager->addBxCarrier(
                        $generic_carrier['ps_id'],
                        $generic_carrier['remote_id'],
                        $generic_carrier['has_relay'],
                        $generic_carrier['service'],
                        $generic_carrier['modality'],
                        $generic_carrier['name'],
                        $generic_carrier['active']
                    );
                }
            }

            $this->BxCarrierManager->activeGenericCarriers();
        }

        return $output;
    }

    private function processFail()
    {
        $output = null;

        $default_weight = (string) Tools::getValue('BX_DEF_WEIGHT');
        $default_depth = (string) Tools::getValue('BX_DEF_DEPTH');
        $default_width = (string) Tools::getValue('BX_DEF_WIDTH');
        $default_height = (string) Tools::getValue('BX_DEF_HEIGHT');
        $default_price = (string) Tools::getValue('BX_DEF_PRICE');

        if (!$default_price || !$default_weight || !$default_depth || !$default_width || !$default_height) {
            $output .= $this->module->displayError($this->module->l('Los datos proporcionados no son válidos'));
        } else {
            Configuration::updateValue('BX_DEF_WEIGHT', $default_weight);
            Configuration::updateValue('BX_DEF_DEPTH', $default_depth);
            Configuration::updateValue('BX_DEF_WIDTH', $default_width);
            Configuration::updateValue('BX_DEF_HEIGHT', $default_height);
            Configuration::updateValue('BX_DEF_PRICE', $default_price);

            $output .= $this->module->displayConfirmation(
                $this->module->l('La configuración fue guardada satisfactoriamente')
            );
        }
        return $output;
    }

    public function installTab($parent, $class_name, $name)
    {
        if (!Configuration::get('BX_TABINSTALLED')) {
            $tab = new Tab();
            $tab->id_parent = (int) Tab::getIdFromClassName($parent);
            $tab->name = [];
            foreach (Language::getLanguages(true) as $lang) {
                $tab->name[$lang['id_lang']] = $name;
            }

            $tab->class_name = $class_name;
            $tab->module = 'blueexpress';
            $tab->active = 1;

            $all_tabs = $tab->getTabs(false, $tab->id_parent);
            foreach ($all_tabs as $value) {
                if ($value['class_name'] === $class_name) {
                    Configuration::updateValue('BX_TABINSTALLED', true);
                    break;
                }
            }

            $dbinstance = Db::getInstance();
            $dbinstance->delete('authorization_role', '`slug` = `ROLE_MOD_TAB_ADMINBX_CREATE`', 1);
            $dbinstance->delete('authorization_role', '`slug` = `ROLE_MOD_TAB_ADMINBX_DELETE`', 1);
            $dbinstance->delete('authorization_role', '`slug` = `ROLE_MOD_TAB_ADMINBX_READ`', 1);
            $dbinstance->delete('authorization_role', '`slug` = `ROLE_MOD_TAB_ADMINBX_UPDATE`', 1);

            if (!Configuration::get('BX_TABINSTALLED')) {
                if (!$tab->save()) {
                    return false;
                } else {
                    Configuration::updateValue('BX_TABINSTALLED', true);
                }
            }
        }
        return true;
    }
}
