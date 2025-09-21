<?php
namespace GroceryCrud\Core\Upload;

class UploadedFiles implements UploadedFilesInterface {
    protected $_uploadedFiles = [];

    public function addUploadedFile($file)
    {
        $this->_uploadedFiles[] = $file;
    }

    /**
     * @param array $_uploadedFiles
     */
    public function setUploadedFiles(array $_uploadedFiles)
    {
        $this->_uploadedFiles = $_uploadedFiles;
    }

    public function getUploadedFiles() : array {
        return $this->_uploadedFiles;
    }

    public function getFirstUploadedFile() : string  {
        if (count($this->_uploadedFiles) > 0) {
            return $this->_uploadedFiles[0];
        }
        return "";
    }
}