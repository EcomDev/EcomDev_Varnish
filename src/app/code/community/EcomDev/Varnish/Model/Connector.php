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
 * @copyright  Copyright (c) 2020 EcomDev BV (http://www.ecomdev.org)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Ivan Chepurnyi <ivan.chepurnyi@ecomdev.org>
 */


/**
 * Connector to the varnish admin
 * 
 */
class EcomDev_Varnish_Model_Connector
{
    const XML_PATH_VARNISH_SERVER = 'varnish/settings/http_ban';

    const HEADER_OBJECTS = EcomDev_Varnish_Helper_Data::HEADER_OBJECTS;

    /**
     * Return Varnish Admin socket
     *
     * DEPRACTED, all ban operations now happen via HTTP
     *
     * @return array[]
     * @deprecated since 3.0.0
     */
    public function getVarnishPool()
    {
        return [];
    }

    /**
     * @return string[]
     */
    private function parseVarnishServers()
    {
        $addresses =  Mage::getStoreConfig(self::XML_PATH_VARNISH_SERVER);

        $lines = explode("\n", $addresses);
        $servers = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if (!preg_match('/^[a-zA-Z0-9_\-.]+:\d+$/', $line)) {
                continue;
            }

            list($host, $port) = explode(':', $line);

            $servers[$line] = sprintf('http://%s:%s', $host, $port);
        }

        return $servers;
    }

    /**
     * Ban list of tags
     * 
     * @param array $tags
     * @return $this
     */
    public function banTags($tags)
    {
        if ($tags) {
            $this->banHeader(
                self::HEADER_OBJECTS,
                '(' . implode('|', $tags) . ')'
            );
        }

        return $this;
    }

    /**
     * Ban pages by header value
     *
     * @param string $headerName
     * @param string $headerValue
     */
    public function banHeader(string $headerName, string $headerValue)
    {
        try {

            $this->executeOnPool(
                'header',
                [
                    'header' => $headerName,
                    'value' => $headerValue
                ]
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Ban pages that start with specified URL
     *
     * @param string $url
     */
    public function banUrl(string $url)
    {
        try {
            $this->executeOnPool(
                'url',
                ['url' => $url]
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    /**
     * Ban pages with specified full action name
     *
     * @param string $page
     */
    public function banPage(string $page)
    {
        try {
            $this->executeOnPool(
                'page',
                ['page' => $page]
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    private function executeOnPool(string $type, array $params) {
        $headers = [
            'X-Magento-Method' => 'BAN'
        ];
        
        foreach ($params as $paramName => $paramValue) {
            $headers[sprintf('X-Ban-%s', ucfirst($paramName))] = $paramValue;
        }

        foreach ($this->parseVarnishServers() as $server => $baseUrl) {
            $client = Mage_HTTP_Client::getInstance();
            $client->setHeaders($headers);
            $client->get(sprintf('%s/%s', $baseUrl, $type));

            if (!$client->getStatus() != 204) {
                Mage::log(
                    sprintf('Unsuccessful BAN request to Varnish server (%s %s): %s', $server, $type, $client->getBody()),
                    Zend_Log::WARN
                );
            }
        }
    }
}
