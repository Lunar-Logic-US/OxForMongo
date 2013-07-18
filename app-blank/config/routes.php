<?php
/**
 * These are the routes that are specific to your application.
 */

Ox_Router::add(WEB_ROOT, new Ox_FlatAction('root'));

//Catches /root; /root/; /root/filename; /root/filename.php; /root/filename.html; /root/index.html
Ox_Router::add('/^\/root\/?(\w+(\.(html|php))?)?$/', new Ox_FlatAction());
// Catch all
Ox_Router::add('/^\/(\w*)(\/\w*)?(\/[\w.\-_]*)?(\/[\w.\-_]*)?(\/[\w.\-_]*)?(\/\w*)?(\/\d*)?$/', new Ox_AssemblerAction());
