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
 * CatalogRule Observer resource model
 * 
 */
class EcomDev_Varnish_Model_Resource_Catalogrule_Observer
    extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * Initializes the db connection
     *
     */
    protected function _construct()
    {
        $this->_setResource('catalogrule');
    }

    /**
     * Returns list of affected product ids
     *
     * @param Mage_Catalog_Model_Product_Condition $condition
     * @return array
     */
    public function getAffectedProductIds($condition)
    {
        return $this->_getReadAdapter()->fetchCol(
            $condition->getIdsSelect($this->_getReadAdapter())
        );
    }
}
