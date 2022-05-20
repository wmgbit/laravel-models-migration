<?php

namespace WMG\Migration\Override\Doctrine;

use Doctrine\DBAL\Schema\ForeignKeyConstraint as BaseForeignKeyConstraint;

class ForeignKeyConstraint extends BaseForeignKeyConstraint {

    public function setName($name) {        
        $this->_setName($name);
    }

}