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
 *
 * @copyright Copyright (c) 2012 Lunar Logic LLC
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 * @package Ox_Assets
 */

/**
 * Contains classes to abstract saving and retrieval of assets.
 *
 * This class hierarchy assumes that files will be uploaded via a webform.
 *
 * @package Ox_Assets
 */
abstract class Ox_Asset
{
    protected $asset_collection = 'assets';

    /**
     * Save the document info into the database and return it.
     *
     * Inherited object should add this to save the information into the database.
     * <pre><code>
     * $doc = parent::save($file_info);
     * </code></pre>
     * You then use the information in $doc to the actual save.
     *
     * @param $file_info array
     * @param $fieldName string
     * @param $extra_fields array Additional fields for the asset that can overwrite the asset doc if needed.
     * @throws Ox_AssetException
     * @return array|null
     */
    public function save($file_info, $fieldName = 'file', array $extra_fields = array())
    {
        $db = Ox_LibraryLoader::Db();
        $assets = $db->getCollection('assets');
        $tmp_name = $file_info[$fieldName]["tmp_name"];
        if (isset($file_info[$fieldName]['error']) && $file_info[$fieldName]['error']!==0) {
            switch ($file_info[$fieldName]['error']) {
                case UPLOAD_ERR_INI_SIZE:
                    Ox_Logger::logError('The uploaded file exceeds the upload_max_filesize directive in php.ini: ' . $file_info[$fieldName]['name']);
                    throw new Ox_AssetException('The uploaded file exceeds the maximum upload size.', 'UPLOAD_ERR_INI_SIZE');
                case UPLOAD_ERR_FORM_SIZE:
                    Ox_Logger::logError('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form: ' . $file_info[$fieldName]['name']);
                    throw new Ox_AssetException('The uploaded file exceeds the maximum file size.', 'UPLOAD_ERR_FORM_SIZE');
                case UPLOAD_ERR_PARTIAL:
                    Ox_Logger::logError('The uploaded file was only partially uploaded.: ' . $file_info[$fieldName]['name']);
                    throw new Ox_AssetException('The uploaded file was only partially uploaded.', 'UPLOAD_ERR_PARTIAL');
                case UPLOAD_ERR_NO_FILE:
                    Ox_Logger::logError('No file was uploaded: ' . $file_info[$fieldName]['name']);
                    throw new Ox_AssetException('No file was uploaded.', 'UPLOAD_ERR_NO_FILE');
                case UPLOAD_ERR_NO_TMP_DIR:
                    Ox_Logger::logError('Missing a temporary folder: ' . $file_info[$fieldName]['name']);
                    throw new Ox_AssetException('A temporary folder was missing preventing the upload.', 'UPLOAD_ERR_NO_TMP_DIR');
                case UPLOAD_ERR_CANT_WRITE:
                    Ox_Logger::logError('Failed to write file to disk: ' . $file_info[$fieldName]['name']);
                    throw new Ox_AssetException('The file could not be written to disk.', 'UPLOAD_ERR_CANT_WRITE');
                case UPLOAD_ERR_EXTENSION:
                    Ox_Logger::logError('A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help' . $file_info[$fieldName]['name']);
                    throw new Ox_AssetException('A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; the system administrator may want to examine the list of loaded extensions with phpinfo().', 'UPLOAD_ERR_EXTENSION');
            }
        }
        if (!file_exists($tmp_name) || !is_uploaded_file($tmp_name)) {
            return null;
        }

        $md5_file = md5_file($tmp_name);
        $doc = array(
            'original_name'=>$file_info[$fieldName]['name'],
            'type'=>$file_info[$fieldName]['type'],
            'size'=>$file_info[$fieldName]['size'],
            'md5'=> $md5_file
        );
        if($extra_fields !== null && !empty($extra_fields)) {
            array_merge($doc, $extra_fields);
        }
        $assets->insert($doc);
        return $doc;
    }

    /**
     * Return URL for the given asset
     * @param $asset
     * @return string
     */
    public abstract function createURI($asset);

    /**
     * Return the saved asset if it exists. to the user.
     * @param $uri string
     * @return mixed
     */
    public abstract function getAsset($uri);
}

require_once('asset_managers/Ox_LocalAsset.php');

