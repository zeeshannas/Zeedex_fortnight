<?php
namespace GroceryCrud\Core\State;

use GroceryCrud\Core\Exceptions\Exception;
use GroceryCrud\Core\GroceryCrud as GCrud;
use GroceryCrud\Core\Render\RenderAbstract;
use GroceryCrud\Core\Model;

class DependedRelationState extends StateAbstract {

    /**
     * MainState constructor.
     * @param GCrud $gCrud
     */
    function __construct(GCrud $gCrud)
    {
        $this->gCrud = $gCrud;
    }

    public function getStateParameters()
    {
        return (object)array(
            'fieldName' => !empty($_GET['field_name']) ? $_GET['field_name'] : null,
            'searchValue' => !empty($_GET['search_value']) ? $_GET['search_value'] : ''
        );
    }

    public function render()
    {
        $stateParameters = $this->getStateParameters();
        $fieldName = $stateParameters->fieldName;
        $searchValue = $stateParameters->searchValue;
        $dependedFieldName = "";
        $resetPermittedValuesForFields = [];
        $permittedValues = [];

        if (!$fieldName) {
            throw new \Exception('field_name parameter is required');
        }

        if ($searchValue) {
            $this->setModel();

            $dependedRelations = $this->gCrud->getDependedRelation();
            $relationWithDependencies = $this->gCrud->getRelationWithDependencies();
            $relations = $this->gCrud->getRelations1toMany();

            if (array_key_exists($fieldName, $relationWithDependencies)) {
                $dependedFieldName = $relationWithDependencies[$fieldName][0];

                $deepDependencyFieldName = $dependedFieldName;
                $counter = 0;
                $maximumLoops = count($relationWithDependencies);
                while(array_key_exists($deepDependencyFieldName, $relationWithDependencies) && $counter < $maximumLoops) {
                    $resetPermittedValuesForFields = array_merge($resetPermittedValuesForFields, $relationWithDependencies[$deepDependencyFieldName]);
                    $deepDependencyFieldName = $relationWithDependencies[$deepDependencyFieldName][0];
                    $counter ++;
                }

                foreach ($relationWithDependencies[$fieldName] as $permittedValuesFieldName) {

                    $relation = $relations[$permittedValuesFieldName];

                    $permittedValues[$permittedValuesFieldName] = $this->getRelationalData(
                        $relation->tableName,
                        $relation->titleField,
                        [
                            $dependedRelations[$permittedValuesFieldName]->fieldNameRelation => $searchValue
                        ],
                        $relation->orderBy,
                        $fieldName
                    );
                }
            }
        }

        $output = (object) [
            'resetPermittedValuesForFields' => $resetPermittedValuesForFields,
            'permittedValues' => $permittedValues
        ];

        $output = $this->addcsrfToken($output);

        $render = new RenderAbstract();

        $render->output = json_encode($output);
        $render->outputAsObject = $output;
        $render->isJSONResponse = true;

        return $render;

    }

}