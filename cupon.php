<?php
/**
* 2007-2019 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2019 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Cupon extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'cupon';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'Aitor_Irene';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('cupon');
        $this->description = $this->l('Modulo que muestra el codigo de descuento general');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('CUPON_LIVE_MODE', false);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayFooter');
    }

    public function uninstall()
    {
        Configuration::deleteByName('CUPON_LIVE_MODE');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent() 
    {
        if (((bool)Tools::isSubmit('submitCuponDescuentoModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        return $this->renderForm();
    }

    function cargaDatos()
    {
        $form_values = $this->getConfigFormValues();
        $this->titulo = $form_values['CUPONDESCUENTO_ACCOUNT_TITULO'];
        $this->descripcion = $form_values['CUPONDESCUENTO_ACCOUNT_DESCRIPCION'];
        $this->idCupon = $form_values['CUPONDESCUENTO_ACCOUNT_CUPON'];

        $cupon = Db::getInstance()->ExecuteS("SELECT code FROM "._DB_PREFIX_."cart_rule WHERE id_cart_rule = ".$this->idCupon);
        foreach($cupon as $fila) {
            $this->cupon = $fila['code'];
        }
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitCuponDescuentoModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $names = Db::getInstance()->ExecuteS("SELECT * FROM "._DB_PREFIX_."cart_rule_lang where id_lang = ". $this->context->language->id);
        $nombresCupones = array();

        foreach($names as $name) {
            $nombresCupones[$name['id_cart_rule']] = array('id' => $name['id_cart_rule'], 'name' => $name['name']);
        }

        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'select',
                        'label' => $this->trans('Cupon', array(), 'Admin.Global'),
                        'name' => 'CUPONDESCUENTO_ACCOUNT_CUPON',
                        'col' => '4',
                        'options' => array(
                            'query' => $nombresCupones,
                            'id' => 'id',
                            'name' => 'name',
                        ),
                    ),        
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'CUPONDESCUENTO_ACCOUNT_TITULO',
                        'label' => $this->l('Titulo'),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'name' => 'CUPONDESCUENTO_ACCOUNT_DESCRIPCION',
                        'label' => $this->l('Descripcion'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'CUPONDESCUENTO_ACCOUNT_CUPON' => Tools::getValue('CUPONDESCUENTO_ACCOUNT_CUPON', Configuration::get('CUPONDESCUENTO_ACCOUNT_CUPON')),
            'CUPONDESCUENTO_ACCOUNT_CUPON' => Configuration::get('CUPONDESCUENTO_ACCOUNT_CUPON', 'contact@prestashop.com'),
            'CUPONDESCUENTO_ACCOUNT_TITULO' => Configuration::get('CUPONDESCUENTO_ACCOUNT_TITULO', 'contact@prestashop.com'),
            'CUPONDESCUENTO_ACCOUNT_DESCRIPCION' => Configuration::get('CUPONDESCUENTO_ACCOUNT_DESCRIPCION', 'contact@prestashop.com'),  
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayFooter()
    {
        $this->cargaDatos();
        
        $this->context->smarty->assign('titulo',$this->titulo);
        $this->context->smarty->assign('descripcion',$this->descripcion);
        $this->context->smarty->assign('cupon',$this->cupon);
        return $this->display(__FILE__,'cupon.tpl');
    }
}
