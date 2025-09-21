<?php
namespace GroceryCrud\Core\State;

use GroceryCrud\Core\GroceryCrud as GCrud;
use GroceryCrud\Core\Render\RenderAbstract;
use GroceryCrud\Core\Model;


class MainState extends StateAbstract implements StateInterface
{
    /**
     * MainState constructor.
     * @param GCrud $gCrud
     */
    function __construct(GCrud $gCrud)
    {
        $this->gCrud = $gCrud;
    }

    public function render()
    {
        $layout = $this->gCrud->getLayout();

        $apiUrlPath = $this->gCrud->getApiUrlPath();
        if ($apiUrlPath !== null) {
            $layout->setApiUrl($apiUrlPath);
        }

        $uniqueId = $this->gCrud->getUniqueId();

        if ($uniqueId !== null) {
            $layout->setUniqueId($uniqueId);
        } else {
            $dbTableName = $this->gCrud->getDbTableName() !== null ? $this->gCrud->getDbTableName() : "";
            $layout->setUniqueId(md5($layout->getApiUrl() . $dbTableName));
        }

        $layout->setAutoLoadFrontend($this->gCrud->getAutoloadJavaScript());

        $output = $this->showList();

        $render = new RenderAbstract();

        $render->output = $output;
        $render->js_files = $layout->getJavaScriptFiles();
        $render->css_files = $layout->get_css_files();
        $render->isJSONResponse = false;

        return $render;

    }

    public function getLoadSelect2()
    {
        $dependentRelations = $this->gCrud->getDependedRelation();

        return count($dependentRelations) > 0 && $this->gCrud->getLoadSelect2();
    }

    public function showList()
    {
        $data = $this->_getCommonData();
        $config = $this->getConfigParameters();

        $textEditorFields = $this->gCrud->getTextEditorFields();

        $data->theme = $this->getTheme();
        $data->skin = $this->gCrud->getSkin();

        $data->load_texteditor = !empty($textEditorFields);
        $data->load_css_theme = $this->gCrud->getLoadCssTheme();
        $data->load_css_icons = $this->gCrud->getLoadCssIcons();
        $data->load_css_third_party = $this->gCrud->getLoadCssThirdParty();

        $data->is_development_environment = $config['environment']  === 'development';
        $data->publish_events = $config['publish_events'];
        $data->remember_state = $config['remember_state_upon_refresh'];
        $data->remember_filtering = $config['remember_filters_upon_refresh'];
        $data->display_js_files_in_output = $config['display_js_files_in_output'];

        $data->autoload_javascript = $this->gCrud->getAutoloadJavaScript();

        return $this->gCrud->getLayout()->themeView('datagrid.php', $data, true);
    }

    public function _getCommonData()
    {
        $data = (object)array();

        $data->subject 				= $this->gCrud->getSubject();
        $data->subject_plural 		= $this->gCrud->getSubjectPlural();

        return $data;
    }
}