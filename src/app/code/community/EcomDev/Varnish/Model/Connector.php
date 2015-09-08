<?php
/**
 * Varnish extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   EcomDev
 * @package    EcomDev_Varnish
 * @copyright  Copyright (c) 2014 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */

/**
 * Connector to the varnish admin
 * 
 */
class EcomDev_Varnish_Model_Connector
{
    const XML_PATH_VARNISH_SERVER = 'varnish/settings/server';
    const XML_PATH_VARNISH_SECRET = 'varnish/settings/secret';
    const HEADER_OBJECTS = EcomDev_Varnish_Helper_Data::HEADER_OBJECTS;

    /**
     * Admin socket for varnish system
     * 
     * @var VarnishAdminSocket[]
     */
    protected $_adapter;

    /**
     * Return Varnish Admin socket
     * 
     * @return VarnishAdminSocket[]
     */
    public function getAdapter()
    {
        if ($this->_adapter === null) {
            $this->_initAdapter();
        }
        
        return $this->_adapter;
    }
    
    protected function _initAdapter()
    {
        $this->_adapter = array();
        $addresses =  Mage::getStoreConfig(self::XML_PATH_VARNISH_SERVER);
        $secret = Mage::getStoreConfig(self::XML_PATH_VARNISH_SECRET);
        
        $lines = explode("\n", $addresses);
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (!preg_match('/^[a-z0-9\.]+:\d+$/', $line) 
                || isset($this->_adapter[$line])) {
                continue;
            }
            
            list($host, $port) = explode(':', $line);
            try {
                $this->_adapter[$line] = new VarnishAdminSocket($host, $port, '3');
                $this->_adapter[$line]->set_auth($secret . "\n");
                $this->_adapter[$line]->connect();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        
        return $this;
    }

    /**
     * Ban list of tags
     * 
     * @param array $tags
     * @return $this
     */
    public function banTags($tags)
    {
        $this->walk(
            'purge', 
            'obj.http.'.self::HEADER_OBJECTS . ' ~ ' . implode('|', $tags)
        );
        return $this;
    }

    /**
     * Invokes method on each adapter.
     *
     * @param string $method
     * @param null|string|int $firstArg
     * @param null|string|int $secondArg
     * @return $this
     */
    public function walk($method, $firstArg = null, $secondArg = null)
    {
        foreach ($this->getAdapter() as $adapter) {
            if ($firstArg === null && $secondArg === null) {
                $adapter->$method();
            } elseif ($secondArg === null) {
                $adapter->$method($firstArg);
            } else {
                $adapter->$method($firstArg, $secondArg);
            }
        }
        
        return $this;
    }
}