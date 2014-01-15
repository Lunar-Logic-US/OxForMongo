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
 */

/**
 * Widget ClassNames
 * This widget Allows you to set class names for a specific element to be output in the layout
 * (or anywhere).
 *
 * To set:
 * global $widget_handler;
 * $widget_handler->ClassNames->set("home","body");
 *
 * To display:
 * <?php WidgetHandler::ClassNames(); ?>
 */

class ClassNames implements Ox_Widget {
    /**
     * Class names array
     */
    private $_classNames;

    /**
     * Initialize the class names member.
     */
    public function __construct(){
        $this->_classNames = '';
    }

    /**
     * Output or return the class names
     * @param bool $return_string
     * @return string
     */
    public function render($return_string = FALSE) {
        if ($return_string === FALSE) {
            print $this->_classNames;
            return TRUE;
        } else {
            return $this->_classNames;
        }
    }

    /**
     * Set the class names to be output in elements in the site layout.
     *
     * @param $title
     * @internal param bool $return_string
     * @return string
     */
    public function set($classNames) {
        $this->_classNames = trim(trim($this->_classNames) . ' ' . trim($classNames));
    }
}