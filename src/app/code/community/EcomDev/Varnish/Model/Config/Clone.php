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
 * Model for cloning of the fields 
 * 
 */
class EcomDev_Varnish_Model_Config_Clone
    extends Mage_Core_Model_Config_Data
{
    /**
     * Get field prefixes for generating translation config nodes
     *
     * @return array
     */
    public function getPrefixes()
    {
        $prefixes = array();
        foreach (Mage::helper('ecomdev_varnish')->getAllowedPages() as $name => $label) {
            $prefixes[] = array(
                'field' => $name.'_',
                'label' => $label
            );
        }

        return $prefixes;
    }
}
