<?php
namespace GroceryCrud\Core\State;

use GroceryCrud\Core\Exceptions\PermissionDeniedException;
use GroceryCrud\Core\GroceryCrud as GCrud;
use GroceryCrud\Core\Render\RenderAbstract;
use GroceryCrud\Core\Exceptions\Exception as GCrudException;

class RemoveOneState extends StateAbstract {

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

        return (object)array(
            'primaryKeyValue' =>  $data['primaryKeyValue']
        );
    }

    public function initialize()
    {
        $this->setInitialData();
        $model = $this->gCrud->getModel();

        if ($this->gCrud->getWhere() !== null) {
            $model->setWhere($this->gCrud->getWhere());
        }

        $model->setPrimaryKey($this->getPrimaryKeyName());
        $model->setRelations1ToN($this->getRelations1ToN());
    }

    public function render()
    {
        if (!$this->gCrud->getLoadList() || !$this->gCrud->getLoadDelete()) {
            throw new PermissionDeniedException('Permission denied. You are not allowed to use this operation');
        }

        $stateParameters = $this->getStateParameters();

        $this->setModel();
        $model = $this->gCrud->getModel();

        $this->initializeLight($model);

        $this->initialize($stateParameters);

        $output = (object)[];

        try {
            if (!$model->validateOne($stateParameters->primaryKeyValue)) {
                $operation = 'delete';
                $errorMessage = "Seems that the record that you are trying to {operation}, doesn't exist.";
                $errorMessage = str_replace('{operation}', $operation, $errorMessage);

                throw new GCrudException($errorMessage);
            }

            $fieldTypes = $this->getFieldTypes(false);

            $uploadOneFields = $this->getUploadOneFields($fieldTypes);
            $uploadMultipleFields = $this->getUploadMultipleFields($fieldTypes);

            $uploadFields = array_merge($uploadOneFields, $uploadMultipleFields);

            $previousData = $model->getOne($stateParameters->primaryKeyValue);

            $callbackResult = $this->stateOperationWithCallbacks($stateParameters, 'Delete',
                function ($stateParameters) use ($uploadFields, $previousData) {
                    $this->gCrud->getModel()->removeOne($stateParameters->primaryKeyValue);

                    foreach ($uploadFields as $fieldName => $fieldInfo) {
                        $this->removeFilesIfNeeded($fieldInfo, '', $previousData[$fieldName]);
                    }

                    return $stateParameters;
            });

            $output = $this->setResponseStatusAndMessage($output, $callbackResult);

        } catch (GCrudException $e) {
            $output->message = $e->getMessage();
            $output->status = 'failure';
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