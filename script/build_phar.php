<?php

$phar = new Phar('/tmp/acmephp.phar');
$phar->startBuffering();
$phar->buildFromDirectory(__DIR__ . '/../');
$phar->compressFiles(Phar::GZ);
$phar->setDefaultStub('bin/acme');
$phar->setSignatureAlgorithm(Phar::SHA256);
$phar->stopBuffering();
