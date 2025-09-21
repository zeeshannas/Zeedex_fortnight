<?php
namespace GroceryCrud\Core\State;

use GroceryCrud\Core\GroceryCrud as GCrud;
use GroceryCrud\Core\Render\RenderAbstract;
use GroceryCrud\Core\Exceptions\Exception as GCrudException;

class UpdateState extends StateAbstract {

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
        $data = $_POST;

        $data['data'] = $this->filterData($data['data']);

        return (object)array(
            'primaryKeyValue' => $data['pk_value'],
            'data' => $data['data']
        );
    }

    public function render()
    {
        if (!$this->gCrud->getLoadEdit()) {
            throw new \Exception('Permission denied. You are not allowed to use this operation');
        }

        $stateParameters = $this->getStateParameters();

        $this->setInitialData();
        $model = $this->gCrud->getModel();

        if ($this->gCrud->getWhere() !== null) {
            $model->setWhere($this->gCrud->getWhere());
        }

        $this->setColumns();

        $validator = $this->setValidationRules('update');

        $output = (object)array();

        if (!$validator->validate()) {
            $output->status = 'error';
            $output->errors = $validator->getErrors();
        } else {
            try {
                $model->setPrimaryKey($this->getPrimaryKeyName());
                $model->setRelations1ToN($this->getRelations1ToN());

                if (!$model->validateOne($stateParameters->primaryKeyValue)) {
                    throw new GCrudException(
                        "Either the user doesn't have access to this row, either the row doesn't exist."
                    );
                }

                $fieldTypes = $this->getFieldTypes();
                $readOnlyEditFields = $this->gCrud->getReadOnlyEditFields();

                foreach ($stateParameters->data as $field_name => $field_value) {
                    if ($field_value === '' && !empty($fieldTypes->$field_name) && $fieldTypes->$field_name->isNullable) {
                        $stateParameters->data[$field_name] = null;
                    }
                }

                $editFields = $this->getEditFields();

                $relation1to1Data = $this->getRelation1to1Data($editFields, $stateParameters->data);
                $relationNtoNData = $this->getRelationNtoNData($editFields, $stateParameters->data);
                $stateParameters->data = $this->getFilteredData($editFields, $stateParameters->data, $readOnlyEditFields);

                $uploadedBlobData = $this->uploadBlobData($fieldTypes);
                if (!empty($uploadedBlobData)) {
                    $stateParameters->data = $this->addUploadBlobData($stateParameters->data, $uploadedBlobData);
                }

                $uploadedData = $this->uploadOneData($fieldTypes);
                if (!empty($uploadedData)) {
                    $stateParameters->data = $this->addUploadOneData($stateParameters->data, $uploadedData);
                }

                $uploadedMultipleData = $this->uploadMultipleData($fieldTypes);
                if (!empty($uploadedMultipleData)) {
                    $stateParameters->data = $this->addUploadMultipleData($stateParameters->data, $uploadedMultipleData);
                }

                $uploadOneFields = $this->getUploadOneFields($fieldTypes);
                $uploadMultipleFields = $this->getUploadMultipleFields($fieldTypes);

                $uploadFields = array_merge($uploadOneFields, $uploadMultipleFields);

                $previousData = $model->getOne($stateParameters->primaryKeyValue);

                $callbackResult = $this->stateOperationWithCallbacks($stateParameters, 'Update',
                    function ($stateParameters) use ($relationNtoNData, $uploadFields, $previousData, $relation1to1Data) {
                        $model = $this->gCrud->getModel();

                        foreach ($relation1to1Data as $relationData) {
                            if (!empty($previousData[$relationData->relation->relationFieldName])) {
                                $relationId = $previousData[$relationData->relation->relationFieldName];
                                $model->update($relationId, $this->filterData($relationData->data), $relationData->relation->relatedTable);

                                // Just making sure that the relation is not changed
                                $stateParameters->data[$relationData->relation->relationFieldName] = $previousData[$relationData->relation->relationFieldName];
                            } else {
                                $relationInsertId = $model->insert($this->filterData($relationData->data), $relationData->relation->relatedTable);
                                $stateParameters->data[$relationData->relation->relationFieldName] = $relationInsertId->insertId;
                            }
                        }

                        $model->update($stateParameters->primaryKeyValue, $stateParameters->data);

                        $relationNtoNfields = $this->gCrud->getRelationNtoN();
                        foreach ($relationNtoNData as $fieldName => $relationData) {
                            $model->updateRelationManytoMany($relationNtoNfields[$fieldName], $relationData, $stateParameters->primaryKeyValue);
                        }

                        foreach ($uploadFields as $fieldName => $fieldInfo) {
                            $this->removeFilesIfNeeded($fieldInfo, $stateParameters->data[$fieldName], $previousData[$fieldName]);
                        }

                        return $stateParameters;
                });

                $output = $this->setResponseStatusAndMessage($output, $callbackResult);

            } catch (GCrudException $e) {
                $output->message = $e->getMessage();
                $output->status = 'failure';
            }
        }

        $output = $this->addcsrfToken($output);

        $render = new RenderAbstract();

        $render->output = json_encode($output);
        $render->outputAsObject = $output;
        $render->isJSONResponse = true;

        return $render;

    }

    public function showList($results)
    {
        $data = $this->_getCommonData();
        $columns = $this->gCrud->getColumns();

        if (!empty($columns)) {
            $data->columns = $columns;
        } else {
            $data->columns = array_keys((array)$results[0]);
        }

        return $this->gCrud->getLayout()->themeView('list_template.php', $data, true);
    }

    public function _getCommonData()
    {
        $data = (object)array();

        $data->subject 				= $this->gCrud->getSubject();
        $data->subject_plural 		= $this->gCrud->getSubjectPlural();

        return $data;
    }
}