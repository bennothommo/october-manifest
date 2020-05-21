<?php

require_once __DIR__ . '/lib/Cli.php';
require_once __DIR__ . '/lib/Install.php';
require_once __DIR__ . '/lib/Compare.php';
require_once __DIR__ . '/lib/Manifest.php';

$cli = new \October\Manifest\Cli;

$manifest = $argv[1] ?? null;
$directory = $argv[2] ?? null;

if (empty($manifest)) {
    $cli->finish('Usage: compare.php [manifest file] [directory to October]');
}
if (empty($directory)) {
    $cli->error('A directory to the October CMS installation must be specified.');
}

$install = new \October\Manifest\Install($directory);
$compare = new \October\Manifest\Compare($manifest);
$build = $compare->compare($install);

if (is_null($build)) {
    $cli->finish('Unable to determine the October CMS version.');
}

$cli->finish('You are running a'
    . (($build['modified']) ? ' ' : 'n un') . 'modified version of build '
    . $build['build']
    . ' of October CMS.'
    . (($build['modified']) ? ' (Probability: ' . $build['probability'] . '%)' : ''));
