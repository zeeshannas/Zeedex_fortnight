<?php
namespace GroceryCrud\Core\State;

use GroceryCrud\Core\Exceptions\Exception;
use GroceryCrud\Core\GroceryCrud as GCrud;
use GroceryCrud\Core\Render\RenderAbstract;
use GroceryCrud\Core\Model;

class AjaxSearchState extends StateAbstract {

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
            'fieldName' => !empty($_POST['field_name']) ? $_POST['field_name'] : null,
            'searchValue' => !empty($_POST['search_value']) ? $_POST['search_value'] : ''
        );
    }

    public function render()
    {
        $MINIMUM_CHARS = 2;
        $stateParameters = $this->getStateParameters();
        $fieldName = $stateParameters->fieldName;
        $searchValue = $stateParameters->searchValue;

        if (!$fieldName) {
            throw new \Exception('field_name parameter is required');
        }

        if (!empty($searchValue) && strlen($searchValue) < $MINIMUM_CHARS) {
            throw new \Exception('search_value has to be equal or greater than: ' . $MINIMUM_CHARS);
        }

        $this->setModel();

        $relations = $this->gCrud->getRelations1toMany();
        $dependedRelations = $this->gCrud->getDependedRelation();
        $dynamicRelations = $this->gCrud->getDynamicRelation1toN();
        $isDependedRelation = isset($relations[$fieldName]) && isset($dependedRelations[$fieldName]);

        if ($isDependedRelation || isset($dynamicRelations[$fieldName])) {
            $relation = $isDependedRelation ? $relations[$fieldName] : $dynamicRelations[$fieldName];
            $where = $this->getWhereFromRelationWithSearchValue($relation->titleField, $searchValue);
            if (property_exists($relation, 'where') && is_array($relation->where)) {
                $where = array_merge($where, $relation->where);
            }
            $relationalData = $this->getRelationalData(
                $relation->tableName,
                $relation->titleField,
                $where,
                $relation->orderBy,
                $fieldName
            );
        } else {
            throw new \Exception('field_name: "' . $fieldName . '" is not a dynamic searchable field');
        }

        $output = (object) [
            'total_count' => count($relationalData),
            'items' => $relationalData
        ];

        $output = $this->addcsrfToken($output);

        $render = new RenderAbstract();

        $render->output = json_encode($output);
        $render->outputAsObject = $output;
        $render->isJSONResponse = true;

        return $render;

    }

}