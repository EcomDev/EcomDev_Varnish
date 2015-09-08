<?php

class EcomDev_Varnish_Model_Vcl_Config_Array
    implements EcomDev_Varnish_Model_Vcl_ConfigInterface
{
    /**
     * Sets up configuration options
     *
     * @var string[][]
     */
    protected $config;

    /**
     * Backend list
     *
     * @var string[][]
     */
    protected $backendList;

    /**
     * Balanced backend list
     *
     * @var string[]
     */
    protected $balancedBackendList;

    /**
     * Admin backend list
     *
     * @var string
     */
    protected $adminBackend;

    /**
     * Instantiate configuration
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $emptyArray = function ($array) { return is_array($array) && !empty($array); };
        $requiredOptions = array(
            'backend' => $emptyArray,
            'default_backend_option' => function ($options) {
                return is_array($options) && array_diff(
                    array('first_byte_timeout', 'connect_timeout', 'between_bytes_timeout'),
                    array_keys($options)
                ) === array();
            }
        );

        foreach ($requiredOptions as $optionName => $check) {
            if (!isset($optionName)) {
                new InvalidArgumentException(sprintf('Missing "%s" option, that is required', $optionName));
            }

            if (is_callable($check)) {
                if (!call_user_func($check, $this->config[$optionName])) {
                    new InvalidArgumentException(sprintf('Option "%s" does not match expected type', $optionName));
                }
            } elseif (empty($this->config[$optionName])) {
                new InvalidArgumentException(sprintf('Option "%s" is empty', $optionName));
            }
        }

        $this->config = $config;
    }

    /**
     * Returns configuration array option
     *
     * @param $optionName
     * @param mixed $defaultValue
     * @return mixed
     */
    protected function getConfigArrayOption($optionName, $defaultValue = null)
    {
        if (!isset($this->config[$optionName])) {
            return $defaultValue;
        }

        return $this->config[$optionName];
    }

    /**
     * Initializes backend options
     *
     * @return $this
     */
    protected function initBackend()
    {
        $this->backendList = array();
        $this->balancedBackendList = array();

        foreach ($this->getConfigArrayOption('backend', array()) as $backendName => $option) {
            if (!isset($option['ip']) || !isset($option['port'])) {
                continue;
            }

            $this->backendList[$backendName] = array($option['ip'], $option['port']);
            if (!empty($option['balanced'])) {
                $this->balancedBackendList[] = $backendName;
            }

            if (!empty($option['admin'])) {
                $this->adminBackend = $backendName;
            }
        }

        if ($this->adminBackend === null) {
            $this->adminBackend = key($this->backendList);
        }

        return $this;
    }

    /**
     * List of backend servers
     *
     * Each backend is a pair of ip address and port
     *
     * @return string[][]
     */
    public function getBackendList()
    {
        if ($this->backendList === null) {
            $this->initBackend();
        }

        return $this->backendList;
    }

    /**
     * Returns backend option from configuration
     *
     * @param string $backendName a identifier from array keys of backend list
     * @param string $option
     * @return string
     */
    public function getBackendOption($backendName, $option, $defaultOption = null)
    {
        $backendConfig = $this->getConfigArrayOption('backend', array());
        if (isset($backendConfig[$backendName][$option])) {
            return $backendConfig[$backendName][$option];
        }

        $optionConfig = $this->getConfigArrayOption('default_backend_option', array());

        if (isset($optionConfig[$option])) {
            return $optionConfig[$option];
        }

        return $defaultOption;
    }

    /**
     * Returns list of balanced backend names
     *
     * @return string[]
     */
    public function getBalancedBackendList()
    {
        if ($this->balancedBackendList === null) {
            $this->initBackend();
        }

        return $this->balancedBackendList;
    }

    /**
     * Returns name of the admin backend
     *
     * @return string|null
     */
    public function getAdminBackend()
    {
        if ($this->adminBackend === null) {
            $this->initBackend();
        }

        return $this->adminBackend;
    }

    /**
     * Returns path for admin panel requests.
     *
     * @return string|null
     */
    public function getAdminPath()
    {
        return $this->getConfigArrayOption('admin_path', 'admin');
    }

    /**
     * Probe url, that is going to be used for a balanced backend
     *
     * @return string
     */
    public function getProbeUrl()
    {
        return $this->getConfigArrayOption('probe_url', false);
    }

    /**
     * Probe option
     *
     * @param string $name
     * @param string|null $defaultOption
     * @return string
     */
    public function getProbeOption($name, $defaultOption = null)
    {
        $options = $this->getConfigArrayOption('probe', array());

        if (isset($options[$name])) {
            return $options[$name];
        }

        return $defaultOption;
    }

    /**
     * Type director that is used
     *
     * @return string
     */
    public function getDirectorType()
    {
        return $this->getConfigArrayOption('directory_type', 'client');
    }

    /**
     * Returns list of local ip addresses, that can supply `getIpHeader()` header
     *
     * @return string[]
     */
    public function getLocalIps()
    {
        return $this->getConfigArrayOption('local_ip', array());
    }

    /**
     * Return list of ip addreses that are allowed to perform Ctrl+F5 (Win) or Cmd+Shift+R (Mac)
     *
     * @return string[]
     */
    public function getRefreshIps()
    {
        return $this->getConfigArrayOption('refresh_ip', array('127.0.0.1'));
    }

    /**
     * List of query parameters, that are meant to be a blacklist
     *
     * Query param name can contain a reg-exp in it
     *
     * @return string[]
     */
    public function getUrlQueryBlacklist()
    {
        return $this->getConfigArrayOption(
            'url_query_black_list',
            array(
                'gclid',
                'cx',
                'ie',
                'cof',
                'siteurl',
                'zanpid',
                'origin',
                'utm_[a-z]+',
                'mr:[A-z]+',
                'fb_local:[A-z]+'
            )
        );
    }

    /**
     * List of cookie names, that are used for Magento
     *
     * @return string[]
     */
    public function getCookieWhiteList()
    {
        return $this->getConfigArrayOption(
            'cookie_white_list',
            array(
                'PHPSESSID',
                'frontend',
                'adminhtml',
                'store',
                EcomDev_Varnish_Helper_Data::COOKIE_TOKEN_CHECKSUM
            )
        );
    }

    /**
     * Header to identify wich request is offloaded
     *
     * @return string
     */
    public function getOffloadHeader()
    {
        return $this->getConfigArrayOption('offload_header', 'X-Forwarded-Proto');
    }

    /**
     * Header for retrieving an ip address of a end client from a possible proxy server
     *
     * In this case proxy server ip address should be specified in local ip addresses
     *
     * @return string
     */
    public function getIpHeader()
    {
        return $this->getConfigArrayOption('ip_header', 'X-Forwarded-For');
    }

    /**
     * Returns path to include device detect library file
     *
     * @return string
     */
    public function getDeviceDetectLibraryPath()
    {
        return $this->getConfigArrayOption('divice_detect_library_path', false);
    }

    /**
     * Return grace period for a cache items
     *
     * @return string
     */
    public function getGracePeriod()
    {
        return $this->getConfigArrayOption('grace_period', '5h');
    }

}
