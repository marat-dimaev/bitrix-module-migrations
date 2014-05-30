<?php
/**
 * @author Maxim Sokolovsky <sokolovsky@worksolutions.ru>
 */

namespace WS\Migrations\Handlers\Iblock;


use WS\Migrations\Catcher;
use WS\Migrations\ChangeHandler;

class IblockUpdate extends ChangeHandler{
    private $_beforeChangeData = array();

    /**
     * Name of Handler in Web interface
     * @return string
     */
    public function getName() {
        $this->getLocalization()->getDataByPath('name');
    }

    public function beforeChange($data) {
        $iblockId = $data[0]['ID'];
        $data = \CIBlock::GetArrayByID($iblockId);
        $this->_beforeChangeData[$iblockId] = $data;
    }

    public function afterChange($data, Catcher $catcher) {
        $catcher->fixChangeData(array(
            'before' => $this->_beforeChangeData,
            'after' => $data[0]
        ));
    }
}