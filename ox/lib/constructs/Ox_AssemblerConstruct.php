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
 * Base class for assemblers.
 *
 * This class facilitates interaction with Ox, providing some code organization
 */
class Ox_AssemblerConstruct
{
    /**
     * Layout this construct will use.
     * @var string
     */
    public $layout = 'default';

    /**
     * Associated array of method and roles that can access it.
     *
     * <code>
     * $this->roles = array(
     *     'index' => array('anonymous','user','admin'),
     *     'edit' => array('admin'),
     * );
     * </code>
     *
     * su always has access.
     *
     * @var array
     */
    public $roles = array();

    /**
     * The current template to render.
     *
     * Defaults to the same name as the method.
     *
     * @var string
     */
    public $template  = null;

    /**
     * Associated Array of variable to be present in the template
     *
     * Use the set method to register those variables.
     *
     * @var array
     */
    public $template_vars = array();

    /**
     * The default roles associated with this assembler
     */
    public $default_roles = array();

    /**
     * Easy access to the DB for in the assembler.
     * @var bool|Ox_MongoSource
     */
    public $db = false;
    /**
     * Easy access to the DB for in the assembler.
     * @var bool|Ox_User
     */

    public $user = false;

    /**
     * Sets a variable to be available to the view.
     *
     * Example uses:
     * <code>
     * $this->set( 'variableName', $someVariable );  // The contents of $someVariable will be available in the view
     *                                               // as $variableName.
     * </code>
     * <code>
     * $this->set( array(                            // Makes controller variables $myVar1 and $myVar2 available to
     *                 'viewVar1' => $myVar1,        // the view as $viewVar1 and $viewVar2 respectively.
     *                 'viewVar2' => $myVar2 ));
     * </code>
     * </code>
     * $this->set( compact( 'myVar1', 'myVar2' ) );  // Makes $myVar1 and $myVar2 in the controller context available
     *                                               // in the view context under the same names.
     * </code>
     * Note:
     *     These variables are currently only available during the normal controller-specific layout processing.  They
     * are *not* available to the shared layout files such as default.php, header.php, etc.
     *
     * @param string $key the string to use as a key or an array of string => value pairs to be made available to the view.
     * @param mixed $value the value to assign to the $key if it is a string, ignored if $key is an array.
     */
    public function set( $key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $innerKey => $innerValue) {
                $this->template_vars[$innerKey] = $innerValue;
            }
        } else {
            $this->template_vars[$key] = $value;
        }
    }
    
    /**
     * Extracts the template variables and requires the passed in file
     *
     * @param string $template_file The template file to be rendered.
     *
     */
    public function renderTemplate($template_file)
    {
        extract($this->template_vars);
        require($template_file);
    }
    
}

