<?php
/*
Plugin Name: Účto+ Invoices
Plugin URI: https://uctoplus.sk
Description: Plugin Účto+ invoices is designed for Wordpress (WooCommerce) online stores who have purchased invoicing software Účto+. Plugin automates the connecting and sending data from e-shop to invoicing system. You can create invoices directly from your e-shop orders.
Author: uctoplus.sk, a.s.
Author URI: https://www.uctoplus.sk/
Version: 1.2.4
*/
defined('ABSPATH') or die('No script kiddies please!');

require_once( 'classes/class.uctoplusInvoicesAdmin.php' );
require_once( 'classes/class.uctoplusInvoicesProcess.php' );

class UctoplusInvoices
{
    public $settings = [];
    public $licenseValidator;
    public $updateChecker;

    public function __construct()
    {
        load_plugin_textdomain('uctoplus-invoices', false, dirname(plugin_basename(__FILE__)) . '/lang');
        $this->settings['plugin-slug'] = 'uctoplus-invoices';
        $this->settings['plugin-path'] = plugin_dir_path(__FILE__);
        $this->settings['plugin-url'] = plugin_dir_url(__FILE__);
        $this->settings['plugin-name'] = 'Účto+ Invoices';
        $this->settings['plugin-license-version'] = '1.x.x';
        $this->initialize();
    }

    private function initialize()
    {
        new UctoplusInvoicesAdmin($this);
        new UctoplusInvoicesProcess($this);
    }

    public function getPluginSlug()
    {
        return $this->settings['plugin-slug'];
    }

    public function getPluginPath()
    {
        return $this->settings['plugin-path'];
    }

    public function getPluginUrl()
    {
        return $this->settings['plugin-url'];
    }

    public function getPluginName()
    {
        return $this->settings['plugin-name'];
    }

    public function getPluginLicenseVersion()
    {
        return $this->settings['plugin-license-version'];
    }

}

new UctoplusInvoices();



