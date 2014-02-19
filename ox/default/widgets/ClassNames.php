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
 * A system action that assembles like files together.
 *
 * This is the action handler for Assembler Constructs.
 *
 * @copyright Copyright (c) 2012 Lunar Logic LLC
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 * @package Ox_Widgets
 */

/**
 * Widget ClassNames
 * This widget Allows you to set class names for a specific element to be output in the layout
 * (or anywhere).
 *
 * To set:
 * <code>
 * $widget_handler=Ox_LibraryLoader::Widget_Handler();
 * $widget_handler->ClassNames->set("home","body");
 * </code>
 *
 * To display:
 * <code>
 * WidgetHandler::ClassNames();
 * </code>
 * @package Ox_Widgets
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
     * @param $classNames
     * @return string
     */
    public function set($classNames) {
        $this->_classNames = trim(trim($this->_classNames) . ' ' . trim($classNames));
    }
}