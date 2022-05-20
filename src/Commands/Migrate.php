<?php

namespace WMG\Migration\Commands;

use Illuminate\Console\Command;
use Doctrine\DBAL\Schema\Comparator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Database\Schema\Blueprint;
use WMG\Migration\Override\Blueprint as OverBlueprint;
use WMG\Migration\Override\Doctrine\Table as OverTable;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use WMG\Migration\Utils\Utils;
use DB;

class Migrate extends Command
{
    protected $signature = 'migrate:models {--f|--fresh} {--dry-run} {--force} {--show-sql}';
    protected $description = 'Execute migration definitions found in models';

    private $traitedModels = [];
    private $showSQL = false;

    public function handle()
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->warn('Use the <info>--force</info> to migrate in production.');
            return;
        }

        if ($this->option('show-sql') || $this->option('dry-run')) {
            $this->showSQL = true;
        }

        $this->handleTraditionalMigrations();
        $this->handleAutomaticMigrations();

        $this->info('Automatic migration completed successfully.');
    }

    private function handleTraditionalMigrations()
    {
        $command = 'migrate';

        if ($this->option('fresh')) {
            $command .= ':fresh';
        }

        if ($this->option('force')) {
            $command .= ' --force';
        }

        Artisan::call($command, [], $this->getOutput());
    }

    private function handleAutomaticMigrations()
    {
        $models = $this->getModels();

        $this->traitedModels = [];
        foreach ($models as $modelEl) {
            $this->migrate($modelEl, $models);
        }
    }

    private function detectDependencies(&$models)
    {
        foreach ($models as &$modelEl) {
            $modelEl['dependencies'] = [];
            foreach ($modelEl['blueprint']->getCommands() as $command) {
                if ($command['index'] && $command['references'] && $command['on'] && isset($models[$command['on']])) $modelEl['dependencies'][] = $command['on'];
            }
        }
        return $models;
    }

    private function getModels()
    {
        $path = app_path('Models');
        $namespace = app()->getNamespace();
        $models = [];

        if (!is_dir($path)) {
            return $models;
        }

        foreach ((new Finder)->in($path) as $modelClass) {
            $modelClass = $namespace . str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($modelClass->getRealPath(), realpath(app_path()) . DIRECTORY_SEPARATOR)
            );

            if (!method_exists($modelClass, 'migration')) continue;
            $model = app($modelClass);

            $modelTable = $model->getTable();
            $table = new OverBlueprint('_hold_' . $modelTable);

            $model->getConnection()->useDefaultSchemaGrammar();
            $grammar = $model->getConnection()->getSchemaGrammar();

            $table->create();
            $model->migration($table);



            $sqlQueries = $table->toSql($model->getConnection(), $model->getConnection()->getSchemaGrammar());
            $table->renameIndexesInCommands('_hold_');


            $models[$modelTable] = [
                'table' => $modelTable,
                'tmp_table' => '_hold_' . $modelTable,
                'class' => $modelClass,
                'object' => $model,
                'sqls' => $sqlQueries,
                'blueprint' => $table,
            ];
        }

        $this->detectDependencies($models);
        return $models;
    }

    private function migrate(&$modelEl, &$models)
    {
        foreach ($modelEl['dependencies'] as $dependantModelTable) {
            if (!isset($this->traitedModels[$dependantModelTable])) {
                $this->migrate($models[$dependantModelTable], $models);
            }
        }

        if ($this->traitedModels[$modelEl['table']] ?? false) return;
        $this->line(sprintf('<info>Comparing table %s</info>', $modelEl['table']));

        $model = $modelEl['object'];
        $schemaManager = $model->getConnection()->getDoctrineSchemaManager();

        Schema::dropIfExists($modelEl['tmp_table']);

        foreach ($modelEl['sqls'] as $sql) {
            if ($this->showSQL) dump($sql);
            try {
                DB::unprepared($sql);
            } catch (\Exception $e) {
                $this->line('<error>SQL Error :</error> ' . $e->getMessage());
                throw $e;
            }
        }
        $tempTableDetails = $schemaManager->listTableDetails($modelEl['tmp_table']);
        $tempTableDetails = Utils::castToObject($tempTableDetails, OverTable::class);
        $tempTableDetails->renameConstraints();

        Schema::drop($modelEl['tmp_table']);

        if (Schema::hasTable($modelEl['table'])) {

            $modelTableDetails = $schemaManager->listTableDetails($modelEl['table']);
            $tableDiff = (new Comparator)->diffTable($modelTableDetails, $tempTableDetails);

            // dump($tempTableDetails);

            if ($tableDiff) {
                if (!$this->option('dry-run')) Schema::disableForeignKeyConstraints();
                try {
                    foreach ($schemaManager->getDatabasePlatform()->getAlterTableSQL($tableDiff) as $el) {
                        if ($this->showSQL) $this->line($el);
                        if (!$this->option('dry-run')) DB::unprepared($el);
                    }                    
                    $this->line(sprintf('<info>Table %s updated</info>', $modelEl['table']));
                } catch (\Exception $e) {
                    $this->line('<error>SQL Error :</error> ' . $e->getMessage());
                    throw $e;
                }
            }
        } else {            
            if (!$this->option('dry-run')) {
                Schema::create($modelEl['table'], function ($table) use ($model) {
                    $model->migration($table);
                });
                $this->line(sprintf('<info>Table %s created</info>', $modelEl['table']));
            }
        }

        $this->traitedModels[$modelEl['table']] = true;
    }
}
