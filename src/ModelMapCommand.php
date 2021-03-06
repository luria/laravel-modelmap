<?php

namespace Luria\Modelmap;

use Luria\Modelmap\Model as GraphModel;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App;
use ReflectionClass;
use ReflectionMethod;

class ModelMapCommand extends Command
{
    const FILENAME_FORMAT = '.xml';
    const DEFAULT_FILENAME = 'modelmap';

    const NEWLINE = '&#xa;';
    
    const MODEL_WIDTH = '170';
    const CELL_HEIGHT = '26';
    const MODEL_HEIGHT = '107';
    const MODEL_X_GAP = '50';
    const MODEL_Y_GAP = '50';
    const MODEL_ROW_COLUMNS = '5';

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
        <mxCell id="model_id" value="model_name" style="swimlane;fontStyle=1;childLayout=stackLayout;horizontal=1;startSize=54;fillColor=#e0e0e0;horizontalStack=0;resizeParent=1;resizeParentMax=0;resizeLast=0;collapsible=1;marginBottom=0;swimlaneFillColor=#ffffff;align=center;fontSize=14;" parent="1" vertex="1">
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
        <mxCell id="relation_id" style="edgeStyle=orthogonalEdgeStyle;rounded=1;orthogonalLoop=1;jettySize=auto;html=1;entryX=0;entryY=0.5;entryDx=0;entryDy=0;endArrow=blockThin;endFill=1;sourcePortConstraint=source_constraint;targetPortConstraint=target_constraint" edge="1" parent="1" source="source_id" target="target_id">
            <mxGeometry relative="1" as="geometry"/>
        </mxCell>
    ';

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
        $database = DB::connection()->getConfig('database');
        // Graph
        $graph = '';
        // Graph Head
        $graphHead = self::DRAWIO_HEAD . PHP_EOL;
        $graphTables = '
        <!--
        -
        -
        -    Tables
        -
        -
        -->
        ';
        // Graph Models
        $graphModels = '
        <!--
        -
        -
        -    MODELS
        -
        -
        -->
        ';
        // Graph Cells
        $graphCells = '
        <!--
        -
        -
        -    CELLS
        -
        -
        -->
        ';
        // Graph ForeignKeys
        $graphForeignKeys = '
        <!--
        -
        -
        -    FOREIGN KEYS
        -
        -
        -->
        ';
        // Graph RelationsForeignKeys Labels
        $graphForeignKeysLabels = '
        <!--
        -
        -
        -    FOREIGN KEYS LABELS
        -
        -
        -->
        ';
        // Graph Relations
        $graphRelations = '
        <!--
        -
        -
        -    RELATIONS
        -
        -
        -->
        ';
        // Graph Relation Labels
        $graphRelationsLabels = '
        <!--
        -
        -
        -    RELATION LABELS
        -
        -
        -->
        ';
        // Graph Footer 
        $graphFooter = self::DRAWIO_FOOT . PHP_EOL;

        $this->info('Drawing graph...');
        $this->info('Tables');        
        
        // Database MAP
        // Tables
        $tables = collect(Schema::getAllTables($database));
        $tablesCount = $tables->count();
        
        $this->getOutput()->progressStart($tablesCount);

        $graphRow = 0;
        $graphColumn = 0;

        $maxModelHeight = 0;
        $modelHeight = 0;

        foreach($tables as $table){
            $graphTables .=
                preg_replace( '/model_id/', $table->Tables_in_laravel,
                preg_replace( '/model_name/', $table->Tables_in_laravel,
                preg_replace( '/model_width/', self::MODEL_WIDTH, 
                preg_replace( '/model_x/', (self::MODEL_WIDTH * $graphColumn + self::MODEL_X_GAP * $graphColumn) + self::MODEL_X_GAP,
                preg_replace( '/model_y/', (self::MODEL_Y_GAP * $graphRow) + $modelHeight + self::MODEL_Y_GAP,
                self::MODEL_HEADER 
            ))))) . PHP_EOL;


            // Cells
            $columns = Schema::getColumnListing($table->Tables_in_laravel);
            foreach($columns as $column){
                //dump ($table->Tables_in_laravel .'-'. $column);
                $graphCells .= 
                    preg_replace( '/parent_id/',    $table->Tables_in_laravel,
                    preg_replace( '/cell_id/',      $table->Tables_in_laravel .'-'. $column,
                    preg_replace( '/cell_name/',    $column,
                    self::MODEL_CELL 
                ))) . PHP_EOL;
            }
            
            $maxModelHeight = max( count($columns) * self::CELL_HEIGHT + self::MODEL_HEIGHT , $maxModelHeight);

            $graphColumn++;
            
            $columnsPerRow = max((int)self::MODEL_ROW_COLUMNS, floor(sqrt($tablesCount)) );

            if ( $graphColumn%(int)$columnsPerRow == 0){
                $graphRow++;
                $graphColumn = 0;
                $modelHeight += $maxModelHeight + self::MODEL_Y_GAP;
            }
            $this->getOutput()->progressAdvance();
        }

        $this->getOutput()->progressFinish();

        // Foreign Keys
        $foreignKeys = collect(DB::select('select * from information_schema.referential_constraints where constraint_schema = ? ', [$database]));
        $relationType = 'FK';
        foreach($foreignKeys as $foreignKey){
           
            $relationId = $foreignKey->CONSTRAINT_NAME;
            $relationLabelId = $foreignKey->CONSTRAINT_NAME .'-label';
            $sourceId = $foreignKey->TABLE_NAME .'-'. 
                preg_replace( '/' . $foreignKey->TABLE_NAME.'_/i', '',
                preg_replace( '/_foreign/i', '',
                $foreignKey->CONSTRAINT_NAME
            ));
            $targetId = $foreignKey->REFERENCED_TABLE_NAME .'-'. 'id';
            $sourceConstraint = 'west';
            $targetConstraint = 'east';
                        
            // Relation Text
            $graphForeignKeys .=
                preg_replace( '/relation_id/', $relationId,
                preg_replace( '/source_id/', $sourceId,
                preg_replace( '/target_id/', $targetId,
                preg_replace( '/source_constraint/', $sourceConstraint,
                preg_replace( '/target_constraint/', $targetConstraint,
                self::MODEL_RELATION
            ))))) . PHP_EOL;
            
            // Relation Label Text
            $graphForeignKeysLabels .=
                preg_replace( '/relation_label_id/', $relationLabelId,
                preg_replace( '/label_value/', $relationType,
                preg_replace( '/parent_id/', $relationId,
                self::MODEL_RELATION_LABEL
            ))) . PHP_EOL;
        }

        // Models
        $models = $this->getModelsThatShouldBeInspected();
        $this->info("Found {$models->count()} models.");
        $this->info("Inspecting model relations...");
        
        $this->getOutput()->progressStart($models->count());
        foreach ($models as $model){

            // Models for Table completion
            $modelShortName = (new ReflectionClass($model))->getShortName();
            $modelTable = (new ReflectionClass($model))->getDefaultProperties()['table'] ? : Str::plural(lcfirst($modelShortName), 2);
            //Add Model Short Name to Table.
            $graphTables = preg_replace( '/value="'.$modelTable.'"/' , 'value="'.$modelShortName . self::NEWLINE . $modelTable.'"',  $graphTables );
            

            // Relations 
            foreach($this->relationFinder->getModelRelations($model) as $relation){
                //dump($modelShortName, $modelTable,  $relation);

                $relatedModelName   = $relation->getName();
                $relatedModel       = $relation->getModel();
                $relationForeignKey = $relation->getForeignKey();
                $relationLocalKey   = $relation->getLocalKey();
                $relationType       = $relation->getType();
                
                $relationTable      = (new ReflectionClass(ucfirst($relatedModel)))->getDefaultProperties()['table'] ? : Str::plural(lcfirst($relatedModel), 2);
                
                $relationId         = $modelShortName .'-'. $relationType .'-'. $relatedModel;
                $relationLabelId    = $modelShortName .'-'. $relationType .'-'. $relatedModel .'-label';
                
                if ( !preg_match('/notifications/i', $relatedModel) ){
                    $relation = true;
                    switch( $relationType ){
                        case 'HasOne':
                        case 'MorphMany':
                            /*
                            $sourceId = $modelShortName .'-'. $relationLocalKey;
                            $targetId = ucfirst($relatedModel) .'-'. $relationForeignKey;
                            $sourceConstraint = 'west';
                            $targetConstraint = 'east';
                            break;
                            */
                        case 'BelongsTo':
                        case 'BelongsToMany':
                            $sourceId = $modelTable .'-'. $relationLocalKey;
                            $targetId = $relationTable .'-'. $relationForeignKey;
                            $sourceConstraint = 'east';
                            $targetConstraint = 'west';
                            break;
                        default:
                            $relation = false;
                            info('WARNING: Default switch case used.');
                            break;
                    }

                    if ($relation){
                        
                        // Relation Text
                        $graphRelations .=
                            preg_replace( '/relation_id/', $relationId,
                            preg_replace( '/source_id/', $sourceId,
                            preg_replace( '/target_id/', $targetId,
                            preg_replace( '/source_constraint/', $sourceConstraint,
                            preg_replace( '/target_constraint/', $targetConstraint,
                            self::MODEL_RELATION
                        ))))) . PHP_EOL;
                        
                        // Relation Label Text
                        $graphRelationsLabels .=
                            preg_replace( '/relation_label_id/', $relationLabelId,
                            preg_replace( '/label_value/', $relationType,
                            preg_replace( '/parent_id/', $relationId,
                            preg_replace( '/source_constraint/', $sourceConstraint,
                            preg_replace( '/target_constraint/', $targetConstraint,
                            self::MODEL_RELATION_LABEL
                        ))))) . PHP_EOL;
                    }
                }
                //Next Model
                $this->getOutput()->progressAdvance();
            }
        }
        $this->getOutput()->progressFinish();

        // Assemble final graph
        $graph = $graphHead . $graphTables . $graphCells . $graphForeignKeys . $graphForeignKeysLabels . $graphRelations . $graphRelationsLabels . $graphFooter;
        
        $this->info('Done.');

        // Export Modelmap
        //$this->info($graph);
        Storage::disk('local')->put('/modelmap/' . self::DEFAULT_FILENAME . self::FILENAME_FORMAT, $graph);

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
