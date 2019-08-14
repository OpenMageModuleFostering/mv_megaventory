<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Adminhtml
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Convert profiles run block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Mv_Megaventory_Block_Adminhtml_System_Convert_Profile_Run extends Mage_Adminhtml_Block_System_Convert_Profile_Run
{
    public function getProfile()
    {
        return Mage::registry('current_convert_profile');
    }

    protected function _toHtml()
    {
    	$profile = $this->getProfile();
    	
    	$profileId = $profile->getId();
    	
    	$exportProductsProfileId = Mage::getStoreConfig('megaventory/general/exportproductsprofileid');
    	$exportStockProfileId = Mage::getStoreConfig('megaventory/general/exportstockprofileid');
    	$exportClientsProfileId = Mage::getStoreConfig('megaventory/general/exportclientsprofileid');
    	
    	if ($profileId != $exportProductsProfileId && $profileId != $exportStockProfileId && $profileId != $exportClientsProfileId){
    		parent::_toHtml();
    	}
    	else 
    	{
    		ob_implicit_flush();
    		$profile->run();
    		if ($profileId == $exportClientsProfileId)
    			$html = '<h1>Megaventory Clients Export</h1><br/>';
    		else
    			$html = '<h1>Megaventory Products Export</h1><br/>';
    		
    		$html .= 'to_be_replaced';
    		$html .= '<br/>';
    		$html .= '<ul style="font-size:12px;font-weight:bold;">';
    		foreach ($profile->getExceptions() as $e) {
    			switch ($e->getLevel()) {
    				case Varien_Convert_Exception::FATAL:
    					$img = 'error_msg_icon.gif';
    					$liStyle = 'background-color:#FBB; ';
    					break;
    				case Varien_Convert_Exception::ERROR:
    					$img = 'error_msg_icon.gif';
    					$liStyle = 'background-color:#FDD; ';
    					break;
    				case Varien_Convert_Exception::WARNING:
    					$img = 'fam_bullet_error.gif';
    					$liStyle = 'background-color:#FFD; ';
    					break;
    				case Varien_Convert_Exception::NOTICE:
    					$img = 'fam_bullet_success.gif';
    					$liStyle = 'background-color:#DDF; ';
    					break;
    			}
    			$liStyle .= ' margin-top:4px;padding:2px 0px 2px 0px; ';
    			$html .= '<li style="'.$liStyle.'">';
    			$html .= '<img src="'.Mage::getDesign()->getSkinUrl('images/'.$img).'" class="v-middle"/>';
    			$html .= $e->getMessage().'</li>';
    		}
    		$html .= '</ul>';
    		
    		
    		
    		return $html;
    	}
    }
    
    protected function _afterToHtml($html)
    {
        return $html;
    }

}
