<?php
echo 'zip=' . (extension_loaded('zip') ? 'yes' : 'no') . PHP_EOL;
echo 'class=' . (class_exists('ZipArchive') ? 'yes' : 'no') . PHP_EOL;
echo 'php=' . PHP_VERSION . PHP_EOL;
