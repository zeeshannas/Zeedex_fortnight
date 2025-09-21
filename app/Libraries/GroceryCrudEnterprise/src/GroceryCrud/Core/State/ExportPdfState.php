<?php
namespace GroceryCrud\Core\State;

use GroceryCrud\Core\Exceptions\PermissionDeniedException;

class ExportPdfState extends ExportState {

    const MAX_AMOUNT_OF_PRINT = 5000;

    public function getStateParameters()
    {
        $stateParameters = parent::getStateParameters();

        $stateParameters->per_page = ExportPdfState::MAX_AMOUNT_OF_PRINT;

        return $stateParameters;
    }

    public function render()
    {
        if (!$this->gCrud->getLoadList()) {
            throw new PermissionDeniedException('Permission denied. You are not allowed to use this operation');
        }

        $output = $this->getFinalData();

        $subjectPlural = $this->gCrud->getSubjectPlural();
        $output->subjectPlural = $subjectPlural;
        $output->filename = (!empty($subjectPlural) ? $subjectPlural : 'Spreadsheet') . '_' . date('Y-m-d');
        $output->i18n = $this->getI18n();

        echo $this->gCrud->getLayout()->themeView('export-pdf.php', $output, true);

        exit;
    }
}