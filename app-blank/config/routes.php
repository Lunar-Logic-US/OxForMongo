<?php
/**
 * These are the routes that are specific to your application.
 */

// Catch all
Ox_Router::add('/^\/(\w*)(\/\w*)?(\/[\w.\-_]*)?(\/[\w.\-_]*)?(\/[\w.\-_]*)?(\/\w*)?(\/\d*)?$/', new Ox_AssemblerAction());
