<?php

/* @var $this Mage_Core_Model_Resource_Setup */

if (!Mage::getStoreConfig('varnish/settings/esi_key')) {
    $this->setConfigData('varnish/settings/esi_key', bin2hex(random_bytes(16)));
}
