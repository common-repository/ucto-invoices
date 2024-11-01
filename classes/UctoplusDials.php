<?php

/**
 * Class UctoplusDials
 *
 * @author MimoGraphix <mimographix@gmail.com>
 * @copyright Epic Fail | Studio
 */
class UctoplusDials
    extends BaseUctoplusAPI
{
    /**
     * @endpoint /dial/invoice-type/{invoiceType}/counters
     *
     * @param $type
     */
    public function getCountersByType($invoiceType)
    {
        return $this->request('GET', '/dial/invoice-type/'.$invoiceType.'/counters');
    }

    /**
     * @endpoint /dial/invoice/templates
     *
     * @param $type
     */
    public function getTemplates()
    {
        return $this->request('GET', '/dial/invoice/templates');
    }

    /**
     * @endpoint /dial/global/countries
     *
     * @param $type
     */
    public function getCountries()
    {
        return $this->request('GET', '/dial/global/countries');
    }
}