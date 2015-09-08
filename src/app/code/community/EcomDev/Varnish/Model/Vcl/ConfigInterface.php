<?php

/**
 * Configuration interface
 *
 * It is used to supply data into configuration renderer
 *
 */
interface EcomDev_Varnish_Model_Vcl_ConfigInterface
{
    /**
     * List of backend servers
     *
     * Each backend is a pair of ip address and port
     *
     * @return string[][]
     */
    public function getBackendList();

    /**
     * Returns backend option from configuration
     *
     * @param string $backendName a identifier from array keys of backend list
     * @param string $option
     * @return string
     */
    public function getBackendOption($backendName, $option, $defaultOption = null);

    /**
     * Returns list of balanced backend names
     *
     * @return string[]
     */
    public function getBalancedBackendList();

    /**
     * Returns name of the admin backend
     *
     * @return string
     */
    public function getAdminBackend();

    /**
     * Returns path for admin panel requests.
     *
     * @return string
     */
    public function getAdminPath();

    /**
     * Probe url, that is going to be used for a balanced backend
     *
     * @return string
     */
    public function getProbeUrl();

    /**
     * Probe option
     *
     * @param string $name
     * @param null|string $defaultValue
     * @return string
     */
    public function getProbeOption($name, $defaultValue = null);

    /**
     * Type director that is used
     *
     * @return string
     */
    public function getDirectorType();

    /**
     * Returns list of local ip addresses, that can supply `getIpHeader()` header
     *
     * @return string[]
     */
    public function getLocalIps();

    /**
     * Return list of ip addreses that are allowed to perform Ctrl+F5 (Win) or Cmd+Shift+R (Mac)
     *
     * @return string[]
     */
    public function getRefreshIps();

    /**
     * List of query parameters, that are meant to be a blacklist
     *
     * Query param name can contain a reg-exp in it
     *
     * @return string[]
     */
    public function getUrlQueryBlacklist();

    /**
     * List of cookie names, that are not used for Magento, e.g. tracking cookies, so can be easily ignored.
     *
     * @return string[]
     */
    public function getCookieWhiteList();

    /**
     * Header to identify wich request is offloaded
     *
     * @return string
     */
    public function getOffloadHeader();

    /**
     * Header for retrieving an ip address of a end client from a possible proxy server
     *
     * In this case proxy server ip address should be specified in logcal ip addresses
     *
     * @return string
     */
    public function getIpHeader();

    /**
     * Returns path to include device detect library file
     *
     * @return string
     */
    public function getDeviceDetectLibraryPath();

    /**
     * Return grace period for a cache items
     *
     * @return string
     */
    public function getGracePeriod();
}
