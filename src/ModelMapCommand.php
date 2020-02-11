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

    const DRAWIO_HEAD = '
        <mxGraphModel dx="677" dy="380" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="850" pageHeight="1100" math="0" shadow="0">
            <root>
                <mxCell id="0"/>
                    <mxCell id="1" parent="0"/>
    ';
    
    const DRAWIO_FOOT = '
        </root>
            </mxGraphModel>
    ';

    const MODEL_HEADER = '
        <mxCell id="model_id" value="model_id" style="swimlane;fontStyle=0;childLayout=stackLayout;horizontal=1;startSize=29;fillColor=#e0e0e0;horizontalStack=0;resizeParent=1;resizeParentMax=0;resizeLast=0;collapsible=1;marginBottom=0;swimlaneFillColor=#ffffff;align=center;fontSize=14;" parent="1" vertex="1">
            <mxGeometry x="150" y="160" width="170" height="107" as="geometry">
                <mxRectangle x="250" y="140" width="100" height="26" as="alternateBounds"/>
            </mxGeometry>
        </mxCell>
    ';

    const MODEL_CELL = '
        <mxCell id="model_id" value="model_id" style="text;strokeColor=none;fillColor=none;spacingLeft=4;spacingRight=4;overflow=hidden;rotatable=0;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;fontSize=12;" parent="TOwnxliuAN4ELFWvssxb-1" vertex="1">
            <mxGeometry y="29" width="170" height="26" as="geometry"/>
        </mxCell>
    ';

    const MODEL_RELATION = '
        <mxCell id="model_destination_id" style="edgeStyle=orthogonalEdgeStyle;rounded=1;orthogonalLoop=1;jettySize=auto;html=1;entryX=0;entryY=0.5;entryDx=0;entryDy=0;endArrow=blockThin;endFill=1;" edge="1" parent="1" source="model_origin_id" target="QAVnj0-Ibj5Dj50Q-H36-2">
            <mxGeometry relative="1" as="geometry"/>
        </mxCell>
    ';

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

    public function __construct(ModelFinder $modelFinder, RelationFinder $relationFinder)
    {
        parent::__construct();

        $this->relationFinder = $relationFinder;
        $this->modelFinder = $modelFinder;
    }

    public function handle()
    {
        $models = $this->getModelsThatShouldBeInspected();
        $graph = '';

        $this->info("Found {$models->count()} models.");
        $this->info("Inspecting model relations.");
        
        $this->getOutput()->progressStart($models->count());

        $graph .= self::DRAWIO_HEAD . PHP_EOL;
        foreach ($models as $model){
            sleep(1);
            $graph .= preg_replace( '/model_id/', (new ReflectionClass($model))->getShortName(), self::MODEL_HEADER ) . PHP_EOL;
            $this->getOutput()->progressAdvance();
        }

        $graph .= self::DRAWIO_FOOT . PHP_EOL;
        $this->getOutput()->progressFinish();
        $this->info('Done.');
        $this->info($graph);

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
