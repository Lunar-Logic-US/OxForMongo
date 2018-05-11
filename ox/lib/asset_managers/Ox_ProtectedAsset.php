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
 * Version of an Asset that saves to a protected directory.
 * @package Ox_Assets
 */

class ProtectedAsset extends LocalAsset
{
    public function getAsset($uri)
    {
        $this->upload_path = DIR_UPLOAD_PROTECTED;
        $db = Ox_LibraryLoader::Db();

         Ox_LibraryLoader::loadCode('RestrictedAsset', array(DIR_APP . 'lib' . DIRECTORY_SEPARATOR));
        
        if(class_exists('RestrictedAsset')){
            if(method_exists('RestrictedAsset', 'accessGranted')) {
                if($access_granted === true) {
                   //$uri = Ox_Router::trimPrefix($uri,Ox_Dispatch::CONFIG_WEB_BASE_NAME);
                    $base_filename = pathinfo($uri, PATHINFO_FILENAME);

                    $assets = $db->getCollection('assets');
                    $asset = $assets->findOne(array('_id'=>new MongoId($base_filename)));
                    if(!$asset) {
                        header("HTTP/1.0 404 Not Found (Not in DB)");
                        Ox_Logger::logWarning('LocalAsset::getAsset File: '.$base_filename.' Not found in the database.');
                        exit(1);
                    }
                    if (!file_exists($this->upload_path . $base_filename)) {
                        Ox_Logger::logWarning('LocalAsset::getAsset File: '. $this->upload_path . $base_filename.' Not found on the filesystem.');
                        header("HTTP/1.0 404 Not Found (File Missing)");
                        exit(1);
                    }
                    $file_size = $asset['size'];

                    //-------------------------------------------------------------------------
                    //Process a partial file request from the client.
                    //-------------------------------------------------------------------------

                    if (isset($_SERVER['HTTP_RANGE'])) {
                        $partial_content = true;
                        $range = $_SERVER['HTTP_RANGE'];
                        if (preg_match('/^bytes/',$_SERVER['HTTP_RANGE'])) {
                            //we have it in the form bytes=number-number
                            //We need to remove the bytes=
                            $split = explode('=',$range);
                            $range = $split[1];
                        }
                        $range = explode("-", $range);

                        // Future-proof in case explode doesn't always create empty array element.
                        if (!isset($range[1])){
                            $range[1] = "";
                        }

                        if (strlen($range[0])==0 && strlen($range[1]) > 0) {
                            // -1000
                            if (self::DEBUG) Ox_Logger::logDebug('LocalAsset::getAsset range[0] empty | [1] set: ');
                            $offsetLast = $file_size -1;
                            $offsetFirst = $file_size-intval($range[0]);
                            if ($offsetFirst<0) $offsetFirst=0;
                        } elseif (strlen($range[0])>0 && strlen($range[1]) == 0) {
                            // 1000-
                            if (self::DEBUG) Ox_Logger::logDebug('LocalAsset::getAsset range[0] NOT empty | [1] NOT set: ');
                            $offsetFirst = intval($range[0]);
                            $offsetLast = $file_size-1;
                        } else {
                            // 0-1000
                            if (self::DEBUG) Ox_Logger::logDebug('LocalAsset::getAsset GENERAL CASE: ');
                            $offsetFirst = intval($range[0]);
                            $offsetLast = intval($range[1]);
                        }

                        //Idiot check -- can not go base the end of the file.
                        if ($offsetLast > $file_size) {
                            $offsetLast = $file_size-1;
                        }

                        if($offsetFirst>$offsetLast){
                            //unsatisfiable range
                            header("Status: 416 Requested range not satisfiable");
                            header("Content-Range: */$file_size");
                            exit;
                        }

                        $length = $offsetLast - $offsetFirst+1; //Offsets start a 0, so need to +1 for length
                        $data_size = $length;
                    } else {
                        $partial_content = false;
                        $offsetFirst = 0;
                        $length = $file_size;
                        $data_size = $file_size;
                        $md5_sum = $asset['md5'];
                    }

                    //-------------------------------------------------------------------------
                    //Get the data from the local file system
                    //-------------------------------------------------------------------------

                    //read the data from the file
                    $handle = fopen($this->upload_path . $base_filename, 'r');
                    if ($handle===false) {
                        Ox_Logger::logDebug('LocalAsset::getAsset Could not open the file: '. $base_filename);
                        header("HTTP/1.0 404 Not Found (Can not open file)");
                        exit(1);
                    }
                    $buffer = '';
                    $seekResult = fseek($handle, $offsetFirst);
                    if ($seekResult===-1) {
                        Ox_Logger::logDebug('LocalAsset::getAsset Could not seek the file: '. $base_filename . ' @ ' . $offsetFirst);
                        header("HTTP/1.0 404 Not Found (Can not seek in the file)");
                        exit(1);
                    }
                    $buffer = fread($handle, $length);
                    if ($partial_content) {
                        $md5_sum = md5($buffer);
                    }
                    fclose($handle);

                    //-------------------------------------------------------------------------
                    //Start the file output
                    //-------------------------------------------------------------------------

                    //Make sure magic quotes are off.  Otherwise php will add unwanted slashes.
                    @ini_set('magic_quotes_runtime', 0);

                    // send the headers and data
                    if ($partial_content) {
                        header("HTTP/1.1 206 Partial content");
                        header('Content-Range: bytes ' . $offsetFirst . '-' . $offsetLast . '/' . $file_size);
                    }
                    header("Content-Length: " . $data_size);
                    header("Accept-Ranges: bytes");
                    header('Content-Type: ' . $asset['type']);
                    header('Content-Disposition: filename="' . $asset['original_name'] . '"');
                    header("Content-md5: " . $md5_sum);
                    $fs = stat($this->upload_path . $base_filename);
                    header("ETag: ".sprintf('"%x-%x-%s"', $fs['ino'], $fs['size'],base_convert(str_pad($fs['mtime'],16,"0"),10,16)));
                    header("Connection: close");

                    //Send the file data
                    echo $buffer;
                    flush();
                    exit(0); 
                }
                else {
                    header("HTTP/1.0 403 Forbidden");
            Ox_Logger::logWarning('');
            exit(1);
                }
            }
        }
    }
}