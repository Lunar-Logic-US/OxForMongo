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
 * Class to manage Widgets
 *
 * This primarily allows collections to change the page rendering in templates.
 */
class Ox_WidgetHandler
{
    /**
     * Directory path for the application widgets
     * @var string
     */
    private static $_dirAppWidgets;
    /**
     * Directory path fro the default Ox widgets
     * @var string
     */
    private static $_dirDefaultWidgets;
    /**
     * Instantiated Class
     * @var null
     */
    private static $_instance = NULL;
    /**
     * List of loaded widget (for caching)
     * @var array
     */
    private static $_widgets = array();
    /**
     * Path to uses with the Library Loader.
     * @var array
     */
    private static $_widgetPath = array();

    /**
     * Singleton pattern function used for object instantiation.
     *
     * The singleton pattern ensures that there will only be one instantiation of this
     * class per session. Creates the instance.
     * 
     * @return null|Ox_WidgetHandler
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        Ox_LibraryLoader::loadCode('Ox_Widget',array(OX_FRAMEINTERFACE));
        return self::$_instance;
    }
    
    /**
     * PRIVATE constructor used for singleton pattern.
     *
     * Only one object of this class allowed.
     * Initialization of members occurs here.
     */
    private function __construct()
    {
        self::$_dirAppWidgets = DIR_COMMON . 'widgets' . DIRECTORY_SEPARATOR;
        self::$_dirDefaultWidgets = OX_FRAME_DEFAULT . 'widgets' . DIRECTORY_SEPARATOR;
        self::$_widgetPath = array(self::$_dirAppWidgets,self::$_dirDefaultWidgets);
    }

    /**
     * MAGIC Return the widget using $widgetHandler->WidgetClass Returns WidgetClass
     *
     * @param $name string
     * @return null|Ox_Widget
     */
    public function __get($name)
    {
        return $this->getWidget($name);
    }

    /**
     * Return the widget with the given class name
     *
     * @param $name string
     * @return null|Ox_Widget
     */
    public static function getWidget($name)
    {
        try {
            self::_load($name);
            return self::$_widgets[$name];
        } catch (Exception $e) {
            print "BROKEN Widget {$name}: Could not load or call the display function.";
        }
        return null;
    }

    /**
     * Call the render function on the given widget.
     * Renders the widget to the screen or optionally returns it as a string.
     *
     * @param $name string
     * @param bool $return_as_string
     * @return string
     */
    public static function renderWidget($name,$return_as_string=FALSE)
    {
        try {
            self::_load($name);
            return self::$_widgets[$name]->render($return_as_string); //current simply set for outputs (not returning a string)
        } catch (Exception $e) {
            print "BROKEN Widget {$name}: Could not load or call the render function.";
        }
        return '';
    }

    /**
     * Load (lazy) the widget and add to the static member array of widgets.
     *
     * @param string $name The name of the widget to load.
     * @param boolean $reset Ability to reload the widget manually, defaults to false.
     */
    private static function _load($name,$reset=FALSE)
    {
        if ($reset || !isset(self::$_widgets[$name])) {
            Ox_LibraryLoader::loadCode($name,self::$_widgetPath);
            self::$_widgets[$name] = new $name;
        }
    }

    /**
     * Reload the a widget from scratch
     * @param $widget_name
     */
    public static function reset($widget_name)
    {
        try {
            self::_load($name,TRUE);
        } catch (Exception $e) {
            print "BROKEN Widget {$name}: Could not load or call the display function.";
        }
    }
    
    /**
     * Render the Widget with with Method Name eg: Ox_WidgetHandler::CSS()
     * The difference between the static magic function and __get is that __get returns
     * the widget object whereas __callStatic renders the widget.
     * 
     * @param $method_name string
     * @param $arguments array
     * @return string
     */

    public static function __callStatic($method_name,$arguments)
    {
        $return_as_string = FALSE;
        if (isset($arguments[0]) && $arguments[0]===TRUE ) {
           $return_as_string = TRUE;
        }
        return self::renderWidget($method_name,$return_as_string);
    }
}