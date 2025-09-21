<?php

namespace GroceryCrud\Core\State;

use GroceryCrud\Core\GroceryCrud as GCrud;
use GroceryCrud\Core\Helpers\ArrayHelper;
use GroceryCrud\Core\Helpers\UploadHelper;
use GroceryCrud\Core\Model;
use GroceryCrud\Core\GroceryCrud;
use GroceryCrud\Core\Error\ErrorMessageInteface;
use GroceryCrud\Core\Model\ModelFieldType;
use GroceryCrud\Core\Redirect\RedirectResponseInterface;
use GroceryCrud\Core\Error\ErrorMessage;
use GroceryCrud\Core\Exceptions\Exception as GCrudException;
use GroceryCrud\Core\Upload\Upload;
use GroceryCrud\Core\Upload\UploadedFiles;

class StateAbstract
{
    const EXTRAS_FIELD_NAME = 'grocery_crud_extras';
    const WITH_PRIMARY_KEY = 1;
    const WITH_TABLE_NAME = 1;

    const UPLOAD_FIELD_EMPTY_STRING = 'EMPTY_STRING';

    public $config;

    /**
     * @var \GroceryCrud\Core\GroceryCrud
     */
    public $gCrud;

    public $filtersAnd = [];
    public $filtersOr = [];

    public $formatData = null;

    /**
     * @var array|null
     */
    protected $fieldTypesWithPermittedData = null;

    /**
     * @var array|null
     */
    protected $fieldTypesLight = null;

    /**
     * @var int|null
     */
    public $page = null;

    /**
     * @var int|null
     */
    public $perPage = null;

    function __construct(GCrud $gCrud)
    {
        $this->gCrud = $gCrud;
        $this->config = $this->getConfigParameters();
    }

    /**
     * @overide
     * @return object
     */
    public function getStateParameters()
    {
        return (object)array();
    }

    public function getConfigParameters(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }
        $config = $this->gCrud->getConfig();

        $this->config = $config;

        return $this->config;
    }

    public function addcsrfToken($output) {
        $CsrfData = $this->getcsrfTokenData();
        if ($CsrfData !== null) {
            $output->csrfToken = $CsrfData;
        }

        return $output;
    }

    /**
     * @param int $page
     * @return $this
     */
    public function setPage($page) {
        $this->page = $page;

        return $this;
    }

    /**
     * @param int $perPage
     * @return $this
     */
    public function setPerPage($perPage) {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * @param array $data
     * @return int|string|null
     */
    public function getPage($data = []) {
        // No matter of our data, if we have set the page we will get the value from our object
        if ($this->page !== null) {
            return $this->page;
        }

        return !empty($data['page']) && is_numeric($data['page']) ? $data['page'] : null;
    }

    /**
     * @param array $data
     * @return int|string|null
     */
    public function getPerPage($data = []) {
        // No matter of our data, if we have set the perPage we will get the value from our object
        if ($this->perPage !== null) {
            return $this->perPage;
        }

        return !empty($data['per_page']) && is_numeric($data['per_page']) ? $data['per_page'] : null;
    }

    public function getTheme() {
        if ($this->gCrud->getTheme() === null) {
            $config = $this->gCrud->getConfig();

            return $config['theme'];
        }

        return strtolower($this->gCrud->getTheme());
    }

    public function getOriginalRelationTitleField($relation) {
        return is_array($relation->titleField) ? $relation->originalTitleField : $relation->titleField;
    }

    public function getcsrfTokenData() {

        if ($this->gCrud->getcsrfTokenValue() !== null) {
            $tokenName = $this->gCrud->getcsrfTokenName();

            if (!empty($tokenName)) {
                return (object) [
                    'inputName' => $tokenName,
                    'inputValue' => $this->gCrud->getcsrfTokenValue()
                ];
            }
        }

        return null;
    }

    public function filterData($data) {
        $config = $this->getConfigParameters();

        if ($config['xss_clean']) {
            foreach ($data as $data_name => $data_value) {
                if (is_string($data_value)) {
                    $data[$data_name] = strip_tags($data_value);
                }
            }
        }

        return $data;
    }

    public function removePrivateData($outputData, $formFields) {

        // If we have an error message object then no data will be rendered so no point to filter them!
        if ($outputData instanceof ErrorMessageInteface) {
            return $outputData;
        }

        // If we have not specific fields data then
        // show all the row (no filtering of data is required)
        if (empty($formFields)) {
            return $outputData;
        }

        $filteredOutputData = new \ArrayObject();

        foreach ($formFields as $fieldName) {
            if (isset($outputData[$fieldName])) {
                $filteredOutputData[$fieldName] = $outputData[$fieldName];
            }
        }

        return $filteredOutputData;
    }

    /**
     * Validate filename and see if the file exists.
     * If so, then remove the file.
     *
     * @param $filename
     * @param $path
     * @return bool
     */
    public function deleteFile($filename, $path): bool {
        // Transform filename for security issues
        $extension = UploadHelper::getExtension($filename);
        $filename = UploadHelper::transformRawFilename(UploadHelper::removeExtension($filename));

        if ($extension) {
            $filename .= '.' . $extension;
        }

        if (file_exists($path . '/' . $filename)) {
            return unlink($path . '/' . $filename);
        }

        return false;
    }

    /**
     * @param Model\ModelInterface $model
     */
    public function initializeLight($model)
    {
        if ($this->gCrud->getDatabaseSchema() !== null) {
            $model->setDatabaseSchema($this->gCrud->getDatabaseSchema());
        }

        if ($this->gCrud->getDbTableName() !== null) {
            $model->setTableName($this->gCrud->getDbTableName());
        }
    }

    public function setInitialData()
    {
        $this->setModel();

        $model = $this->gCrud->getModel();

        $this->initializeLight($model);

        $primaryKeys = $this->gCrud->getPrimaryKeys();
        if (!empty($primaryKeys)) {
               foreach ($primaryKeys as $tableName => $primaryKey) {
                   $model->setPrimaryKey($primaryKey, $tableName);
               }
        }
    }

    public function getMappedColumns() {
        return $this->gCrud->getMapColumns();
    }

    public function getMappedFields() {
        return $this->gCrud->getMapFields();
    }

    public function setColumns()
    {
        $filteredColumns = $this->removeRelationalColumns($this->getColumns(StateAbstract::WITH_PRIMARY_KEY));

        $mappedColumns = $this->getMappedColumns();

        foreach ($mappedColumns as $columnName => $mappedColumn) {
            if (in_array($columnName, $filteredColumns)) {
                $arrayKey = array_search($columnName, $filteredColumns);

                // Work-around for blob field functionality
                unset($filteredColumns[$arrayKey]);

                $filteredColumns[$columnName] = $mappedColumn;
            }
        }

        $virtualFields = $this->gCrud->getVirtualFields();

        foreach ($virtualFields as $virtualFieldName) {
            if (in_array($virtualFieldName, $filteredColumns)) {
                $arrayKey = array_search($virtualFieldName, $filteredColumns);
                unset($filteredColumns[$arrayKey]);
            }
        }

        $this->gCrud->getModel()->setColumns($filteredColumns);
    }

    public function removeRelationalColumns($columns)
    {
        $relational_fields = array_keys($this->gCrud->getRelationNtoN());

        foreach ($columns as $rowNum => $columnName) {
            if (in_array($columnName, $relational_fields)) {
                unset($columns[$rowNum]);
            }
        }

        return $columns;
    }

    /**
     * Usually we don't use "clever" functions. However here as the callback names are hardcoded for now, we
     * did create dynamic names as getters
     *
     * @param $inputStateParameters
     * @param $callbackTitle
     * @param $operationCallback
     * @return {Object}
     */
    public function stateOperationWithCallbacks($inputStateParameters, $callbackTitle, $operationCallback)
    {
        $callbackBeforeString = "getCallbackBefore" . $callbackTitle;
        $callbackBefore = $this->gCrud->$callbackBeforeString();
        $callbackOperationString = "getCallback" . $callbackTitle;
        $callbackOperation = $this->gCrud->$callbackOperationString();
        $callbackAfterString = "getCallbackAfter" . $callbackTitle;
        $callbackAfter = $this->gCrud->$callbackAfterString();

        $inputStateParameters = $this->beforeStateOperation($callbackBefore, $inputStateParameters);
        // If the callback returns false then do not continue
        if ($inputStateParameters === false || $inputStateParameters instanceof ErrorMessageInteface) {
            return $inputStateParameters;
        }

        $inputStateParameters = $this->stateOperation($callbackOperation, $inputStateParameters, $operationCallback);
        if ($inputStateParameters === false || $inputStateParameters instanceof ErrorMessageInteface) {
            return $inputStateParameters;
        }

        $inputStateParameters = $this->afterStateOperation($callbackAfter, $inputStateParameters);
        if ($inputStateParameters === false || $inputStateParameters instanceof ErrorMessageInteface) {
            return $inputStateParameters;
        }

        return $inputStateParameters;
    }

    public function getMultiselectFields() {

        $fieldTypes = $this->getFieldTypes();
        $multiselectFields = [];

        foreach ($fieldTypes as $fieldName => $fieldType) {
            if (in_array($fieldType->dataType, [
                GroceryCrud::FIELD_TYPE_MULTIPLE_SELECT_SEARCHABLE,
                GroceryCrud::FIELD_TYPE_MULTIPLE_SELECT_NATIVE
            ]) ) {
                $multiselectFields[] = $fieldName;
            }
        }

        return $multiselectFields;
    }

    public function addUploadBlobData($data, $uploadedBlobData) {
        foreach ($uploadedBlobData as $fieldName => $fieldData) {
            $data[$fieldName] = file_get_contents($fieldData['filePath']);
            $data[$fieldData['filenameField']] = $fieldData['filename'];
            unlink($fieldData['filePath']);
        }

        return $data;
    }

    public function addUploadOneData($data, $uploadData) {
        foreach ($uploadData as $fieldName => $fieldData) {
            $data[$fieldName] = $fieldData;
        }

        return $data;
    }

    public function addUploadMultipleData($data, $uploadData) {
        foreach ($uploadData as $fieldName => $fieldData) {
            $data[$fieldName] =
                !empty($data[$fieldName])
                    ? implode(",", array_merge([$data[$fieldName]], $fieldData))
                    : implode(",", $fieldData);
        }

        return $data;
    }

    /**
     * @return array|string[]
     */
    public function getGlobalAllowedFileTypes():array {
        $config = $this->gCrud->getConfig();

        return $config['upload_allowed_file_types'];
    }

    /**
     * @param $fieldTypes
     * @return array
     * @throws GCrudException
     * @throws \Exception
     */
    public function uploadBlobData($fieldTypes): array
    {
        $blobFields = [];
        $uploadData = [];

        foreach($fieldTypes as $fieldName => $fieldType) {
            if ($fieldType->dataType === GroceryCrud::FIELD_TYPE_BLOB) {
                $blobFields[$fieldName] = $fieldType;
            }
        }

        $globalAllowedFileTypes = $this->getGlobalAllowedFileTypes();

        foreach ($blobFields as $fieldName => $fieldType) {
            if (
                array_key_exists('data', $_FILES) &&
                array_key_exists('name', $_FILES['data']) &&
                array_key_exists($fieldName . GroceryCrud::PREFIX_FILE_UPLOAD, $_FILES['data']['name'])
            ) {
                // Upload file
                $filenameField = $fieldType->options->filenameField;
                $uploadFieldName = $fieldName . GroceryCrud::PREFIX_FILE_UPLOAD;
                $uploadPath = $fieldType->options->temporaryUploadDirectory;
                $maxUploadSize = $fieldType->options->maxUploadSize;

                $minUploadSize = array_key_exists('minUploadSize', $fieldType->options->extraOptions)
                    ? $fieldType->options->extraOptions['minUploadSize']:
                    Upload::DEFAULT_MIN_UPLOAD_SIZE;

                $allowedFileTypes = array_key_exists('allowedFileTypes', $fieldType->options->extraOptions)
                    ? $fieldType->options->extraOptions['allowedFileTypes']:
                    $globalAllowedFileTypes;

                $result = $this->upload($uploadFieldName, $uploadPath, $maxUploadSize, $minUploadSize, $allowedFileTypes);

                if ($this->hasErrorResponse($result)) {
                    throw new GCrudException($result->getMessage());
                } else if ($result instanceof UploadedFiles) {
                    $uploadData[$fieldName] = [
                        'filename' => $result->getFirstUploadedFile(),
                        'filenameField' => $filenameField,
                        'filePath' => $uploadPath . '/' . $result->getFirstUploadedFile()
                    ];
                }
            }
        }
        return $uploadData;
    }


    /**
     * @param $fieldInfo
     * @param $dataRaw - empty (e.g. null) if we are deleting the file
     * @param $previousData
     * @return void
     */
    public function removeFilesIfNeeded($fieldInfo, $dataRaw, $previousData) {
        $config = $this->gCrud->getConfig();

        if ($config['remove_file_on_delete'] === true) {
            if (!empty($previousData)) {
                // Ensuring that the file system accurately reflects any changes made to the list of file names in the
                // field. The below code will delete any files that are no longer referenced in the database.
                $data = empty($dataRaw) ? '' : $dataRaw;
                $filesToDelete = array_diff(explode(',', $previousData), explode(',', $data));
                foreach ($filesToDelete as $fileToDelete) {
                    $this->deleteFile($fileToDelete, $fieldInfo->options->uploadPath);
                }
            }
        }
    }

    public function removeFilesIfNeededMultiplePrimaryKeys($fieldName, $fieldInfo, $data) {
        $config = $this->gCrud->getConfig();

        if ($config['remove_file_on_delete'] === true) {
            foreach ($data as $row) {
                if (!empty($row[$fieldName])) {
                    $filesToDelete = explode(',', $row[$fieldName]);

                    foreach ($filesToDelete as $fileToDelete) {
                        $this->deleteFile($fileToDelete, $fieldInfo->options->uploadPath);
                    }
                }
            }
        }
    }

    public function getUploadOneFields($fieldTypes, $filterByFieldName = null): array
    {
        $uploadFields = [];

        foreach($fieldTypes as $fieldName => $fieldType) {
            if ($fieldType->dataType === GroceryCrud::FIELD_TYPE_UPLOAD) {
                $uploadFields[$fieldName] = $fieldType;
            }
        }

        // If we have a specific field name to filter by.
        // Useful for quick edit updates where we have only one field to update
        // If the field does not exist, then we act as if no upload field exists
        if (!empty($filterByFieldName)) {
            $uploadFields = array_filter($uploadFields, function($fieldType, $fieldName) use ($filterByFieldName) {
                return $fieldName === $filterByFieldName;
            }, ARRAY_FILTER_USE_BOTH);
        }

        return $uploadFields;
    }

    public function getUploadMultipleFields($fieldTypes, $filterByFieldName = null): array
    {
        $uploadFields = [];

        foreach($fieldTypes as $fieldName => $fieldType) {
            if ($fieldType->dataType === GroceryCrud::FIELD_TYPE_UPLOAD_MULTIPLE) {
                $uploadFields[$fieldName] = $fieldType;
            }
        }

        // If we have a specific field name to filter by.
        // Useful for quick edit updates where we have only one field to update
        // If the field does not exist, then we act as if no upload field exists
        if (!empty($filterByFieldName)) {
            $uploadFields = array_filter($uploadFields, function($fieldType, $fieldName) use ($filterByFieldName) {
                return $fieldName === $filterByFieldName;
            }, ARRAY_FILTER_USE_BOTH);
        }

        return $uploadFields;
    }

    public function uploadOneData($fieldTypes, $filterByFieldName = null): array
    {
        $uploadData = [];

        $uploadFields = $this->getUploadOneFields($fieldTypes, $filterByFieldName);

        $globalAllowedFileTypes = $this->getGlobalAllowedFileTypes();

        foreach ($uploadFields as $fieldName => $fieldType) {
            if (
                array_key_exists('data', $_FILES) &&
                array_key_exists('name', $_FILES['data']) &&
                array_key_exists($fieldName . GroceryCrud::PREFIX_FILE_UPLOAD, $_FILES['data']['name'])
            ) {
                $uploadFieldName = $fieldName . GroceryCrud::PREFIX_FILE_UPLOAD;
                $uploadPath = $fieldType->options->uploadPath;

                $maxUploadSize = array_key_exists('maxUploadSize', $fieldType->options->extraOptions)
                    ? $fieldType->options->extraOptions['maxUploadSize']:
                    Upload::DEFAULT_MAX_UPLOAD_SIZE;

                $minUploadSize = array_key_exists('minUploadSize', $fieldType->options->extraOptions)
                    ? $fieldType->options->extraOptions['minUploadSize']:
                    Upload::DEFAULT_MIN_UPLOAD_SIZE;

                $allowedFileTypes = array_key_exists('allowedFileTypes', $fieldType->options->extraOptions)
                    ? $fieldType->options->extraOptions['allowedFileTypes']:
                    $globalAllowedFileTypes;

                $stateParameters = (object)[
                    'fieldName' => $fieldName,
                    'uploadFieldName' => $uploadFieldName,
                    'uploadPath' => $uploadPath,
                    'maxUploadSize' => $maxUploadSize,
                    'minUploadSize' => $minUploadSize,
                    'allowedFileTypes' => $allowedFileTypes
                ];

                // Enabling callbacks. Replacing upload or before/after the upload
                $uploadOutput = $this->stateOperationWithCallbacks($stateParameters, 'Upload', function ($stateParameters) {
                    // Real upload of the file
                    $uploadResult = $this->upload(
                        $stateParameters->uploadFieldName,
                        $stateParameters->uploadPath,
                        $stateParameters->maxUploadSize,
                        $stateParameters->minUploadSize,
                        $stateParameters->allowedFileTypes
                    );

                    if ($uploadResult instanceof UploadedFiles) {
                        $uploadResult = $uploadResult->getFirstUploadedFile();
                    }

                    return (object)[
                        'uploadResult' => $uploadResult,
                        'stateParameters' => $stateParameters
                    ];

                });

                $output = is_object($uploadOutput) && property_exists($uploadOutput, 'uploadResult')
                    ? $uploadOutput->uploadResult
                    : $uploadOutput;

                if ($this->hasErrorResponse($output)) {
                    if ($output instanceof ErrorMessageInteface) {
                        throw new GCrudException($output->getMessage());
                    } else {
                        throw new GCrudException("Unknown error");
                    }
                } else if (is_string($output)) {
                    $uploadData[$fieldName] = $output;
                }
            }
        }
        return $uploadData;
    }

    /**
     * $maxUploadSize: Ensure file is no larger than maxUploadSize (use "B", "K", M", or "G")
     *
     * @param string $fieldName
     * @param string $uploadPath
     * @param string $maxUploadSize
     * @param string $minUploadSize
     * @param array $allowedFileTypes
     * @return UploadedFiles|ErrorMessage|null
     */
    public function upload(string $fieldName, string $uploadPath, string $maxUploadSize, string $minUploadSize, array $allowedFileTypes)
    {
        $uploader = new Upload($fieldName, $uploadPath);

        $uploader->setValidationAllowedExtensions($allowedFileTypes);
        $uploader->setValidationMaxUploadSize($maxUploadSize);
        $uploader->setValidationMinUploadSize($minUploadSize);

        if ($uploader->validate() === true) {
            $uploader->upload();
        } else {
            $errors = "- " . implode("\n -", $uploader->getValidationErrors());
            return (new ErrorMessage())->setMessage("There were some validation errors with the upload:\n" . $errors);
        }

        return $uploader->getUploadedFiles();
    }


    public function uploadMultipleData($fieldTypes, $filterByFieldName = null): array
    {
        $uploadData = [];

        $uploadFields = $this->getUploadMultipleFields($fieldTypes, $filterByFieldName);

        $globalAllowedFileTypes = $this->getGlobalAllowedFileTypes();

        foreach ($uploadFields as $fieldName => $fieldType) {
            if (
                array_key_exists('data', $_FILES) &&
                array_key_exists('name', $_FILES['data']) &&
                array_key_exists($fieldName . GroceryCrud::PREFIX_FILE_UPLOAD, $_FILES['data']['name'])
            ) {
                $uploadFieldName = $fieldName . GroceryCrud::PREFIX_FILE_UPLOAD;
                $uploadPath = $fieldType->options->uploadPath;

                $maxUploadSize = array_key_exists('maxUploadSize', $fieldType->options->extraOptions)
                    ? $fieldType->options->extraOptions['maxUploadSize']:
                    Upload::DEFAULT_MAX_UPLOAD_SIZE;

                $minUploadSize = array_key_exists('minUploadSize', $fieldType->options->extraOptions)
                    ? $fieldType->options->extraOptions['minUploadSize']:
                    Upload::DEFAULT_MIN_UPLOAD_SIZE;

                $allowedFileTypes = array_key_exists('allowedFileTypes', $fieldType->options->extraOptions)
                    ? $fieldType->options->extraOptions['allowedFileTypes']:
                    $globalAllowedFileTypes;

                $stateParameters = (object)[
                    'fieldName' => $fieldName,
                    'uploadFieldName' => $uploadFieldName,
                    'uploadPath' => $uploadPath,
                    'maxUploadSize' => $maxUploadSize,
                    'minUploadSize' => $minUploadSize,
                    'allowedFileTypes' => $allowedFileTypes
                ];

                // Enabling callbacks. Replacing upload or before/after the upload
                $uploadOutput = $this->stateOperationWithCallbacks($stateParameters, 'Upload', function ($stateParameters) {
                    // Real upload of the file
                    $uploadResult = $this->upload(
                        $stateParameters->uploadFieldName,
                        $stateParameters->uploadPath,
                        $stateParameters->maxUploadSize,
                        $stateParameters->minUploadSize,
                        $stateParameters->allowedFileTypes
                    );

                    if ($uploadResult instanceof UploadedFiles) {
                        $uploadResult = $uploadResult->getUploadedFiles();
                    }

                    return (object)[
                        'uploadResult' => $uploadResult,
                        'stateParameters' => $stateParameters
                    ];
                });

                $output = is_object($uploadOutput) && property_exists($uploadOutput, 'uploadResult')
                    ? $uploadOutput->uploadResult
                    : $uploadOutput;

                if ($this->hasErrorResponse($output)) {
                    if ($output instanceof ErrorMessageInteface) {
                        throw new GCrudException($output->getMessage());
                    } else {
                        throw new GCrudException("Unknown error");
                    }
                } else if (is_array($output)) {
                    $uploadData[$fieldName] = $output;
                }
            }
        }

        return $uploadData;
    }

    public function mapFields($outputData) {
        $mappedFields = $this->getMappedFields();

        if (!empty($mappedFields) && !empty($outputData)) {
            foreach ($outputData as $fieldName => $rowValue) {
                if (array_key_exists($fieldName, $mappedFields) && isset($outputData[$mappedFields[$fieldName]])) {
                    $outputData[$fieldName] = $outputData[$mappedFields[$fieldName]];
                }
            }
        }

        return $outputData;
    }

    public function setRelation1to1Fields($outputData) {
        $model = $this->gCrud->getModel();
        $relations = $this->getRelations1To1();

        foreach ($relations as $relation) {
            $relationFieldName = $relation->relationFieldName;
            $relatedTable = $relation->relatedTable;
            $relatedTableFields = $relation->relatedTableFields;

            if (!empty($outputData[$relationFieldName])) {
                $data = $model->getOne($outputData[$relationFieldName], $relatedTable);
            } else {
                $data = [];
            }

            foreach ($relatedTableFields as $relatedTableField) {
                $outputData[$relatedTableField] = $data[$relatedTableField] ?? "";
            }

        }

        return $outputData;
    }

    public function enhanceInsertFormOutputData($outputData) {
        $relations = $this->gCrud->getRelations1toMany();

        $dependedRelations = $this->gCrud->getDependedRelation();

        foreach ($dependedRelations as $dependedRelation) {
            $fieldName = $dependedRelation->fieldName;
            $relation = $relations[$fieldName];
            $fieldNameRelation = $dependedRelation->fieldNameRelation;
            $dependencyFromField = $dependedRelation->dependencyFromField;

            $dependencyRelationValue = null;

            if (!empty($outputData[$dependencyFromField])) {
                $dependencyRelationValue = is_object($outputData[$dependencyFromField])
                    ? $outputData[$dependencyFromField]->value
                    : $outputData[$dependencyFromField];
            }

            $permittedValues = !empty($dependencyRelationValue) ?
                $this->getRelationalData(
                    $relation->tableName,
                    $relation->originalTitleField,
                    [
                        $fieldNameRelation => $dependencyRelationValue
                    ],
                    $relation->orderBy,
                    $fieldName
                ) : [];

            $outputData[$fieldName] = (object)[
                'value' => $outputData[$fieldName],
                'permittedValues' => $permittedValues
            ];
        }

        return $outputData;
    }

    public function enhanceFormOutputData($outputData, $primaryKeyValue) {
        $model = $this->gCrud->getModel();
        $relationNtoN = $this->gCrud->getRelationNtoN();
        $multiselectFields = $this->getMultiselectFields();
        $relations = $this->gCrud->getRelations1toMany();

        $dependedRelations = $this->gCrud->getDependedRelation();
        $dynamicRelations = $this->gCrud->getDynamicRelation1toN();

        foreach ($relationNtoN as $field) {
            $outputData[$field->fieldName] = $model->getRelationNtoNData($field, $primaryKeyValue);
        }

        foreach ($multiselectFields as $fieldName) {
            if (isset($outputData[$fieldName])) {
                $outputData[$fieldName] = !empty($outputData[$fieldName]) ? explode(",", $outputData[$fieldName]) : null;
            }
        }

        foreach ($dependedRelations as $dependedRelation) {
            $fieldName = $dependedRelation->fieldName;
            $relation = $relations[$fieldName];
            $fieldNameRelation = $dependedRelation->fieldNameRelation;
            $dependencyFromField = $dependedRelation->dependencyFromField;

            $outputData[$fieldName] = (object)[
                'value' => (string) $outputData[$fieldName],
                'permittedValues' => $this->getRelationalData(
                    $relation->tableName,
                    $relation->originalTitleField,
                    [
                        $fieldNameRelation =>
                            is_object($outputData[$dependencyFromField])
                                ? $outputData[$dependencyFromField]->value
                                : $outputData[$dependencyFromField]
                    ],
                    $relation->orderBy,
                    $fieldName
                )
            ];
        }

        foreach ($dynamicRelations as $dynamicRelation) {
            $fieldName = $dynamicRelation->fieldName;
            $relationPrimaryKeyField = $this->getPrimaryKeyName($dynamicRelation->tableName);

            $relationalData = $this->getRelationalData(
                $dynamicRelation->tableName,
                $dynamicRelation->originalTitleField,
                [
                    $relationPrimaryKeyField => $outputData[$fieldName]
                ],
                $dynamicRelation->orderBy,
                $fieldName
            );

            $valueLabel = count($relationalData) > 0 ? $relationalData[0]->title : "";

            $outputData[$fieldName] = (object)[
                'value' => (string) $outputData[$fieldName],
                'label' => $valueLabel,
            ];
        }

        foreach ($outputData as $fieldName => $fieldValue) {
            if (is_numeric($fieldValue)) {
                $outputData[$fieldName] = (string)$fieldValue;
            }
        }

        return $outputData;
    }

    public function setResponseStatusAndMessage($output, $callbackResult) {

        if ($callbackResult instanceof ErrorMessageInteface) {
            $output->message = $callbackResult->getMessage();
            $output->status = 'failure';
        } else if ($callbackResult instanceof RedirectResponseInterface) {
            $output->url = $callbackResult->getUrl();
            $output->status  = 'redirect';
        } else {
            $output->message = $callbackResult === false ? 'Unknown error' : 'Success';
            $output->status = $callbackResult === false ? 'failure' : 'success';
        }

        return $output;
    }

    public function hasErrorResponse($response) {
        return $response === false || $response instanceof ErrorMessageInteface;
    }

    public function stripTags($results)
    {
        $callbackColumns = $this->gCrud->getCallbackColumns();

        foreach ($results as &$result) {
            foreach ($result as $columnName => &$columnValue) {
                if (is_array($columnValue) || is_object($columnValue) || isset($callbackColumns[$columnName])) {
                    continue;
                }

                $columnValue = trim(str_replace(["\n","\r", "="], '', strip_tags($columnValue)));
            }
        }

        return $results;
    }

    public function getColumnDateFields($includePermittedValues = true) {
        $fieldTypes = $this->getFieldTypes($includePermittedValues);
        $dateTypeColumns = [];

        foreach($fieldTypes as $fieldName => $field) {
            if ($field->dataType == 'date') {
                $dateTypeColumns[] = $fieldName;
            }
        }

        return $dateTypeColumns;
    }

    public function getColumnDatetimeFields($includePermittedValues = true) {
        $fieldTypes = $this->getFieldTypes($includePermittedValues);
        $dateTypeColumns = [];

        foreach($fieldTypes as $fieldName => $field) {
            if (in_array($field->dataType, ['datetime', 'timestamp'])) {
                $dateTypeColumns[] = $fieldName;
            }
        }

        return $dateTypeColumns;
    }

    /**
     * @return array|null
     */
    public function getFieldNamesWithoutCharacterLimiter() {
        $fieldTypes = $this->getFieldTypes();
        $fieldNamesOutput = [];

        $noCharacterLimiterDataTypes = (object)[
            'boolean'=> true,
            'color' => true,
            'date' => true,
            'datetime' => true,
            'depended_relational' => true,
            'dropdown' => true,
            'dropdown_search' => true,
            'enum' => true,
            'enum_searchable' => true,
            'float' => true,
            'hidden' => true,
            'int' => true,
            'invisible' => true,
            'multiselect_native' => true,
            'multiselect_searchable' => true,
            'native_date' => true,
            'native_datetime' => true,
            'native_time' => true,
            'numeric' => true,
            'relational' => true,
            'relational_native' => true,
            'time' => true,
            'timestamp' => true,
            'upload' => true,
            'upload-multiple' => true,
        ];

        foreach ($fieldTypes as $fieldName => $fieldType) {
            if (isset($noCharacterLimiterDataTypes->{$fieldType->dataType})) {
                $fieldNamesOutput[$fieldName] = true;
            }
        }

        if (empty($fieldNamesOutput)) {
            return null;
        }

        return $fieldNamesOutput;
    }

    public function enhanceColumnResults($results)
    {
        $skipCharacterLimiterFields = $this->getFieldNamesWithoutCharacterLimiter();

        $primaryKeyName = $this->getPrimaryKeyName();

        $inlineEditFields = $this->gCrud->getInlineEditFields();
        $hasInlineEditFields = !empty($inlineEditFields);

        $callbackColumns = $this->gCrud->getCallbackColumns();
        $actionButtons = $this->gCrud->getActionButtons();

        $config = $this->getConfigParameters();

        $characterLimiter = $config['column_character_limiter'];

        $dynamicRelations = array_merge($this->gCrud->getDependedRelation(), $this->gCrud->getDynamicRelation1toN());
        $relations = array_merge(
            $this->gCrud->getRelations1toMany(),
            $this->gCrud->getDynamicRelation1toN()
        );
        $dynamicRelationIds = [];
        $dynamicRelationData = [];

        foreach($dynamicRelations as $dynamicFieldName => $row) {
            $dynamicRelationIds[$dynamicFieldName] = [];
        }

        foreach ($results as &$result) {
            // In case the original value is truncated, we are going to add the original value in the response
            $rowInlineFields = [];

            foreach ($result as $columnName => &$columnValue) {
                $columnValue = is_numeric($columnValue) ? (string)$columnValue : $columnValue;
                $originalColumnValue = $columnValue;

                if (isset($callbackColumns[$columnName])) {
                    $columnValue = $callbackColumns[$columnName]($columnValue, $result);
                } else if (!is_array($columnValue)) {
                    $columnValue = is_string($columnValue) ? strip_tags($columnValue) : "";

                    if (
                        (
                            // As this is a heavy process, for performance reasons we are doing multiple checks
                            $skipCharacterLimiterFields === null ||
                            !isset($skipCharacterLimiterFields[$columnName])
                        ) &&
                        $characterLimiter > 0 &&
                        (mb_strlen($columnValue, 'UTF-8') > $characterLimiter)
                    ) {
                        if ($hasInlineEditFields && in_array($columnName, $inlineEditFields)) {
                            $rowInlineFields[$columnName] = $originalColumnValue;
                        }
                        $columnValue = mb_substr($columnValue, 0 , $characterLimiter - 1, 'UTF-8') . '...';
                    }
                    if (array_key_exists($columnName, $dynamicRelations) && $columnValue) {
                        $dynamicRelationIds[$columnName][] = $columnValue;
                    }
                }
            }

            $result[StateAbstract::EXTRAS_FIELD_NAME] = (object) array(
                'primaryKeyValue' => $result[$primaryKeyName]
            );

            if ($hasInlineEditFields) {
                $result[StateAbstract::EXTRAS_FIELD_NAME]->inlineFieldsData = (object) $rowInlineFields;
            }

            foreach ($actionButtons as $actionButton) {
                if (!isset($result[StateAbstract::EXTRAS_FIELD_NAME]->actionButtons)) {
                    $result[StateAbstract::EXTRAS_FIELD_NAME]->actionButtons = [];
                }
                $callback = $actionButton->urlCallback;

                $result[StateAbstract::EXTRAS_FIELD_NAME]->actionButtons[] = (object)array(
                    'label' => $actionButton->label,
                    'iconName' => $actionButton->iconName,
                    'url' => $callback($result),
                    'newTab' => $actionButton->newTab
                );
            }
        }

        foreach ($dynamicRelationIds as $fieldName => $relationIds) {
            $dynamicRelationIds[$fieldName] = array_unique($relationIds);

            $relation = $relations[$fieldName];
            $dynamicRelationData[$fieldName] = $this->getRelationDataIds($this->getRelationalData(
                $relation->tableName,
                $this->getOriginalRelationTitleField($relation),
                [
                    'IN_ARRAY' => [
                        $relation->relationPrimaryKey => $dynamicRelationIds[$fieldName]
                    ]
                ],
                null,
                $fieldName
            ));
        }

        if (!empty($dynamicRelationIds)) {
            foreach ($results as &$row) {
                foreach ($dynamicRelationIds as $fieldName => $relationIds) {
                    if (isset($row[$fieldName]) && array_key_exists($row[$fieldName], $dynamicRelationData[$fieldName])) {
                        $row[$fieldName] = $dynamicRelationData[$fieldName][$row[$fieldName]];
                    }
                }
            }
        }

        return $results;
    }

    public function getWhereFromRelationWithSearchValue($titleField, $searchValue): array
    {

        $dbDriverName = $this->gCrud->getModel()->getDriverName();
        $LIKE_OPERATOR = $dbDriverName === 'Postgresql' ? 'ILIKE' : 'LIKE';

        $hasMultipleFields = $this->hasMultipleFields($titleField);

        if ($hasMultipleFields) {

            $fields = $this->getFieldsArray($titleField);

            //@TODO: Move this section to the Model
            if ($dbDriverName === 'Mysql') {

                $concatString = "CONCAT('" . str_replace(['{','}'], ["', ", ", '"] , $titleField) . "')\n";
                $concatString = str_replace(["CONCAT('', ", ", '')"], ["CONCAT(", ")"], $concatString);

                $finalWhereQuery = $concatString . ' ' . $LIKE_OPERATOR . ' ?';

                return [
                    $finalWhereQuery => '%' . $searchValue . '%'
                ];
            } else {
                $finalStringArray = [];
                foreach ($fields as $field) {
                    $finalStringArray[] = $field . ' ' . $LIKE_OPERATOR . ' ?';
                }

                $finalWhereQuery = '(' . implode(' OR ', $finalStringArray) . ')';
                $finalWhereValue = [];

                $arrayLength = count($finalStringArray);
                for ($i = 1; $i <= $arrayLength; $i++) {
                    $finalWhereValue[] =  '%' . $searchValue . '%';
                }

                return [
                    $finalWhereQuery => $finalWhereValue
                ];
            }

        } else {
            return [
                $titleField . ' ' . $LIKE_OPERATOR . ' ?' => '%' . $searchValue . '%'
            ];
        }
    }

    public function getRelationDataIds($data) {
        $finalData = [];
        foreach ($data as $row) {
            $finalData[$row->id] = $row->title;
        }

        return $finalData;
    }

    /**
     * @param array $results
     * @return array
     */
    public function filterByColumns($results)
    {
        $columnNames = $this->getColumns();
        $finalResults = [];

        foreach ($results as $row) {
            $tmpRow = [];
            foreach ($row as $fieldName => $fieldValue) {
                if (in_array($fieldName, $columnNames)) {
                    $tmpRow[$fieldName] = $fieldValue;
                }
            }
            $finalResults[] = $tmpRow;
        }

        return $finalResults;

    }

    public function getColumns($extra = null) {
        $columns = $this->gCrud->getColumns();
        $unsetColumns = $this->gCrud->getUnsetColumns();

        if (empty($columns)) {
            $columns = $this->getColumnNames();
        }

        foreach ($unsetColumns as $columnName) {
            $columns = ArrayHelper::array_reject($columns, function ($value) use ($columnName) {
                return $value === $columnName;
            });
        }

        if ($extra === StateAbstract::WITH_PRIMARY_KEY) {
            $primaryKey = $this->getPrimaryKeyName();
            if (!in_array($primaryKey, $columns)) {
                array_unshift($columns, $primaryKey);
            }
        }

        return $columns;
    }

    public function getRelations1To1()
    {
        $relations1To1 = $this->gCrud->getRelation1to1();

        foreach ($relations1To1 as &$relation) {
            $relation->relatedPrimaryField = $this->getPrimaryKeyName($relation->relatedTable);
        }

        return $relations1To1;
    }

    public function getRelations1ToN()
    {
        $relations1ToN = $this->gCrud->getDbRelations1ToN();
        $dynamicRelations1ToN = $this->gCrud->getDynamicRelation1toN();

        foreach ($relations1ToN as &$relation) {
            $relation->relationPrimaryKey = $this->getPrimaryKeyName($relation->tableName);
            $relation->dynamicRelation = false;
        }

        foreach ($dynamicRelations1ToN as &$dynamicRelation) {
            $dynamicRelation->relationPrimaryKey = $this->getPrimaryKeyName($dynamicRelation->tableName);
            $dynamicRelation->dynamicRelation = true;
        }

        return array_merge($relations1ToN, $dynamicRelations1ToN);
    }

    public function getRelationsNToN()
    {
        $relationsNToN = $this->gCrud->getDbRelationsNToN();

        foreach ($relationsNToN as &$relation) {
            $relation->referrerPrimaryKeyField = $this->getPrimaryKeyName($relation->referrerTable);
        }

        return $relationsNToN;
    }

    public function beforeStateOperation($stateCallback, $stateParameters) {
        if ($stateCallback === null) {
            return $stateParameters;
        }
        return $stateCallback($stateParameters);
    }

    public function stateOperation($stateCallback, $stateParameters, $operationCallback) {
        if ($stateCallback === null) {
            return $operationCallback($stateParameters);
        }

        return $stateCallback($stateParameters);
    }

    public function afterStateOperation($stateCallback, $stateParameters) {
        if ($stateCallback === null) {
            return $stateParameters;
        }
        return $stateCallback($stateParameters);
    }

    public function getColumnNames() {
        $model = $this->gCrud->getModel();

        $columnNames = $model->getColumnNames();

        foreach ($this->gCrud->getRelationNtoN() as $fieldName => $fieldInfo) {
            $columnNames[] = $fieldName;
        }

        return $columnNames;

    }

    public function setFilters($searchArray)
    {
        foreach ($searchArray as $fieldName => $fieldValue) {
            $this->filtersAnd[$fieldName] = $fieldValue;
        }
    }

    public function getPrimaryKeyName($dbTableNameRaw = null)
    {
        $dbTableName = $dbTableNameRaw !== null ? $dbTableNameRaw : $this->gCrud->getDbTableName();

        return $this->gCrud->getModel()->getPrimaryKeyField($dbTableName);
    }

    public function removePrimaryKeyFromList($fieldList)
    {
        if(($primaryKeyIndex = array_search($this->getPrimaryKeyName(), $fieldList)) !== false) {
            array_splice($fieldList, $primaryKeyIndex, 1);
        }

        return $fieldList;
    }

    /**
     * @param string $stringWithFields
     * @return array
     */
    public function getFieldsArray($stringWithFields) {

        preg_match_all("{([^{}]+)}", $stringWithFields, $matches);

        $fields = [];
        foreach ($matches[0] as $match) {
            if (strstr($stringWithFields, '{' . $match . '}')) {
                $fields[] = $match;
            }
        }

        return $fields;
    }

    /**
     * @param string $stringWithFields
     * @return array
     */
    public function getMatchesAsArray($stringWithFields) {

        preg_match_all("{([^{}]+)}", $stringWithFields, $matches);

        return $matches[0];
    }

    public function transformDataWithMultipleFields($data, $stringWithFields) {
        $matches = $this->getMatchesAsArray($stringWithFields);

        $finalData = [];

        foreach ($data as $row) {
            $tmp = (object)[];
            $tmp->id = $row->id;
            $tmp->title = '';

            foreach ($matches as $match) {
                if (array_key_exists($match, $row->title)) {
                    $tmp->title .= $row->title[$match];
                } else {
                    $tmp->title .= $match;
                }
            }

            $finalData[] = $tmp;
        }

        return $finalData;
    }

    public function transformRelationalData($data) {
        $finalData = [];
        foreach ($data as $row) {
            $finalData[$row->id] = $row->title;
        }

        return $finalData;
    }

    public function hasMultipleFields($titleField) {
        return strstr($titleField, "{");
    }

    public function getRelationalData($tableName, $titleField , $where, $orderBy = null, $relationFieldName = null) {
        $model = $this->gCrud->getModel();

        // In case we have already cached the primary key for the relational data
        $model->setPrimaryKey($this->getPrimaryKeyName($tableName), $tableName);

        $hasMultipleFields = $this->hasMultipleFields($titleField);

        $columns = $hasMultipleFields
            ? $this->getFieldsArray($titleField)
            : $titleField;

        if ($orderBy !== null) {
            $orderBy = $orderBy;
        } else {
            $orderBy = is_array($columns) ? $columns[0] : $columns;
        }

        $data = $model->getRelationData(
            $tableName,
            $columns,
            $where,
            $orderBy,
            $relationFieldName
        );

        if ($hasMultipleFields) {
            $data = $this->transformDataWithMultipleFields($data, $titleField);
        }

        return $data;
    }

    protected function _getDependencies($fieldName, $dependedRelations) {
        $dependencies = [];

        if (array_key_exists($fieldName, $dependedRelations)) {
            $dependencies[] = $dependedRelations[$fieldName]->dependencyFromField;
        }

        for ($i = 1; $i <= count($dependedRelations); $i++) {
            $dependencyField = $dependedRelations[$fieldName]->dependencyFromField;

            if (array_key_exists($dependencyField , $dependedRelations)) {
                $dependencies[] = $dependedRelations[$dependencyField]->dependencyFromField;
                $fieldName = $dependencyField;
            } else {
                break;
            }
        }

        return $dependencies;
    }

    public function getDependedFrom($fieldName, $dependedRelations) {
        return
            array_key_exists($fieldName, $dependedRelations)
                ? $this->_getDependencies($fieldName, $dependedRelations)
                : [];
    }

    /**
     * @param bool $includePermittedValues
     * @return array|Model\ModelFieldType[]
     */
    public function getFieldTypes($includePermittedValues = true)
    {
        // Return the cached version of fieldTypes if we have it
        if ($includePermittedValues && $this->fieldTypesWithPermittedData !== null) {
            return $this->fieldTypesWithPermittedData;
        }

        // Return the cached version of fieldTypes if we have it
        if (!$includePermittedValues && $this->fieldTypesLight !== null) {
            return $this->fieldTypesLight;
        }

        $model = $this->gCrud->getModel();

        $dbTableName = $this->gCrud->getDbTableName();

        $fieldTypes = $model->getFieldTypes($dbTableName);

        $relations = $this->gCrud->getRelations1toMany();
        $dynamicRelations = $this->gCrud->getDynamicRelation1toN();
        $dependedRelations = $this->gCrud->getDependedRelation();

        $relation1to1 = $this->gCrud->getRelation1to1();

        foreach ($relation1to1 as $relation) {
            $relatedTableFieldTypes = $model->getFieldTypes($relation->relatedTable);
            $relatedTableFields = $relation->relatedTableFields;

            foreach($relatedTableFields as $relatedTableFieldName) {
                if (isset($relatedTableFieldTypes[$relatedTableFieldName])) {
                    $fieldTypes[$relatedTableFieldName] = $relatedTableFieldTypes[$relatedTableFieldName];
                }

            }
        }

        $virtualFields = $this->gCrud->getVirtualFields();

        foreach ($virtualFields as $fieldName) {
            // Initial default values for the virtual fields. Keep in mind that those will need to be replaced later
            // anyway with callbacks. We are adding this extra code so it won't break the frontend code
            // since it is relying on the fieldTypes
            $fieldTypes[$fieldName] = new ModelFieldType();
        }

        foreach ($fieldTypes as $fieldName => $fieldType) {
            if (isset($relations[$fieldName])) {

                $relation = $relations[$fieldName];

                if (array_key_exists($fieldName, $dependedRelations)) {
                    $fieldTypes[$fieldName]->dataType = 'depended_relational';
                    $fieldTypes[$fieldName]->options = (object)[
                        'dependedFrom' => $this->getDependedFrom($fieldName, $dependedRelations)
                    ];
                } else {
                    $fieldTypes[$fieldName]->dataType = 'relational';
                }

                if ($includePermittedValues) {
                    if (!array_key_exists($fieldName, $dependedRelations)) {
                        $relationalData = $this->getRelationalData(
                            $relation->tableName,
                            $relation->originalTitleField,
                            $relation->where,
                            $relation->orderBy,
                            $fieldName
                        );

                        $fieldTypes[$fieldName]->permittedValues = $relationalData;
                    } else {
                        $fieldTypes[$fieldName]->permittedValues = [];
                    }
                }
            } else if (isset($dynamicRelations[$fieldName])) {
                $fieldTypes[$fieldName]->dataType = 'dynamic-relation';
                $fieldTypes[$fieldName]->options = null;
            }
        }

        foreach ($this->gCrud->getRelationNtoN() as $fieldName => $relation) {

            $relationNtoNDataType = $relation->orderingFieldName ? 'relational_n_n_ordering' : 'relational_n_n';
            $fieldTypes[$fieldName] = (object)array(
                'dataType' => $relationNtoNDataType,
                'isNullable' => false,
                'defaultValue' => ''
            );

            if ($includePermittedValues) {
                $fieldTypes[$fieldName]->permittedValues = $this->getRelationalData(
                    $relation->referrerTable,
                    $relation->referrerTitleField,
                    $relation->where,
                    $relation->sortingFieldName,
                    $fieldName
                );
            }
        }

        $manualFieldTypes = $this->gCrud->getFieldTypes();
        foreach ($manualFieldTypes as $fieldName => $filedTypeInfo) {
            if (isset($fieldTypes[$fieldName])) {
                $fieldTypes[$fieldName]->dataType = $filedTypeInfo->dataType;

                if ($filedTypeInfo->permittedValues !== null) {
                    $fieldTypes[$fieldName]->permittedValues = $filedTypeInfo->permittedValues;
                }

                if ($filedTypeInfo->options !== null) {
                    $fieldTypes[$fieldName]->options = $filedTypeInfo->options;
                }

                if (
                    property_exists($fieldTypes[$fieldName], 'options') &&
                    is_array($fieldTypes[$fieldName]->options) &&
                    isset($fieldTypes[$fieldName]->options['defaultValue'])
                ) {
                    $fieldTypes[$fieldName]->defaultValue = $fieldTypes[$fieldName]->options['defaultValue'];
                }
            }
        }

        $texteditorFields = $this->gCrud->getTextEditorFields();
        foreach ($texteditorFields as $fieldName => $fieldValue) {
            if (isset($fieldTypes[$fieldName])) {
                $fieldTypes[$fieldName]->dataType = 'texteditor';
            }
        }

        $readOnlyFields = $this->gCrud->getReadOnlyFields();

        foreach ($readOnlyFields as $fieldName) {
            if (isset($fieldTypes[$fieldName])) {
                $fieldTypes[$fieldName]->isReadOnly = true;
            }
        }

        $unsetSearchColumns = $this->gCrud->getUnsetSearchColumns();
        $unsetSortingColumns = $this->gCrud->getUnsetSortingColumns();

        foreach ($fieldTypes as $fieldName => &$field) {
            $field->isReadOnly = property_exists($field, 'isReadOnly') ? $field->isReadOnly : false;
            $field->isSearchable = !in_array($fieldName, $unsetSearchColumns);
            $field->hasOrdering = !in_array($fieldName, $unsetSortingColumns) && $this->hasOrdering($field->dataType);
        }

        // Add extra field options such as hint_text. Those options will always need to be added as last
        // so they can be overwritten
        $fieldOptions = $this->gCrud->getFieldOptions();

        foreach ($fieldOptions as $fieldName => $fieldOption) {
            if (isset($fieldTypes[$fieldName])) {
                if (!empty($fieldTypes[$fieldName]->options)) {
                    $fieldTypes[$fieldName]->options = (object)array_merge(
                        (array)$fieldTypes[$fieldName]->options,
                        (array)$fieldOption
                    );
                } else {
                    $fieldTypes[$fieldName]->options = $fieldOption;
                }
            }
        }

        if ($includePermittedValues) {
            $this->fieldTypesWithPermittedData = $fieldTypes;
        } else {
            $this->fieldTypesLight = $fieldTypes;
        }

        return $fieldTypes;
    }

    public function setModel()
    {
        $model = $this->gCrud->getModel();
        if ($model === null) {
            $databaseConfig = $this->gCrud->getDatabaseConfig();
            if ($databaseConfig === null) {
                throw new \Exception('You need to add a database configuration file first');
            }
            $model = new Model($databaseConfig);
            $this->gCrud->setModel($model);
        }

        $config = $this->gCrud->getConfig();

        $this->gCrud->getModel()->setOptimizeSqlQueries($config['optimize_sql_queries']);
    }

    public function getEditFields()
    {
        return $this->transformFieldsList($this->gCrud->getEditFields(), $this->gCrud->getUnsetEditFields());
    }

    public function getCloneFields()
    {
        return $this->transformFieldsList($this->gCrud->getCloneFields(), $this->gCrud->getUnsetCloneFields());
    }

    public function getAddFields()
    {
        return $this->transformFieldsList($this->gCrud->getAddFields(), $this->gCrud->getUnsetAddFields());
    }

    public function getFilteredData($fields, $data, $readOnlyFields = [])
    {
        $finalData = [];

        $relation1to1Fields = $this->getRelation1to1Fields();
        $relationNtoNfields = $this->gCrud->getRelationNtoN();
        $fieldTypes = $this->getFieldTypes();
        $blobFields = $this->gCrud->getBlobFieldNames();

        foreach ($relation1to1Fields as $fieldName) {
            if (array_key_exists($fieldName, $data)) {
                // We are unsetting the relation1to1 fields as they are not part of the main table,
                // and we have already stored the data for 1 to 1 relations into another array
                unset($data[$fieldName]);
            }
        }

        foreach ($fields as $field) {
            if (array_key_exists($field->name, $data) && !in_array($field->name, $readOnlyFields)) {
                if (!isset($relationNtoNfields[$field->name])) {
                    $finalData[$field->name] = $this->filterValue($fieldTypes, $field->name, $data[$field->name]);
                }
            }
        }

        $mappedFields = $this->getMappedFields();

        if (!empty($mappedFields)) {
            foreach ($mappedFields as $fieldName => $mappedField) {
                if (array_key_exists($fieldName, $finalData)) {

                    // Edge case when one field is NULLable and the other is not!
                    if (
                        $finalData[$fieldName] === null &&
                        $fieldTypes[$fieldName]->isNullable &&
                        !$fieldTypes[$mappedField]->isNullable
                    ) {
                        $finalData[$mappedField] = '';
                    } else {
                        $finalData[$mappedField] = $finalData[$fieldName];
                    }


                    // Work-around: If we have blob field and empty value then we will need to store both fields as empty
                    // and hence we are NOT un-setting the old field
                    if (! (in_array($fieldName, $blobFields) && $finalData[$fieldName] === null)) {
                        unset($finalData[$fieldName]);
                    }
                }
            }
        }

        return $finalData;
    }

    public function filterValue($fieldTypes, $fieldName, $fieldValue) {
        if( isset($fieldTypes[$fieldName]) &&
            $fieldTypes[$fieldName]->isNullable === true &&
            $fieldValue === ''
        ) {
            return null;
        }

        return $fieldValue;
    }

    public function getRelation1to1Fields() {
        $relation1to1 = $this->gCrud->getRelation1to1();
        $fields = [];

        foreach ($relation1to1 as $relation) {
            $fields = array_merge($fields, $relation->relatedTableFields);
        }

        return $fields;
    }

    /**
     * @param $fields
     * @param $data
     * @return array
     */
    public function getRelation1to1Data($fields, $data) {
        $relation1to1Data = [];

        $relation1to1 = $this->getRelations1To1();
        foreach ($relation1to1 as $relation) {
            $currentRelationData = [];
            foreach ($fields as $field) {
                if (in_array($field->name, $relation->relatedTableFields) && array_key_exists($field->name, $data)) {
                    $currentRelationData[$field->name] = $data[$field->name];
                }
            }
            $relation1to1Data[] = (object)[
              'relation' => $relation,
              'data' => $currentRelationData
            ];
        }

        return $relation1to1Data;
    }

    public function getRelationNtoNData($fields, $data)
    {
        $relationNtoNData = [];

        $relationNtoNfields = $this->gCrud->getRelationNtoN();

        foreach ($fields as $field) {
            if (isset($relationNtoNfields[$field->name])) {
                if (array_key_exists($field->name, $data)) {
                    $relationNtoNData[$field->name] = $data[$field->name];
                } else {
                    $relationNtoNData[$field->name] = [];
                }
            }
        }

        return $relationNtoNData;
    }

    public function getFieldTypesAddForm() {
        $fieldTypes = $this->getFieldTypes();
        $requiredFields = $this->getRequiredFields('insert');

        $fieldTypesAddForm = $this->gCrud->getFieldTypesAddForm();
        $callbackAddFields  = $this->gCrud->getCallbackAddFields();

        foreach ($callbackAddFields as $fieldName => $callback) {
            $fieldTypeModel = new ModelFieldType();
            $fieldTypeModel->dataType = GroceryCrud::FIELD_TYPE_BACKEND_CALLBACK;

            $fieldTypesAddForm[$fieldName] = $fieldTypeModel;
        }

        foreach ($requiredFields as $fieldName) {
            if (isset($fieldTypesAddForm[$fieldName])) {
                $fieldTypesAddForm[$fieldName]->isRequired = true;
            } else if (isset($fieldTypes[$fieldName])) {
                $fieldTypesAddForm[$fieldName] = clone $fieldTypes[$fieldName];
                $fieldTypesAddForm[$fieldName]->isRequired = true;
            }
        }

        return $fieldTypesAddForm;
    }

    public function getFieldTypesEditForm() {
        $fieldTypes = $this->getFieldTypes();
        $requiredFields = $this->getRequiredFields('update');

        $fieldTypesEditForm = $this->gCrud->getFieldTypesEditForm();
        $callbackEditFields  = $this->gCrud->getCallbackEditFields();

        foreach ($callbackEditFields as $fieldName => $callback) {
            $fieldTypeModel = new ModelFieldType();
            $fieldTypeModel->dataType = GroceryCrud::FIELD_TYPE_BACKEND_CALLBACK;

            $fieldTypesEditForm[$fieldName] = $fieldTypeModel;
        }

        foreach ($requiredFields as $fieldName) {
            if (isset($fieldTypesEditForm[$fieldName])) {
                $fieldTypesEditForm[$fieldName]->isRequired = true;
            } else if (isset($fieldTypes[$fieldName])) {
                $fieldTypesEditForm[$fieldName] = clone $fieldTypes[$fieldName];
                $fieldTypesEditForm[$fieldName]->isRequired = true;
            }
        }

        return $fieldTypesEditForm;
    }

    public function getFieldTypesCloneForm() {
        $fieldTypes = $this->getFieldTypes();
        $requiredFields = $this->getRequiredFields('clone');

        $fieldTypesCloneForm = $this->gCrud->getFieldTypesCloneForm();
        $callbackCloneFields  = $this->gCrud->getCallbackCloneFields();

        foreach ($callbackCloneFields as $fieldName => $callback) {
            $fieldTypeModel = new ModelFieldType();
            $fieldTypeModel->dataType = GroceryCrud::FIELD_TYPE_BACKEND_CALLBACK;

            $fieldTypesCloneForm[$fieldName] = $fieldTypeModel;
        }

        foreach ($requiredFields as $fieldName) {
            if (isset($fieldTypesCloneForm[$fieldName])) {
                $fieldTypesCloneForm[$fieldName]->isRequired = true;
            } else if (isset($fieldTypes[$fieldName])) {
                $fieldTypesCloneForm[$fieldName] = clone $fieldTypes[$fieldName];
                $fieldTypesCloneForm[$fieldName]->isRequired = true;
            }
        }

        return $fieldTypesCloneForm;
    }

    public function getFieldTypesColumns() : array {
        $fieldTypeColumns = $this->gCrud->getFieldTypeColumns();
        $callbackColumns  = $this->gCrud->getCallbackColumns();
        $unsetSearchColumns = $this->gCrud->getUnsetSearchColumns();

        foreach ($callbackColumns as $fieldName => $callback) {
            $fieldTypeModel = new ModelFieldType();
            $fieldTypeModel->dataType = GroceryCrud::FIELD_TYPE_CALLBACK_COLUMN;

            $fieldTypeColumns[$fieldName] = $fieldTypeModel;
        }

        foreach ($fieldTypeColumns as $fieldName => $fieldObject) {
            $fieldObject->isSearchable = !in_array($fieldName, $unsetSearchColumns);
            $fieldObject->hasOrdering = $this->hasOrdering($fieldObject->dataType);
            $fieldTypeColumns[$fieldName] = $fieldObject;
        }

        return $fieldTypeColumns;
    }

    public function getFieldTypesSearchColumns() : array {
        $fieldTypesSearchColumns = $this->gCrud->getFieldTypesSearchColumns();
        $unsetSearchColumns = $this->gCrud->getUnsetSearchColumns();

        foreach ($fieldTypesSearchColumns as $fieldName => $fieldObject) {
            $fieldObject->isSearchable = !in_array($fieldName, $unsetSearchColumns);
            $fieldObject->hasOrdering = $this->hasOrdering($fieldObject->dataType);
            $fieldTypesSearchColumns[$fieldName] = $fieldObject;
        }

        return $fieldTypesSearchColumns;
    }

    public function getFieldTypesReadForm() {
        $fieldTypesReadForm = $this->gCrud->getFieldTypesReadForm();
        $callbackReadFields  = $this->gCrud->getCallbackReadFields();

        foreach ($callbackReadFields as $fieldName => $callback) {
            $fieldTypeModel = new ModelFieldType();
            $fieldTypeModel->dataType = GroceryCrud::FIELD_TYPE_BACKEND_CALLBACK;

            $fieldTypesReadForm[$fieldName] = $fieldTypeModel;
        }

        return $fieldTypesReadForm;
    }

    public function hasOrdering($fieldType) {

        switch ($fieldType) {
            case GroceryCrud::FIELD_TYPE_STRING:
                return true;
            case GroceryCrud::FIELD_TYPE_DROPDOWN:
            case GroceryCrud::FIELD_TYPE_DROPDOWN_WITH_SEARCH:
            case GroceryCrud::FIELD_TYPE_MULTIPLE_SELECT_NATIVE:
            case GroceryCrud::FIELD_TYPE_MULTIPLE_SELECT_SEARCHABLE:
            case 'relational_n_n':
            case 'native_relational_n_n':
                return false;
            case 'relational':
            case GroceryCrud::FIELD_TYPE_RELATIONAL_NATIVE:
            case 'depended_relational':
                $config = $this->getConfigParameters();
                return  $config['optimize_sql_queries'] === false;
        }

        // Default true
        return true;
    }


    public function validateVisibleColumnsMinified($visibleColumnsMinified) {

        $visibleColumnsIndexes = explode('|', $visibleColumnsMinified);

        foreach ($visibleColumnsIndexes as $index) {
            if (!is_numeric($index)) {
                return false;
            }
        }

        return true;
    }

    public function getVisibleColumns($allColumns, $unsetColumns, $visibleColumnsMinified) {
        if (!$this->validateVisibleColumnsMinified($visibleColumnsMinified)) {
            throw new \Exception('Wrong input data for visible_columns');
        }

        $datagridColumns = [];

        if (empty($allColumns)) {
            $allColumns = $this->removePrimaryKeyFromList($this->getColumnNames());
        }

        if (!empty($unsetColumns)) {
            foreach ($unsetColumns as $unsetFieldName) {
                $allColumns = ArrayHelper::array_reject($allColumns, function ($value) use ($unsetFieldName) {
                    return $unsetFieldName === $value;
                });
            }
        }

        $visibleColumnsIndexes = explode('|', $visibleColumnsMinified);

        foreach ($allColumns as $keyIndex => $fieldName) {
            if (in_array($keyIndex, $visibleColumnsIndexes)) {
                $datagridColumns[$fieldName] = true;
            }
        }

        if (empty($datagridColumns)) {
            throw new \Exception('Wrong input data for visible_columns');
        }

        return $datagridColumns;
    }

    public function getFullLanguagePath() {
        $config = $this->gCrud->getConfig();
        $language = $this->gCrud->getLanguage() !== null ? $this->gCrud->getLanguage() : $config['default_language'];

        $gCrudLanguagePath = $this->gCrud->getLanguagePath();

        if ($gCrudLanguagePath === null ) {
            return (__DIR__ . '/../../i18n/') . $language . '.php';
        }

        if (is_dir($gCrudLanguagePath)) {
            if (substr($gCrudLanguagePath, -1) !== '/') {
                $gCrudLanguagePath .= '/';
            }

            return $gCrudLanguagePath . $language . '.php';
        }

        // If this is not DIR we assume that we have the full path
        return $gCrudLanguagePath;
    }

    public function getI18n()
    {
        $languagePath = $this->getFullLanguagePath();

        if (file_exists($languagePath)) {
            // Good old fashion way :)
            $i18nArray = include($languagePath);
            $i18n = (object)$i18nArray;
        } else {
            throw new \Exception('Language path "' . $languagePath . '" does not exist');
        }

        foreach ($this->gCrud->getLandStrings() as $name => $string) {
            $i18n->$name = $string;
        }

        if ($this->gCrud->getSubject() !== null) {
            $i18n->subject = $this->gCrud->getSubject();
        }

        if ($this->gCrud->getSubjectPlural() !== null) {
            $i18n->subject_plural = $this->gCrud->getSubjectPlural();
        }

        return $i18n;
    }

    public function transformFieldsList($simpleList, $unsetFields) {
        $transformedList = [];
        $displayAs = $this->gCrud->getDisplayAs();

        if (empty($simpleList)) {
            $simpleList = $this->removePrimaryKeyFromList($this->getColumnNames());
        }

        if (!empty($unsetFields)) {
            foreach ($unsetFields as $unsetFieldName) {
                $simpleList = ArrayHelper::array_reject($simpleList, function ($value) use ($unsetFieldName) {
                    return $unsetFieldName === $value;
                });
            }
        }

        foreach ($simpleList as $fieldName) {
            $transformedList[] = (object)array(
                'name' => $fieldName,
                'displayAs' => !empty($displayAs[$fieldName]) ? $displayAs[$fieldName] : ucfirst(str_replace('_',' ', $fieldName))
            );
        }

        return $transformedList;
    }

    public function getUniqueId() {
        return $this->gCrud->getLayout()->getUniqueId();
    }

    public function getLanguage() {
        $config = $this->gCrud->getConfig();

        return $this->gCrud->getLanguage() !== null ? $this->gCrud->getLanguage() : $config['default_language'];
    }

    public function uploadDataHasValue($fieldName, $fieldValue): bool
    {
        if (!empty($fieldValue) && $fieldValue !== self::UPLOAD_FIELD_EMPTY_STRING) {
            return true;
        }

        // If the fieldValue is empty then we need to also check if we have upload data
        if (array_key_exists('data', $_FILES) &&
            array_key_exists('name', $_FILES['data']) &&
            array_key_exists($fieldName . GroceryCrud::PREFIX_FILE_UPLOAD, $_FILES['data']['name'])) {
            return true;
        }

        return false;
    }

    public function getRequiredFields($formOperation, $filterRuleByFieldName = '') {
        switch ($formOperation) {
            case 'insert':
                return $this->gCrud->getRequiredAddFields();
            case 'update':
                return $this->gCrud->getRequiredEditFields();
            case 'quick-update':
                if (!empty($filterRuleByFieldName)) {
                    $requiredEditFields = $this->gCrud->getRequiredEditFields();
                    if (in_array($filterRuleByFieldName, $requiredEditFields)) {
                        return [$filterRuleByFieldName];
                    }
                    return [];
                }
                return $this->gCrud->getRequiredEditFields();
            case 'clone':
                return $this->gCrud->getRequiredCloneFields();
            default:
                return [];
        }
    }

    public function setValidationRules(string $formOperation, $filterRuleByFieldName = '')
    {
        $fieldTypes = $this->getFieldTypes();
        $data = $this->getStateParameters()->data;

        $uploadOneFields = $this->getUploadOneFields($fieldTypes);
        $uploadMultipleFields = $this->getUploadMultipleFields($fieldTypes);

        $uploadFields = array_merge($uploadOneFields, $uploadMultipleFields);
        $hasUploadFields = !empty($uploadFields);

        $validator = $this->gCrud->getValidator();
        $validationRules = $this->gCrud->getValidationRules();
        foreach ($validationRules as $rule) {
            $validator->set_rule($rule['fieldName'], $rule['rule'], $rule['parameters']);
        }

        $displayAs = $this->gCrud->getDisplayAs();
        $uniqueFields = $this->gCrud->getUniqueFields();

        if (!empty($uniqueFields)) {
            $this->gCrud->getModel()->setPrimaryKey($this->getPrimaryKeyName());
            $uniqueCallback = function ($field, $value) {
                $stateParameters = $this->getStateParameters();
                $primaryKeyValue = !empty($stateParameters->primaryKeyValue) ? $stateParameters->primaryKeyValue : null;
                return $this->gCrud->getModel()->isUnique($field, $value, $primaryKeyValue);
            };
            $uniqueCallback = \Closure::bind($uniqueCallback, $this);
            $validator->setUniqueCallback($uniqueCallback);
        }

        if (!empty($uploadFields)) {
            $requiredUploadCallback = function ($field, $value) {
                return $this->uploadDataHasValue($field, $value);
            };
            $requiredUploadCallback = \Closure::bind($requiredUploadCallback, $this);
            $validator->setRequiredUploadCallback($requiredUploadCallback);
        }

        foreach ($displayAs as $fieldName => $display) {
            $validator->set_label($fieldName, $display);
        }

        $requiredFields = $this->getRequiredFields($formOperation, $filterRuleByFieldName);
        foreach ($requiredFields as $fieldName) {
            if ($hasUploadFields && array_key_exists($fieldName, $uploadFields)) {
                // Work-around for Valitron library since it doesn't support callback validation to a non-empty field
                // We are changing the value of the field to a non-empty data if the field is empty
                // to make the callback work
                if (empty($data[$fieldName])) {
                    $data[$fieldName] = self::UPLOAD_FIELD_EMPTY_STRING;
                }

                $validator->set_rule($fieldName, 'requiredUpload');
            } else {
                $validator->set_rule($fieldName, 'required');
            }
        }

        foreach ($uniqueFields as $fieldName) {
            $validator->set_rule($fieldName, 'unique');
        }

        $validator->set_language($this->getLanguage());

        $validator->set_data($data);

        return $validator;
    }
}