<?php
namespace October\Manifest;

/**
 * Stores the manifest information.
 *
 * @author Ben Thomson
 */
class Manifest
{
    /**
     * Array of builds, with files and hashes
     *
     * @var array
     */
    protected $builds = [];

    /**
     * Constructor
     *
     * @param string $manifest Manifest file to load
     */
    public function __construct(string $manifest = null)
    {
        if (isset($manifest)) {
            $this->load($manifest);
        }
    }

    /**
     * Loads a manifest file.
     *
     * @param string $manifest
     * @throws Exception If the manifest is invalid, or cannot be parsed.
     */
    public function load(string $manifest)
    {
        $data = json_decode(file_get_contents(realpath($manifest)), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Unable to decode manifest JSON data. JSON Error: ' . json_last_error_msg());
        }
        if (!isset($data['manifest'])) {
            throw new \Exception('File "' . $manifest . '" does not appear to be a valid manifest file.');
        }

        foreach ($data['manifest'] as $build) {
            $this->builds[$build['build']] = [
                'md5sum' => $build['md5sum'],
                'files' => $build['files'],
            ];
        }

        return $this;
    }

    /**
     * Adds a build to the manifest
     *
     * @param integer $build Build number
     * @param Install $install The installation of October CMS at that particular build
     * @return void
     */
    public function addBuild(int $build, Install $install): void
    {
        $this->builds[$build] = [
            'md5sum' => $install->getChecksum(),
            'files' => $this->processChanges($build, $install->getFiles()),
        ];
    }

    /**
     * Gets all builds.
     *
     * @return array
     */
    public function getBuilds(): array
    {
        return $this->builds;
    }

    /**
     * Generate the JSON manifest.
     *
     * @throws Exception If no builds have been added to the manifest.
     * @return string
     */
    public function generate(): string
    {
        if (!count($this->builds)) {
            throw new \Exception('No builds have been added to the manifest.');
        }

        $json = [
            'manifest' => [],
        ];

        foreach ($this->builds as $build => $details) {
            $json['manifest'][] = [
                'build' => $build,
                'md5sum' => $details['md5sum'],
                'files' => $details['files'],
            ];
        }

        return json_encode($json, JSON_PRETTY_PRINT);
    }

    /**
     * Gets the file list state at a certain build.
     *
     * Will list all current files and their sums.
     *
     * @param integer $build
     * @throws \Exception If the specified build has not been added
     * @return array
     */
    public function getState(int $build): array
    {
        if (!isset($this->builds[$build])) {
            throw new \Exception('The specified build has not been added.');
        }

        $state = [];

        foreach ($this->builds as $number => $details) {
            if (isset($details['files']['added'])) {
                foreach ($details['files']['added'] as $filename => $sum) {
                    $state[$filename] = $sum;
                }
            }
            if (isset($details['files']['modified'])) {
                foreach ($details['files']['modified'] as $filename => $sum) {
                    $state[$filename] = $sum;
                }
            }
            if (isset($details['files']['removed'])) {
                foreach ($details['files']['removed'] as $filename) {
                    unset($state[$filename]);
                }
            }

            if ($number === $build) {
                break;
            }
        }

        return $state;
    }

    /**
     * Determines file changes between the specified build and the previous build.
     *
     * Will return an array of added, modified and removed files.
     *
     * @param integer $build
     * @param array $files
     * @return array
     */
    protected function processChanges(int $build, array $files): array
    {
        // Previous build
        $previousBuild = $this->builds[$build - 1] ?? null;

        // If no previous build exists, all files are added
        if (is_null($previousBuild)) {
            return [
                'added' => $files
            ];
        }

        // Only save files if they are changing the "state" of the manifest (ie. the file is modified, added or removed)
        $state = $this->getState($build - 1);
        $added = [];
        $modified = [];

        foreach ($files as $file => $sum) {
            if (!isset($state[$file])) {
                $added[$file] = $sum;
                continue;
            } else {
                if ($state[$file] !== $sum) {
                    $modified[$file] = $sum;
                }
                unset($state[$file]);
            }
        }

        // Any files still left in state have been removed
        $removed = array_keys($state);

        $changes = [];
        if (count($added)) {
            $changes['added'] = $added;
        }
        if (count($modified)) {
            $changes['modified'] = $modified;
        }
        if (count($removed)) {
            $changes['removed'] = $removed;
        }

        return $changes;
    }
}
