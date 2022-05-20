<?php

namespace WMG\Migration\Override\Doctrine;

use Doctrine\DBAL\Schema\UniqueConstraint as BaseUniqueConstraint;

class UniqueConstraint extends BaseUniqueConstraint {

    public function setName($name) {        
        $this->_setName($name);
    }

}