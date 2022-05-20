<?php

namespace WMG\Migration\Override\Doctrine;

use Doctrine\DBAL\Schema\Index as BaseIndex;

class Index extends BaseIndex {

    public function setName($name) {        
        $this->_setName($name);
    }

}