<?php

/**
 * Currency rate import model (From fixer.io)
 *
 * @category   C3
 * @package    C3_FixerIOCurrencyImport
 * @author      C3 Development Team <development@c3media.co.uk>
 */
class C3_FixerIOCurrencyImport_Model_Currency_Import_FixerIO extends Mage_Directory_Model_Currency_Import_Abstract
{
    protected $_url = 'https://api.fixer.io/latest?base={{CURRENCY_FROM}}&symbols={{CURRENCY_TO}}';

    protected $_messages = array();

     /**
     * HTTP client
     *
     * @var Varien_Http_Client
     */
    protected $_httpClient;

    public function __construct()
    {
        $this->_httpClient = new Varien_Http_Client();
    }

    protected function _convert($currencyFrom, $currencyTo, $retry=0)
    {
        $url = str_replace('{{CURRENCY_FROM}}', $currencyFrom, $this->_url);
        $url = str_replace('{{CURRENCY_TO}}', $currencyTo, $url);

        try {
            $response = $this->_httpClient
                ->setUri($url)
                ->setConfig(array('timeout' => Mage::getStoreConfig('currency/fixerio/timeout')))
                ->request('GET')
                ->getBody();

            $json = Mage::helper('core')->jsonDecode($response);
            if( !$json || !isset($json['rates'][$currencyTo])) {
                $this->_messages[] = Mage::helper('directory')->__('Cannot retrieve rate from %s.', $url);
                return null;
            }

            $rate = $json['rates'][$currencyTo];

            return (float) $rate;
        }
        catch (Exception $e) {
            if( $retry == 0 ) {
                $this->_convert($currencyFrom, $currencyTo, 1);
            } else {
                $this->_messages[] = Mage::helper('directory')->__('Cannot retrieve rate from %s.', $url);
            }
        }
    }
}
