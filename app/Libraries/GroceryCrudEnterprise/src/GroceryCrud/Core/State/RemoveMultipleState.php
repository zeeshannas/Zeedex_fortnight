<?php
namespace GroceryCrud\Core\State;

use GroceryCrud\Core\Exceptions\Exception;
use GroceryCrud\Core\Exceptions\PermissionDeniedException;
use GroceryCrud\Core\GroceryCrud as GCrud;
use GroceryCrud\Core\Render\RenderAbstract;
use GroceryCrud\Core\Exceptions\Exception as GCrudException;

class RemoveMultipleState extends StateAbstract {

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
        if (empty($_POST['primaryKeys']) || !is_array($_POST['primaryKeys'])) {
            throw new Exception('Not valid Request');
        }

        // TODO: Validate data
        return (object)array(
            'primaryKeys' => $_POST['primaryKeys']
        );
    }

    public function initialize($stateParameters)
    {
        $model = $this->gCrud->getModel();

        if ($this->gCrud->getWhere() !== null) {
            $model->setWhere($this->gCrud->getWhere());
        }

        $model->setPrimaryKey($this->getPrimaryKeyName());
        $model->setRelations1ToN($this->getRelations1ToN());
    }

    public function render()
    {
        if (!$this->gCrud->getLoadList() || !$this->gCrud->getLoadDelete() || !$this->gCrud->getLoadDeleteMultiple()) {
            throw new PermissionDeniedException('Permission denied. You are not allowed to use this operation');
        }

        $stateParameters = $this->getStateParameters();

        $this->setInitialData();
        $model = $this->gCrud->getModel();

        $this->initialize($stateParameters);

        $output = (object)array();

        try {
            if (!$model->validateMultiple($stateParameters->primaryKeys)) {
                throw new GCrudException(
                    "Couldn't remove the rows that you have specified. 
                    Please check that you have the correct permissions to remove 
                    these rows and/or that the rows exist."
                );
            }

            $fieldTypes = $this->getFieldTypes(false);
            $uploadOneFields = $this->getUploadOneFields($fieldTypes);
            $uploadMultipleFields = $this->getUploadMultipleFields($fieldTypes);

            $uploadFields = array_merge($uploadOneFields, $uploadMultipleFields);

            $data = [];

            $config = $this->gCrud->getConfig();

            /**
             * For optimization reasons we are having the below statement.
             */
            $needRowData = $config['remove_file_on_delete'] === true && (!empty($uploadOneFields) || !empty($uploadMultipleFields));

            if ($needRowData) {
                foreach ($stateParameters->primaryKeys as $primaryKey) {
                    $data[$primaryKey] = $this->gCrud->getModel()->getOne($primaryKey);
                }
            }

            $callbackResult = $this->stateOperationWithCallbacks($stateParameters, 'DeleteMultiple',
                function ($stateParameters) use ($uploadFields, $data) {
                    $this->gCrud->getModel()->removeMultiple($stateParameters->primaryKeys);

                    foreach ($uploadFields as $fieldName => $fieldInfo) {
                        $this->removeFilesIfNeededMultiplePrimaryKeys($fieldName, $fieldInfo, $data);
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