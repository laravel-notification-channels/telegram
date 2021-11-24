<?php

$finder = PhpCsFixer\Finder::create()->in(__DIR__);

$config = new PhpCsFixer\Config();
$config->setRiskyAllowed(true)
    ->setRules([
        '@PhpCsFixer' => true,
    ])
    ->setFinder($finder);

return $config;
