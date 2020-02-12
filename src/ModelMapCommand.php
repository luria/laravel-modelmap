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

    const MODEL_WIDTH = '170';
    const MODEL_X_GAP = '50';
    const MODEL_Y_GAP = '50';

    const DRAWIO_HEAD = '
        <mxGraphModel dx="1422" dy="798" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="1169" pageHeight="827" math="0" shadow="0">
             <root>
                <mxCell id="0"/>
                    <mxCell id="1" parent="0"/>
    ';
    
    const DRAWIO_FOOT = '
        </root>
            </mxGraphModel>
    ';

    const MODEL_HEADER = '
        <mxCell id="model_id" value="model_name" style="swimlane;fontStyle=0;childLayout=stackLayout;horizontal=1;startSize=29;fillColor=#e0e0e0;horizontalStack=0;resizeParent=1;resizeParentMax=0;resizeLast=0;collapsible=1;marginBottom=0;swimlaneFillColor=#ffffff;align=center;fontSize=14;" parent="1" vertex="1">
            <mxGeometry x="model_x" y="model_y" width="model_width" height="107" as="geometry">
                <mxRectangle x="250" y="140" width="100" height="26" as="alternateBounds"/>
            </mxGeometry>
        </mxCell>
    ';

    const MODEL_CELL = '
        <mxCell id="cell_id" value="cell_name" style="text;strokeColor=none;fillColor=none;spacingLeft=4;spacingRight=4;overflow=hidden;rotatable=0;points=[[0,0.5],[1,0.5]];portConstraint=eastwest;fontSize=12;" parent="parent_id" vertex="1">
            <mxGeometry y="29" width="170" height="26" as="geometry"/>
        </mxCell>
    ';

    const MODEL_RELATION = '
        <mxCell id="relation_id" style="edgeStyle=orthogonalEdgeStyle;rounded=1;orthogonalLoop=1;jettySize=auto;html=1;entryX=0;entryY=0.5;entryDx=0;entryDy=0;endArrow=blockThin;endFill=1;" edge="1" parent="1" source="source_id" target="target_id">
            <mxGeometry relative="1" as="geometry"/>
        </mxCell>
    ';

    /*
    const MODEL_RELATION = '
        <mxCell id="relation_id" style="edgeStyle=orthogonalEdgeStyle;rounded=1;orthogonalLoop=1;jettySize=auto;html=1;entryX=0;entryY=0.5;entryDx=0;entryDy=0;endArrow=blockThin;endFill=1;sourcePortConstraint=origin_constraint;targetPortConstraint=destination_constraint" edge="1" parent="1" source="model_origin_id" target="model_destination_id">
            <mxGeometry relative="1" as="geometry"/>
        </mxCell>
    ';
    */

    const MODEL_RELATION_LABEL = '
        <mxCell id="relation_label_id" value="&lt;b&gt;label_value&lt;/b&gt;" style="text;html=1;align=center;verticalAlign=middle;resizable=0;points=[];labelBackgroundColor=#ffffff;" vertex="1" connectable="0" parent="parent_id">
        <mxGeometry x="0.0065" y="-3" relative="1" as="geometry">
            <mxPoint as="offset"/>
        </mxGeometry>
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
        // Graph
        $graph = '';
        // Graph Head
        $graphHead = self::DRAWIO_HEAD . PHP_EOL;
        // Graph Models
        $graphModels = '';
        // Graph Cells
        $graphCells = '';
        // Graph Relations
        $graphRelations = '';
        // Graph Relation Labels
        $graphLabels = '';
        // Graph Footer 
        $graphFooter = self::DRAWIO_FOOT . PHP_EOL;

        $models = $this->getModelsThatShouldBeInspected();
        $this->info("Found {$models->count()} models.");
        $this->info("Inspecting model relations.");
        
        $this->getOutput()->progressStart($models->count());

        // Models
        $modelIteration = 0;
        foreach ($models as $model){
            //sleep(1);

            $modelShortName = (new ReflectionClass($model))->getShortName();
            $graphModels .= 
                preg_replace( '/model_id/', $modelShortName,
                preg_replace( '/model_name/', $modelShortName, 
                preg_replace( '/model_width/', self::MODEL_WIDTH, 
                preg_replace( '/model_x/', self::MODEL_WIDTH * $modelIteration + self::MODEL_X_GAP * $modelIteration + $modelIteration,
                preg_replace( '/model_y/', self::MODEL_Y_GAP,
                self::MODEL_HEADER 
            ))))) . PHP_EOL;
            
            $modelIteration++;

            // Properties and Relations for this Model
            foreach($this->relationFinder->getModelRelations($model) as $relation){
                dump($model, $relation);
                
                $relatedModel   = $relation->getName();
                $relatedKey     = $relation->getForeignKey();

                $cellName       = $relation->getLocalKey();
                $cellId         = $modelShortName .'-'. $cellName;

                $relationType   = $relation->getType();
                $relationId     = $modelShortName .'-'. $relationType .'-'. $relatedModel;
                $relationName   = $modelShortName .'-'. $relationType .'-'. $relatedModel .'-label';
                
                if ( !preg_match('/notifications/i', $relatedModel) ){

                    // Properties
                    $graphCells .= 
                        preg_replace( '/parent_id/',    $modelShortName,
                        preg_replace( '/cell_id/',      $cellId,
                        preg_replace( '/cell_name/',    $cellName,
                        self::MODEL_CELL 
                    ))) . PHP_EOL;
                
                    // Relations
                    switch( $relationType ){
                        case 'HasOne':
                        case 'MorphMany':
                            $sourceId = $cellId;
                            $targetId = ucfirst($relatedModel) .'-'. $relatedKey;
                            //$originConstraint = 'west';
                            //$destinationConstraint = 'east';
                            break;
                        case 'BelongsTo':
                        case 'BelongsToMany':
                            $sourceId = ucfirst($relatedModel) .'-'. $relatedKey;
                            $targetId = $cellId;
                            //$originConstraint = 'east';
                            //$destinationConstraint = 'west';
                            break;
                        default:
                            $sourceId = $targetId = 'User';
                            break;
                    }

                    /*  Looks like constraints are not needed  */ 
                    //preg_replace( '/origin_constraint/', $originConstraint,
                    //preg_replace( '/destination_constraint/', $destinationConstraint,
                    $graphRelations .=
                        preg_replace( '/relation_id/', $relationId,
                        preg_replace( '/source_id/', $sourceId,
                        preg_replace( '/target_id/', $targetId,
                        self::MODEL_RELATION
                    ))) . PHP_EOL;

                    $graphLabels .=
                        preg_replace( '/relation_label_id/', $relationName,
                        preg_replace( '/label_value/', $relationType,
                        preg_replace( '/parent_id/', $relationId,
                        self::MODEL_RELATION_LABEL
                    ))) . PHP_EOL;
                }
            }
            //Next Model
            $this->getOutput()->progressAdvance();
        }

        // Assemble final graph
        $graph = $graphHead . $graphModels . $graphCells . $graphRelations . $graphLabels . $graphFooter;
        $this->getOutput()->progressFinish();
        $this->info('Done.');

        // TODO - Export file instead of output
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
