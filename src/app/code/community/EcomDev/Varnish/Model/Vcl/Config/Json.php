<?php

class EcomDev_Varnish_Model_Vcl_Config_Json
    extends EcomDev_Varnish_Model_Vcl_Config_Array
{
    /**
     * Instantiate configuration
     *
     */
    public function __construct(array $config)
    {
        if (!isset($config['file']) || !is_file($config['file']) || !is_readable($config['file'])) {
            throw new InvalidArgumentException('JSON file cannot be read by file system');
        }

        $json = file_get_contents($config['file']);
        $data = json_decode($json, true);
        if ($data === null) {
            throw new InvalidArgumentException('JSON file has syntax errors');
        }

        parent::__construct($data);
    }

}
