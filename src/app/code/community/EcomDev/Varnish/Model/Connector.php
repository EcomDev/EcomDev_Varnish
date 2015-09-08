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


use ride\library\varnish\VarnishPool;
use ride\library\varnish\VarnishAdmin;
use ride\library\varnish\exception\VarnishException;

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
     * @var VarnishPool
     */
    protected $pool;

    /**
     * Returns nothing as a different library
     * used now for varnish communication
     *
     * @deprecated since 2.0.0
     */
    public function getAdapter()
    {
        return array();
    }

    /**
     * Return Varnish Admin socket
     * 
     * @return VarnishPool
     */
    public function getVarnishPool()
    {
        if ($this->pool === null) {
            $this->_initVarnishPool();
        }
        
        return $this->pool;
    }

    /**
     * Initializes varnish pool
     *
     * @return $this
     * @throws VarnishException
     */
    protected function _initVarnishPool()
    {
        $this->pool = new VarnishPool();

        $addresses =  Mage::getStoreConfig(self::XML_PATH_VARNISH_SERVER);
        $secret = Mage::getStoreConfig(self::XML_PATH_VARNISH_SECRET) . "\n";
        
        $lines = explode("\n", $addresses);
        $instantiated = array();
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (!preg_match('/^[a-z0-9\.]+:\d+$/', $line) 
                || isset($instantiated[$line])) {
                continue;
            }
            
            list($host, $port) = explode(':', $line);

            try {
                $this->pool->addServer(new VarnishAdmin($host, $port, $secret));
            } catch (VarnishException $e) {
                Mage::logException($e);
            }

            $instantiated[$line] = true;
        }

        $this->pool->setIgnoreOnFail(true);

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
        try {
            $this->getVarnishPool()->ban('obj.http.' . self::HEADER_OBJECTS . ' ~ ' . implode('|', $tags));
        } catch (VarnishException $e) {
            Mage::logException($e);
        }

        return $this;
    }

    /**
     * Invokes method on each adapter.
     *
     * @param string $method
     * @param null|string|int $firstArg
     * @param null|string|int $secondArg
     * @return $this
     * @deprecated since 2.0.0
     */
    public function walk($method, $firstArg = null, $secondArg = null)
    {
        if ($method === 'purge') {
            $method = 'ban';
        }

        if ($firstArg === null && $secondArg === null) {
            $this->getVarnishPool()->$method();
        } elseif ($secondArg === null) {
            $this->getVarnishPool()->$method($firstArg);
        } else {
            $this->getVarnishPool()->$method($firstArg, $secondArg);
        }

        return $this;
    }
}
