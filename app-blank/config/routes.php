<?php
/**
 * These are the routes that are specific to your application.
 */

//Catches /root; /root/; /root/filename; /root/filename.php; /root/filename.html; /root/index.html
Ox_Router::add('/^\/root\/?(\w+(\.(html|php))?)?$/', new Ox_FlatAction());
// Catch all
Ox_Router::add('/^\/(\w*)(\/\w*)?(\/\d*)?(\/\d*)?$/', new Ox_AssemblerAction(null, null, null));
