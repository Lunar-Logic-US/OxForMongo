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
 * @copyright Copyright (c) 2017 Lunar Logic LLC
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 * @package Ox_Widgets
 */

/**
 * Widget ReCaptcha
 * This widget Allows you to add a Google ReCaptcha widget to any page,
 * as well as verify the response from the form submission
 *
 * To render a widget:
 * <code>
 * $widget_handler=Ox_LibraryLoader::Widget_Handler();
 * $captcha = $widget_handler->ReCaptcha->create(array(
 *   'siteKey' => 'site-key-value'
 *)); // Create the captcha handler
 * $captcha->render(); // Render the widget
 *</code>
 * To verify a widget form response:
 * $widget_handler=Ox_LibraryLoader::Widget_Handler();
 * $captcha = $widget_handler->ReCaptcha->create(array(
 *   'siteSecret' => 'site-secret-value'
 *)); // Create the captcha handler
 * $response = $captcha->verify($_POST['g-recaptcha-response']); // Verify the token
 * if ($response->isSuccess())... //Handle
 *</code>
 *
 * @package Ox_Widgets
 */
class ReCaptcha implements Ox_Widget {
    private $options = array(
      'badge' => 'inline',
      'cssClasses' => '',
      'callback' => ''
    );
    private $isWidgetInstance = false;
    private static $instanceCreated = false;

    function __construct(array $options = array()) {
      if (!self::$instanceCreated) {
        self::$instanceCreated = true;
        $this->isWidgetInstance = true;
      } else {
        $this->options = array_merge($this->options, $options);
      }
    }

    public function create($options) {
      return new ReCaptcha($options);
    }

    public function render($return_string=FALSE) {
      // Ensure we are not in the base singleton
      if ($this->isWidgetInstance) {
        throw new Exception("Cannot call render() on ReCaptcha widget instance. Use ReCaptcha->create() instead.");
      }

      // Get the site key and verify it is valid
      $siteKey = isset($this->options['siteKey']) ? $this->options['siteKey'] : false;
      if (!$siteKey || !is_string($siteKey)) {
        throw new Exception("Cannot render ReCaptcha: option 'siteKey' must be a string!");
      }

      // Get the remaining render options
      $badge = $this->options['badge'];
      $callback = $this->options['callback'];
      $cssClasses = $this->options['cssClasses'];

      // Generate the value
      $value = <<<HTML
       <div data-sitekey="{$siteKey}" data-badge="{$badge}" data-callback="{$callback}" class="g-recaptcha {$cssClasses}"></div>
HTML;
      // Return or echo
      if($return_string) {
        return $value;
      } else {
        echo $value;
      }
    }
    
    public function verify($responseToken, $remoteIp = NULL) {
      // Ensure we are not in the base singleton
      if ($this->isWidgetInstance) {
        throw new Exception("Cannot call validate() on ReCaptcha widget instance. Use ReCaptcha->create() instead.");
      }
      $secret = isset($this->options['secretKey']) ? $this->options['secretKey'] : false;
      if (!$secret || !is_string($secret)) {
        throw new Exception("Cannot verify ReCaptcha: option 'secretKey' must be a string!");
      }

      $recaptcha = new \ReCaptcha\ReCaptcha($secret);
      return $recaptcha->verify($responseToken, $remoteIp);
    }
}
