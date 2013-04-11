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
 * Widget JS
 * This widget Allows you to add javascript files from anywhere in your code and then have it added
 * in a layout (or anywhere)
 *
 * To set:
 * global $widget_handler;
 * $widget_handler->JS->add("tag-it.js"); // set a simple js from /js/tag-it.js
 * $widget_handler->JS->add("jquery.min.js",'http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/','text/javascript','utf-8');
 *
 * To display:
 * <?php Ox_WidgetHandler::JS(); ?>
 */

class JS implements Ox_Widget {
    /**
     * Array of javascript filenames.
     */
    private $_js_file_list = array();
    private $_js_jQuery_ready_list = array();
    private $_js_script_list = array();
    private $_pageBase = '';

    /**
     * Create script tags for the list of js files.
     *
     * @param bool $return_string
     * @return string
     */
    public function render($return_string = FALSE)
    {
        $output = '';
        if (!empty($this->_pageBase)) {
            $output .= <<<JS
<script>
    var pageBase = '{$this->_pageBase}';
</script>

JS;
        }

        $appWebBase = Ox_LibraryLoader::Config_Parser()->getAppConfigValue(Ox_Dispatch::CONFIG_WEB_BASE_NAME);
        foreach ($this->_js_file_list as $js_file => $js_options) {
            $type = '';
            if (isset($js_options['type']) && $js_options['type']!==FALSE) {
                $type = " type=\"{$js_options['type']}\" ";
            }
            $charset = '';
            if (isset($js_options['charset']) && $js_options['charset']!==FALSE) {
                $charset = " charset=\"{$js_options['charset']}\" ";
            }

            $file = $js_file;
            $directory = $appWebBase . $js_options['directory'];


            $output .= "<script src=\"{$directory}{$file}\"{$type}{$charset}></script>\n";
        }
        
        if (count($this->_js_script_list)) {
            $output .= "<script>\n";
            foreach ($this->_js_script_list as $id => $script) {
                $output .= "    <!-- Script ID: {$id} -->\n";
                $output .= "    " . $script;
                $output .= "\n";
            }
            $output .= "</script>\n";
        }

        if (count($this->_js_jQuery_ready_list)) {
            $output .= "<script>\n";
            $output .= '$(document).ready(function() {' . "\n";
            foreach ($this->_js_jQuery_ready_list as $id => $script) {
                $output .= "    //<!-- Script ID: {$id} -->\n";
                $output .= "    " . $script;
                $output .= "\n";
            }
            $output .= "});\n";
            $output .= "</script>\n";
        }


        if ($return_string === FALSE) {
            print $output;
        } else {
            return $output;
        }
    }

    /**
     * Add file to the top of the JS list.
     *
     * @param $file
     * @param string $directory
     * @param bool $type
     * @param bool $charset
     * @return void
     * @internal param bool $media
     */
    public function add_to_top($file,$directory='/js/',$type=FALSE,$charset=FALSE)
    {
        $options = array('directory'=>$directory,'type'=>$type,'charset'=>$charset);
        if (isset($this->_js_file_list[$file])) {
            $this->_js_file_list[$file] = $options;
        } else {
            $new = array($file => $options);
            $this->_js_file_list = array_merge($new,$this->_js_file_list);
        }
    }

    /**
     * Add file to the bottom of the JS list.
     *
     * @param $file
     * @param string $directory
     * @param bool $type
     * @param bool $charset
     * @return void
     * @internal param bool $media
     */
    public function add_to_bottom($file,$directory='/js/',$type=FALSE,$charset=FALSE)
    {
        $options = array('directory'=>$directory,'type'=>$type,'charset'=>$charset);
        $new = array($file => $options);
        //This will overwrite if the same file is used twice.
        $this->_js_file_list = array_merge($this->_js_file_list,$new);
    }

    /**
     * Add file to the bottom of the JS list.
     *
     * @param $file
     * @param string $directory
     * @param bool $type
     * @param bool $charset
     * @return void
     * @internal param bool $media
     */
    public function add($file,$directory='/js/',$type=FALSE,$charset=FALSE)
    {
        $this->add_to_bottom($file,$directory,$type,$charset);
    }

    public function setPageBase($pageBase)
    {
        $appWebBase = Ox_LibraryLoader::Config_Parser()->getAppConfigValue(Ox_Dispatch::CONFIG_WEB_BASE_NAME);
//        if (!empty($appWebBase)) {
//            $appWebBase .= '/';
//        }
        $this->_pageBase = $appWebBase . $pageBase;
    }
    
    public function addHeaderScript ($id,$script) {
        $new = array($id=>$script);
        //This will overwrite if the same script id is used twice.
        $this->_js_script_list = array_merge($this->_js_script_list,$new);
        
    }
    
    public function addjQueryReady ($id,$script) {
        $new = array($id=>$script);
        //This will overwrite if the same script id is used twice.
        $this->_js_jQuery_ready_list = array_merge($this->_js_jQuery_ready_list,$new);
    }
    
}