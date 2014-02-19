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
 * @package Ox_Widgets
 */

/**
 * Interface for building widgets.
 *
 * Widgets are object that are used to render either common interfaces or
 * display code that needs to be set in a construct that need to be displayed in a layout.
 *
 * @package Ox_Widgets
 */
interface Ox_Widget
{
    /**
     * Renders the widget.
     *
     * This should either output the widget to the screen OR puts what it generates into a string and return it,
     * depending on the value of $return_string).
     *
     * @param bool $return_string Echo out the to the display or return a string.
     * @return string
     */
    public function render($return_string=FALSE);
}