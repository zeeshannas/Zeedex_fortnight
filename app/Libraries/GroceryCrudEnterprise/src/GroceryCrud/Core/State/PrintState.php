<?php
namespace GroceryCrud\Core\State;

use GroceryCrud\Core\Exceptions\PermissionDeniedException;

class PrintState extends ExportState {

    const MAX_AMOUNT_OF_PRINT = 5000;

    public function getStateParameters()
    {
        $stateParameters = parent::getStateParameters();

        $stateParameters->per_page = PrintState::MAX_AMOUNT_OF_PRINT;

        return $stateParameters;
    }

    public function render()
    {
        if (!$this->gCrud->getLoadList()) {
            throw new PermissionDeniedException('Permission denied. You are not allowed to use this operation');
        }

        $output = $this->getFinalData();

        $output->subjectPlural = $this->gCrud->getSubjectPlural();

        echo $this->gCrud->getLayout()->themeView('print.php', $output, true);

        exit;
    }
}