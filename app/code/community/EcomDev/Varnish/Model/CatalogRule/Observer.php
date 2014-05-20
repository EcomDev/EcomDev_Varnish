<?php

class EcomDev_Varnish_Model_CatalogRule_Observer extends Mage_Core_Model_Abstract
{
    /**
     * List of affected product ids
     *
     * @var array
     */
    protected $_productIds = array();

    /**
     * Initialize the resource model
     *
     */
    protected function _construct()
    {
        $this->_init('ecomdev_varnish/catalogrule_observer');
    }

    /**
     * Sets time limit to zero to let admin user apply
     * catalog price rules
     *
     * @param Varien_Event_Observer $observer
     */
    public function preApply(Varien_Event_Observer $observer)
    {
        set_time_limit(0);
    }

    /**
     * Collects affected products by price rules
     *
     * @param Varien_Event_Observer $observer
     */
    public function retrieveAppliedProducts(Varien_Event_Observer $observer)
    {
        $this->_productIds = $this->_getResource()->getAffectedProductIds(
            $observer->getEvent()->getProductCondition()
        );
    }

    /**
     * Clears cache after applying the price rules
     *
     * @param Varien_Event_Observer $observer
     */
    public function postApply(Varien_Event_Observer $observer)
    {
        $productIds = array();
        foreach ($this->_productIds as $productId) {
            $productIds[] = EcomDev_Varnish_Model_Processor_Product::TAG_PREFIX  . $productId;
        }

        // Run cleaning process separately if amount of stored tags too large
        $chunks = array_chunk(
            $productIds,
            500
        );

        foreach ($chunks as $tags) {
            Mage::getSingleton('ecomdev_varnish/connector')->banTags($tags);
        }

        $this->_productIds = array();
    }
}