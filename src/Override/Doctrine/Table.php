<?php

namespace WMG\Migration\Override\Doctrine;

use Doctrine\DBAL\Schema\Table as BaseTable;
use WMG\Migration\Utils\Utils;
use WMG\Migration\Override\Doctrine\Index;
use WMG\Migration\Override\Doctrine\ForeignKeyConstraint;
use WMG\Migration\Override\Doctrine\UniqueConstraint;

class Table extends BaseTable
{
    public function renameBlock($key, $class)
    {
        if (!isset($this->$key)) return;
        $els = [];
        foreach($this->$key as $k=>$v) {
            $v = Utils::castToObject($v, $class);
            $newName = preg_match('/^_hold_/', $v->getName()) ? preg_replace('/^_hold_/', '', $v->getName()) : $k;
            $v->setName($newName);
            $els[$newName] = $v;
        }
        $this->$key = $els;
    }

    public function renameConstraints()
    {
        $this->renameBlock('_indexes', Index::class);
        $this->renameBlock('_fkConstraints', ForeignKeyConstraint::class);
        $this->renameBlock('uniqueConstraints', UniqueConstraint::class);        
    }
}
