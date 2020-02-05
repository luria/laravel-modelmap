<?php

namespace Luria\Modelmap;

use Luria\Modelmap\Model as GraphModel;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use ReflectionClass;

class ModelMapCommand extends Command
{
    const FORMAT_TEXT = 'text';
    const DEFAULT_FILENAME = 'modelMap';

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'modelmap:draw {filename?} {--format=xml}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Map models and relations to draw.io diagram.';

    /** @var ModelFinder */
    protected $modelFinder;

    /** @var RelationFinder */
    protected $relationFinder;

    /** @var Graph */
    protected $graph;

    /** @var GraphBuilder */
    protected $graphBuilder;

    public function __construct(ModelFinder $modelFinder, RelationFinder $relationFinder, GraphBuilder $graphBuilder)
    {
        parent::__construct();

        $this->relationFinder = $relationFinder;
        $this->modelFinder = $modelFinder;
        $this->graphBuilder = $graphBuilder;
    }

    public function handle()
    {
        $models = $this->getModelsThatShouldBeInspected();

        $this->info("Found {$models->count()} models.");
        $this->info("Inspecting model relations.");
        
        $this->command->getOutput()->progressStart($models->count());
        for ($i = 0; $i < 10; $i++) {
            sleep(1);
            $this->command->getOutput()->progressAdvance();
        }
        $this->command->getOutput()->progressFinish();
        $this->info(PHP_EOL);
        $this->info('Done.');

        /*
        $bar = $this->output->createProgressBar($models->count());

        $models->transform(function ($model) use ($bar) {
            $bar->advance();
            return new GraphModel(
                $model,
                (new ReflectionClass($model))->getShortName(),
                $this->relationFinder->getModelRelations($model)
            );
        });

        $graph = $this->graphBuilder->buildGraph($models);

        if ($this->option('format') === self::FORMAT_TEXT) {
            $this->info($graph->__toString());
            return;
        }

        $graph->export($this->option('format'), $this->getOutputFileName());

        $this->info(PHP_EOL);
        $this->info('Wrote diagram to ' . $this->getOutputFileName());
        */
    }

    protected function getOutputFileName(): string
    {
        return $this->argument('filename') ?:
            static::DEFAULT_FILENAME . '.' . $this->option('format');
    }

    protected function getModelsThatShouldBeInspected(): Collection
    {
        $directories = config('modelmap.directories');

        $modelsFromDirectories = $this->getAllModelsFromEachDirectory($directories);

        return $modelsFromDirectories;
    }

    protected function getAllModelsFromEachDirectory(array $directories): Collection
    {
        return collect($directories)
            ->map(function ($directory) {
                return $this->modelFinder->getModelsInDirectory($directory)->all();
            })
            ->flatten();
    }
}
