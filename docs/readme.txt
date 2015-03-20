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

Ox Framework

This framework is setup to be tightly coupled with Mongo to gain the fexibility advantages when building websites.  The idea is that you are building user interface constructs for the user to interact with the database.  A construct is a major UI piece what coherently works together.  For example, if you are building an inventory system, an inventory entry screen may be one construct, a audit screen may be aother.  Notice that both will deal with the inventory collections and supporting data, but they are single pieces that a user will directly interface with.  As part of those pieces. there may be ajunct screens or ajax calls that would be assoiated with that construct.

Installation
Required
    Web Service (Apache, IIS, etc.)
    PHP
    MongoDB
    Pear MongoDB driver

Optional
    PHP Unit
    XDebug
    
Installing PHPUnit:

pear channel-discover pear.phpunit.de
pear channel-discover components.ez.no
pear channel-discover pear.symfony-project.com
pear install phpunit/PHPUnit

Once you have PHP, MongoDB, Apache, and the Pear MongoDB driver installed all you have to do is point apache at /app-blank/webroot and your new site is up and running! Currently the webroot of the site must be /app-blank/webroot.

Advanced Install
You can set up your projects in Ox to be in separate locations. In the file structure below the "app" location could be housed in a separate repository where appropriate, making updates to the framework modular. To do this put your application code where you want it. Edit the /config/framework.php file to point DIR_FRAMEWORK to the Ox directory.

Currently in the framework there are three types of constructs, 1) an assembly based construct, 2) a flat php construct and 3) a flat html construct.

The framework has the following directory structure:

/ --+-- app  (the application code)
    |   +--- config (files used to configure the app)
    |   +--- constructs (directories on major UI pieces)
    |   +    +--- _common  (common code need between constructs)
    |   |    |    +--- layouts  (whole page templates)
    |   |    |    +--- widgets  (functional peices that are common between constructs)
    |   |    +--- root (the default root of the site)
    |   +--- tmp (location for application tmp files
    |   +--- webroot (the actual webroot of the site -- storage for CSS, JS, ...)
    |        +--- assests (these are assets used by the constructs)
    |        +--- css
    |        +--- images
    |        +--- js
    +-- ox (the framework code)
    |   +--- defaults
    |   +--- docs
    |   +--- lib (the default framework libraries)
    *-- plugins (plugins)







