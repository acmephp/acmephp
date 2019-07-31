<?php

$phar = new Phar('/tmp/acmephp.phar');
$phar->buildFromDirectory(__DIR__ . '/../');
$phar->compressFiles(Phar::GZ);
$phar->setDefaultStub('bin/acme');