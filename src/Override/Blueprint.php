<?php

namespace WMG\Migration\Override;

use Illuminate\Database\Schema\Blueprint as BaseBlueprint;


class Blueprint extends BaseBlueprint
{
    public function renameIndexesInCommands($prefix)
    {
        foreach ($this->commands as $c) {
            if ($c['index']) $c['index'] = $prefix . $c['index'];
        }
    }
}
