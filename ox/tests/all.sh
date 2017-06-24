#!/bin/sh
find . -name '*Test.php' | xargs -n1 -t phpunit --colors
