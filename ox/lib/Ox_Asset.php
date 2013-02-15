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
 * Contains classes to abstract saving and retrieval of assets.
 *
 * This class hierarchy assumes that files will be uploaded via a webform.
 */
abstract class Ox_Asset
{
    /**
     * Save the document info into the database and return it.
     *
     * Inherited object should all this to save the information into the database.
     * <code>
     * $doc = parent::save($file_info);
     * </code>
     * You then use the information in $doc to the actual save.
     *
     * @param $file_info array
     * @return array|null
     */
    public function save($file_info, $fieldName='file')
    {
        $db = Ox_LibraryLoader::Db();
        $assets = $db->getCollection('assets');
        $tmp_name = $file_info[$fieldName]["tmp_name"];
        //var_dump($file_info);
        if (!file_exists($tmp_name)) {
            return null;
        }
        $md5_file = md5_file($tmp_name);
        $doc = array('original_name'=>$file_info[$fieldName]['name'],
                              'type'=>$file_info[$fieldName]['type'],
                              'size'=>$file_info[$fieldName]['size'],
                              'md5'=> $md5_file
                            );
        //var_dump($doc);
        //print "xxxxxxxxxxxxxxxxxxxxxxxxx";
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

