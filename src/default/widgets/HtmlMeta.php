<?php
/**
 *    Copyright (c) 2014 Lunar Logic LLC
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
 * @copyright Copyright (c) 2014 Lunar Logic LLC
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 * @package Ox_Widgets
 */

/**
 * Widget HtmlMeta
 * This widget Allows you to set meta tags from anywhere in your code and then have it displayed
 * in a layout (or anywhere)
 *
 * To set:
 * $widget_handler=Ox_LibraryLoader::Widget_Handler();
 * $widget_handler->HtmlMeta->set('title',"Lunar Logic");
 * $widget_handler->HtmlMeta->set('description',"Lunar Logic is a fantastic multi-technology company.");
 *
 * To display:
 * <code>
 * WidgetHandler::HtmlMeta();
 * </code>
 * @package Ox_Widgets
 */

class HtmlMeta implements Ox_Widget {
    /**
     * The holds the current meta tags.
     */
    private $_metaTags;

    /**
     * Load a default title from the config or set a default title
     */
    public function __construct(){
        //setup default tags HTML5 must have a title tag....
        $title=Ox_LibraryLoader::Config_Parser()->getAppConfigValue('default_title');
        if (empty($title)) {
            $title = 'Ox Framework';
        }
        $this->_metaTags = array('title'=>$title);
    }

    /**
     * Output or return the meta tags
     * @param bool $return_string
     * @return string
     */
    public function render($return_string = FALSE) {
        $output = '';
        foreach ($this->_metaTags as $tag => $value) {
            if ($tag == 'title' && !empty($value)) {
                $output .=  "<title>{$value}</title>\n";
            } elseif ($tag == 'canonical'  && !empty($value)) {
                $output .=  "<link rel=\"$tag\" href=\"{$value}\"/>\n";
            } elseif (!empty($value)) {
                $output .=  "<meta name=\"$tag\" content=\"{$value}\"/>\n";
            }
        }

        if ($return_string === FALSE) {
            print $output;
            return TRUE;
        } else {
            return $output;
        }
    }

    /**
     * Set given the meta tag.
     *
     * @param $metaTag
     * @param $value
     * @return string
     */
    public function set($metaTag,$value) {
        $this->_metaTags[$metaTag] = $value;
    }
}


