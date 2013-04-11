<?php
/**
 *    Copyright (c) 2012 Lunar Logic LLC
 *
 *    This program is free software: you can redistribute it and/or  modify
 *    it under the terms of the GNU Affero General Public License, version 3,
 *    as published by the Free Software Foundation.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Version of an Asset that saves on the local file system.
 */
class LocalAsset extends Ox_Asset
{

    /**
     * Saved uri path
     * @var string
     */
    private $base_uri;

    /**
     * Setup
     * @param string $uri
     */
    public function __construct($uri='/assets/')
    {
        $this->base_uri = $uri;
    }

    /**
     * Save the uploaded file.  The form file input must have a name of "file".
     * @param $file_info array
     * @param $fieldName string
     * @return array|bool|null (the uploaded asset record)
     */
    public function save($file_info,$fieldName='file')
    {
        $doc = parent::save($file_info,$fieldName);
        if(empty($doc)) {
            return false;
        }
        if($uploaded=move_uploaded_file($file_info[$fieldName]["tmp_name"], DIR_UPLOAD . $doc['_id']->__tostring())) {
            Ox_Logger::logMessage('Uploaded: ' . $file_info[$fieldName]['name'] . ' -> ' . DIR_UPLOAD . $doc['_id']->__tostring());
        } else {
            Ox_Logger::logError('Uploaded: ' . $file_info[$fieldName]['name'] . ' -> ' . DIR_UPLOAD . $doc['_id']->__tostring());
        }
        return $doc;
    }

    /**
     * Return URL for the given asset
     * @param $asset
     * @return string
     */
    public function createURI($asset)
    {
        if(!$asset) {
            Ox_Logger::logWarning('LocalAsset::createURI Received null asset');
            return '#';
        }
        $extension = pathinfo($asset['original_name'], PATHINFO_EXTENSION);
        $appWebBase = Ox_LibraryLoader::Config_Parser()->getAppConfigValue(Ox_Dispatch::CONFIG_WEB_BASE_NAME);
        return $appWebBase . $this->base_uri . $asset['_id']->__toString() . '.' . $extension;
    }

    /**
     * Return the saved asset if it exists. to the user.
     * @param $uri string
     * @return mixed
     */
    public function getAsset($uri)
    {
        $db = Ox_LibraryLoader::Db();
        $base_filename = pathinfo($uri, PATHINFO_FILENAME);

        $assets = $db->getCollection('assets');
        $asset = $assets->findOne(array('_id'=>new MongoId($base_filename)));
        if(!$asset) {
            header("HTTP/1.0 404 Not Found");
            exit(1);
        }

        header('Content-Type: ' . $asset['type']);
        header('Content-Length: ' . $asset['size']);
        header('Content-Disposition: filename="' . $asset['original_name'] . '"');

        if(!@readfile(DIR_UPLOAD . $base_filename)) {
            Ox_Logger::logError('Could not read file: ' . DIR_UPLOAD . $base_filename);
            exit(1);
        }

        exit(0);

    }


}