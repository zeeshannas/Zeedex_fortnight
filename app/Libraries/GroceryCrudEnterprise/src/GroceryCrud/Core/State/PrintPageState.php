<?php
namespace GroceryCrud\Core\State;

use GroceryCrud\Core\Exceptions\PermissionDeniedException;

class PrintPageState extends DatagridState {

    const MAX_AMOUNT_OF_PRINT = 500;

    public function getStateParameters()
    {
        $stateParameters = parent::getStateParameters();

        $stateParameters->per_page = self::MAX_AMOUNT_OF_PRINT;

        return $stateParameters;
    }

    public function render()
    {
        if (!$this->gCrud->getLoadList()) {
            throw new PermissionDeniedException('Permission denied. You are not allowed to use this operation');
        }

        $output = $this->getData();
        $output->data = $this->enhanceColumnResults($output->data);
        $output->data = $this->filterResults($output->data);
        $output = $this->addcsrfToken($output);

        $render = new RenderAbstract();

        $render->output = json_encode($output);
        $render->outputAsObject = $output;
        $render->isJSONResponse = true;

        return $render;
    }
}