<?php
namespace GroceryCrud\Core\State;

use GroceryCrud\Core\GroceryCrud;
use GroceryCrud\Core\Render\RenderAbstract;

class InitialState extends StateAbstract implements StateInterface
{
    public function render()
    {
        $this->setInitialData();

        $output = $this->showInitData();

        $render = new RenderAbstract();

        $render->output = json_encode($output);
        $render->outputAsObject = $output;
        $render->isJSONResponse = true;

        return $render;
    }

    public function showInitData()
    {
        $data = (object)[];
        $config = $this->gCrud->getConfig();

        $data->i18n = $this->getI18n();
        $data->subject = (object) [
            'subject_single' => $this->gCrud->getSubject() !== null ? $this->gCrud->getSubject() : '',
            'subject_plural' => $this->gCrud->getSubjectPlural() !== null ? $this->gCrud->getSubjectPlural() : ''
        ];

        $actionButtons = $this->gCrud->getActionButtons();
        $actionButtonsMultiple = $this->gCrud->getActionButtonsMultiple();
        $datagridButtons = $this->gCrud->getDatagridButtons();

        $inlineEditFields = $this->gCrud->getInlineEditFields();

        $operations = (object) array(
            'actionButtons' => !empty($actionButtons),
            'actionButtonsMultiple' => !empty($actionButtonsMultiple),
            'add' => $this->gCrud->getLoadAdd(),
            'backToList' => $this->gCrud->getLoadBackToList(),
            'clone' => $this->gCrud->getLoadClone(),
            'columns' => $this->gCrud->getLoadColumnsButton(),
            'datagridTitle' => $this->gCrud->getLoadDatagridTitle(),
            'deleteMultiple' => $this->gCrud->getLoadDeleteMultiple(),
            'deleteSingle' => $this->gCrud->getLoadDelete(),
            'edit' => $this->gCrud->getLoadEdit(),
            'exportData' => $this->gCrud->getLoadExport(),
            'exportExcel' => $this->gCrud->getLoadExportExcel(),
            'exportPdf' => $this->gCrud->getLoadExportPdf(),
            'filters' => $this->gCrud->getLoadFilters(),
            'inlineEdit' => !empty($inlineEditFields),
            'list' => $this->gCrud->getLoadList(),
            'print' => $this->gCrud->getLoadPrint(),
            'read' => $this->gCrud->getLoadRead(),
            'settings' => $this->gCrud->getLoadSettings(),
        );

        $data->language = $this->getLanguage();
        $data->columns = $this->transformFieldsList($this->gCrud->getColumns(), $this->gCrud->getUnsetColumns());
        $data->addFields = $this->getAddFields();
        $data->editFields = $this->getEditFields();
        $data->cloneFields = $this->getCloneFields();
        $data->readFields = $this->transformFieldsList($this->gCrud->getReadFields(), $this->gCrud->getUnsetReadFields());
        $data->readOnlyAddFields = $this->gCrud->getReadOnlyAddFields();
        $data->readOnlyEditFields = $this->gCrud->getReadOnlyEditFields();
        $data->readOnlyCloneFields = $this->gCrud->getReadOnlyCloneFields();
        $data->inlineEditFields = $inlineEditFields;

        $data->paging = (object)[
            'defaultPerPage' => $config['default_per_page'],
            'pagingOptions'  => $config['paging_options']
        ];

        $data->primaryKeyField = $this->getPrimaryKeyField();
        $data->fieldTypes = $this->filterTypesPrivateInfo($this->getFieldTypes());
        $data->fieldTypesAddForm = $this->filterTypesPrivateInfo($this->getFieldTypesAddForm());
        $data->fieldTypesEditForm = $this->filterTypesPrivateInfo($this->getFieldTypesEditForm());
        $data->fieldTypesReadForm = $this->filterTypesPrivateInfo($this->getFieldTypesReadForm());
        $data->fieldTypesCloneForm = $this->filterTypesPrivateInfo($this->getFieldTypesCloneForm());
        $data->fieldTypesColumns = $this->filterTypesPrivateInfo($this->getFieldTypesColumns());
        $data->fieldTypesSearchColumns = $this->filterTypesPrivateInfo($this->getFieldTypesSearchColumns());
        $data->operations = $operations;
        $data->configuration = $this->extraConfigurations();

        $data->groupFields = (object) [
            'groupFields' => $this->gCrud->getGroupFields(),
            'groupFieldsAddForm' => $this->gCrud->getGroupFieldsAddForm(),
            'groupFieldsEditForm' => $this->gCrud->getGroupFieldsEditForm(),
            'groupFieldsCloneForm' => $this->gCrud->getGroupFieldsCloneForm(),
            'groupFieldsReadForm' => $this->gCrud->getGroupFieldsReadForm(),
        ];

        $data->actionButtonsMultiple = $actionButtonsMultiple;
        $data->datagridButtons = $datagridButtons;

        $datagrid = (object) [];

        if (count($datagridButtons) > 0) {
            $datagrid->datagridButtons = $datagridButtons;
        }

        $data->datagrid = $datagrid;

        $isMasterDetail = $this->gCrud->getIsMasterDetail();
        if ($isMasterDetail) {
            $data->masterDetail = (object)$this->gCrud->getMasterDetailInfo();
        }

        $defaultColumnWidth = $this->gCrud->getDefaultColumnWidth();
        if (!empty($defaultColumnWidth)) {
            $data->columnWidth = $defaultColumnWidth;
        }

        $data->displayOptions = (object)[];

        if ($this->gCrud->getDefaultState() !== null) {
            $data->displayOptions->defaultState = $this->gCrud->getDefaultState();
        }

        $data = $this->addcsrfToken($data);

        return $data;
    }

    public function extraConfigurations() {
        $data = (object)[];

        $config = $this->gCrud->getConfig();

        $data->urlHistory = $config['url_history'];
        $data->actionButtonType = $config['action_button_type'];
        $data->openInModal = $config['open_in_modal'];
        $data->showImagePreview = $config['show_image_preview'];

        $data->leftSideActions = $config['actions_column_side'] === 'left';
        $data->rightSideActions = $config['actions_column_side'] === 'right';

        $data->maxActionButtons = (object)[
            'mobile' => $config['max_action_buttons']['mobile'],
            'desktop' =>  $config['max_action_buttons']['desktop'],
        ];

        return $data;
    }

    public function filterTypesPrivateInfo($fieldTypes) {
        foreach ($fieldTypes as &$fieldType) {
            switch ($fieldType->dataType) {
                case GroceryCrud::FIELD_TYPE_BLOB: {
                $fieldType->options = null;
                    break;
                }
                case GroceryCrud::FIELD_TYPE_UPLOAD_MULTIPLE:
                case GroceryCrud::FIELD_TYPE_UPLOAD: {
                    unset($fieldType->options->uploadPath);
                    break;
                }
                default:
                    break;

            }
        }

        return $fieldTypes;
    }

    public function getPrimaryKeyField() {
        return $this->gCrud->getModel()->getPrimaryKeyField();
    }
}