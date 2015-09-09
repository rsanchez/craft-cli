#!/usr/bin/env php
<?php

Phar::mapPhar('craft.phar');

$vendor_path = 'phar://'.__FILE__.'/vendor/';

require 'phar://'.__FILE__.'/src/bootstrap.php';

__HALT_COMPILER();
