<?php
namespace GroceryCrud\Core;

use GroceryCrud\Core\Layout\LayoutInterface;

class Layout implements LayoutInterface
{
    protected $_ApiUrl;
    protected $_viewsPath;
    protected $_assetsFolder;
    protected $_themeName 				= null;
    protected $_uniqueId 				= null;
    protected $_publishEvents 			= false;
    protected $theme 				    = null;

    protected $css_files				= array();
    protected $js_files					= array();

    public $autoLoadFrontend = true;

    public function __construct($config) {
        $this->_viewsPath = realpath(dirname ( __FILE__ ) . '/../' . 'Views') . '/';
        $this->_assetsFolder = $config['assets_folder'];
        $this->_themeName = $config['theme'];
    }

    public function setUniqueId($uniqueId)
    {
        $this->_uniqueId = $uniqueId;
    }

    public function getUniqueId()
    {
        return $this->_uniqueId;
    }

    public function setPublishEvents($publishEvents)
    {
        $this->_publishEvents = $publishEvents;
    }

    public function getPublishEvents()
    {
        return $this->_publishEvents;
    }

    public function setApiUrl($url){
        $this->_ApiUrl = $url;

        return $this;
    }

    public function getApiUrl()
    {
        if ($this->_ApiUrl !== null) {
            return $this->_ApiUrl;
        }

        if(php_sapi_name() !== 'cli') {
            return str_replace('&amp;', '&', htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8'));
        }

        return '';
    }

    public function setAutoLoadFrontend($autoLoadFrontend)
    {
        $this->autoLoadFrontend = $autoLoadFrontend;
    }

    public function getThemeName()
    {
        return $this->_themeName;
    }

    public function setCssFile($css_file)
    {
        $this->css_files[sha1($css_file)] = $css_file;
    }

    public function setJavaScriptFile($js_file)
    {
        $this->js_files[sha1($js_file)] = $js_file;
    }

    public function get_css_files()
    {
        return $this->css_files;
    }

    public function getJavaScriptFiles()
    {
        return $this->js_files;
    }

    public function clearJavaScriptFiles()
    {
        $this->js_files = [];
    }

    public function setTheme($theme = null)
    {
        $this->_themeName = $theme;

        return $this;
    }

    public function themeView($view, $vars = array(), $return = FALSE)
    {
        $vars = (is_object($vars)) ? get_object_vars($vars) : $vars;

        $ext = pathinfo($view, PATHINFO_EXTENSION);
        $file = ($ext == '') ? $view.'.php' : $view;

        $path = $this->_viewsPath . $file;

        if ( !file_exists($path))
        {
            throw new \GroceryCrud\Core\Exceptions\Exception('Unable to load the requested file: '.$path, 16);
        }

        extract($vars);

        #region buffering...
        ob_start();

        include($path);

        $buffer = ob_get_contents();
        @ob_end_clean();
        #endregion

        if ($return === TRUE)
        {
            return $buffer;
        }

        $this->views_as_string .= $buffer;
    }
}
