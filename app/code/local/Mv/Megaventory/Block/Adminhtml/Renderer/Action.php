<?php

class Mv_Megaventory_Block_Adminhtml_Renderer_Action extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Action {

    public function render(Varien_Object $row) {
    	
    	if ($row['result'] != 'success'){
            $actions[] =
                    array(
                        'url' => "javascript:MegaventoryManager.redo('" . $this->getUrl('*/*/redoLog', array('id' => $row->getId())) . "','" . $row->getId() . "')",
                        'caption' => 'Redo',
                        'id' => 'redo'
            );
    	}
            
            $actions[] =
            array(
            		'url' => $this->getUrl('*/*/deleteLog', array('id' => $row->getId())) . "','" . $row->getId(),
            		'caption' => 'Delete',
            		'id' => 'delete'
            );

           
        $this->getColumn()->setActions(
                $actions
        );
        return parent::render($row);
    }

}

