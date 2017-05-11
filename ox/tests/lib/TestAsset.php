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
 * Test version of an asset.
 */
class TestAsset extends Ox_Asset
{
    public function save($file_info, $fieldName = 'file', array $extra_fields = array())
    {
        $doc = parent::save($file_info);
        return $doc;

    }

    public function createURI($asset)
    {
        return $asset;
    }

    public function getAsset($uri)
    {
        return $uri;
    }
}
