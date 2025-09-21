<?php
namespace GroceryCrud\Core\Upload;

interface UploadedFilesInterface {
    public function addUploadedFile($file);
    public function setUploadedFiles(array $_uploadedFiles);
    public function getUploadedFiles() : array;
    public function getFirstUploadedFile() : string;
}