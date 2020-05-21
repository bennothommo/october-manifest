<?php

require_once __DIR__ . '/lib/Cli.php';
require_once __DIR__ . '/lib/Manifest.php';
require_once __DIR__ . '/lib/Install.php';

$cli = new \October\Manifest\Cli;

$output = $argv[1] ?? null;

if (empty($output)) {
    $cli->finish('Usage: generate.php [output file]');
}

$manifest = new \October\Manifest\Manifest;

if (!is_dir(__DIR__ . '/tmp')) {
    mkdir(__DIR__ . '/tmp', 0775);
}

// Get all builds since build 420
$build = 420;
$found = true;

while ($found === true) {
    // Download version from GitHub
    $cli->out('Processing build ' . $build);
    $cli->out('  - Downloading...');

    if (file_exists(__DIR__ . '/tmp/build-' . $build . '.zip') || is_dir(__DIR__ . '/tmp/' . $build . '/')) {
        $cli->overwrite('Already downloaded.');
    } else {
        $zipFile = @file_get_contents('https://github.com/octobercms/october/archive/v1.0.' . $build . '.zip');
        if (empty($zipFile)) {
            $cli->overwrite('Not found.');
            $found = false;
            break;
        }

        file_put_contents(__DIR__ . '/tmp/build-' . $i . '.zip', $zipFile);

        $cli->overwrite('Done.');
    }

    // Extract version
    $cli->out('  - Extracting...');
    if (is_dir(__DIR__ . '/tmp/' . $build . '/')) {
        $cli->overwrite('Already extracted.');
    } else {
        $zip = new ZipArchive;
        if ($zip->open(__DIR__ . '/tmp/build-' . $build . '.zip')) {
            $toExtract = [];
            $paths = [
                'october-1.0.' . $build . '/modules/backend/',
                'october-1.0.' . $build . '/modules/system/',
            ];

            // Only get necessary files from the modules directory
            for ($i = 0; $i < $zip->numFiles; ++$i) {
                $filename = $zip->statIndex($i)['name'];
                $matches = false;

                foreach ($paths as $path) {
                    if (strpos($filename, $path) === 0) {
                        $toExtract[] = $filename;
                        break;
                    }
                }
            }

            if (!count($toExtract)) {
                $cli->overwrite('Unable to get valid files for extraction.');
                $cli->error('Cancelled.');
            }

            $zip->extractTo(__DIR__ . '/tmp/' . $build . '/', $toExtract);
            $zip->close();

            // Remove ZIP file
            unlink(__DIR__ . '/tmp/build-' . $build . '.zip');
        } else {
            $cli->overwrite('Unable to extract.');
            $cli->error('Cancelled.');
        }

        $cli->overwrite('Done.');
    }

    // Add build to manifest
    $cli->out('  - Adding to manifest...');
    $install = new \October\Manifest\Install(__DIR__ . '/tmp/' . $build . '/october-1.0.' . $build);
    $manifest->addBuild($build, $install);
    $cli->overwrite('Done.');

    // Loop for next build
    ++$build;
}

// Generate manifest
$cli->out('Generating manifest...');
file_put_contents($output, $manifest->generate());
$cli->overwrite('Done.');

$cli->finish('Completed.');
