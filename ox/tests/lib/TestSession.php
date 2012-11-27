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

require_once(DIR_FRAMELIB  . 'Ox_Session.php');
/**
 * This is a dummy session class to avoid session startup error, which cause problems during testing.
 */
class TestSession extends Ox_Session {
    private static $_instance = null;
    private static $_storedValues = array();
    public $started = false;

    public static function getInstance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
    }

    public function debug() {
    }

    public function start() {
        $this->started = true;
    }

    public function stop() {
        $this->started = false;
    }

    function set($key, $value) {
        $this->_storedValues[$key]=$value;
    }

    function get($key) {
        if (isset($this->_storedValues[$key])) {
            return $this->_storedValues[$key];
        }
        return null;
    }

    public function isExpired() {
    }

    public function getSessionTimeRemaining() {
    }

    public function update() {
    }
}
