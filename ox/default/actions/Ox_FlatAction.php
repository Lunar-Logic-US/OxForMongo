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
 * This Routable Action allows you to build a directory of flat html files
 * that is directly passed (possibly insterted into a layout) back to these
 * client browser.
 *
 * There is current NO security on these files.
 *
 * @copyright Copyright (c) 2012 Lunar Logic LLC
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Ox_FlatAction implements Ox_Routable
{
    /**
     * Turns on object debug logging.
     */
    const DEBUG = FALSE;

    /**
     * Directory location of the construct
     * @var string
     */
    private $flat_file_dir;

    /**
     * Layout to use for these files.
     * @var string
     */
    private $layout;

    /**
     * Setup
     * @param string $file_dir
     * @param string $layout
     */
    public function __construct($file_dir=null, $layout = null)
    {
        $this->flat_file_dir = $file_dir;
        $this->layout = $layout;
    }

    /**
     * The "main" method for the action.
     *
     * This is called when the router has found a match and give controls to the action.
     * @param mixed[] $args
     * @return mixed
     */
    public function go($args)
    {
        if (self::DEBUG) Ox_Logger::logDebug('Ox_FlatAction Start to Perform:' . print_r($args,1));
        $path = $args[0];
        $a = explode('/', $path);
        if(isset($a[1])){
            $dir = $a[1];
        } else {
            print "<div class=\"error\">File not found (Flat Action) : " . $path . "</div>";
            Ox_Logger::logError('FlatAction Directory not specified in URL: ' . $path);
            return;
        }

        if(!$dir) {
            $dir = 'root';
        }

        $flat_file = 'index';
        if(isset($a[2]) && strlen($a[2])>0) {
            $flat_file = $a[2];
        }

        $file = DIR_CONSTRUCT . $dir . DIRECTORY_SEPARATOR . $flat_file;
        if($this->flat_file_dir) {
            $file = $this->flat_file_dir . DIRECTORY_SEPARATOR . $flat_file;
        }

        if(file_exists($file. '.html')) {
            $file .= '.html';

        } else if(file_exists($file . '.php')) {
            $file .= '.php';
        }
        if (self::DEBUG) Ox_Logger::logDebug('Ox_FlatAction go - file:' . $file);

        if(file_exists($file)) {
            ob_start();
            require_once($file);
            $content = ob_get_clean();
            if (!$this->layout) {
                print $content;
            } else {
                $layout_file = DIR_LAYOUTS . $this->layout . '.php';
                if (file_exists($layout_file)) {
                    require_once($layout_file);
                } else {
                    //There is no layout file
                    print $content;
                }
            }
        } else {
            print "FlatAction: File Not Found ({$file})";
            Ox_Logger::logError('FlatAction: Could not load: ' . $file);
        }

    }
}
