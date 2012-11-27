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
 *This represents an action to retrieve saved file assets.
 *
 * @copyright Copyright (c) 2012 Lunar Logic LLC
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */
class Ox_AssetAction implements Ox_Routable
{
    /**
     * Turns on object debug logging.
     */
    const DEBUG = FALSE;

    /**
     * The "main" method for the action.
     *
     * This is called when the router has found a match and give controls to the action.
     * @param mixed[] $args
     * @return mixed
     */
    public function go($args)
    {
        $filename = $args[1];
        $assets_helper = new LocalAsset('/assets/');
        $assets_helper->getAsset($filename);
    }
}
