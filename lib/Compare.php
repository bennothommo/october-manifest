<?php
namespace October\Manifest;

/**
 * Compares an installation with the manifest to find an October CMS version.
 *
 * @author Ben Thomson
 */
class Compare
{
    /** @var array The manifest data */
    public $manifest;

    /**
     * Constructor.
     *
     * @param string $manifest
     */
    public function __construct(string $manifest = '')
    {
        if (!empty($manifest)) {
            $this->setManifest($manifest);
        }
    }

    /**
     * Sets the manifest to compare against.
     *
     * @param string $manifest
     * @throws Exception If the manifest is invalid, or cannot be parsed.
     */
    public function setManifest(string $manifest)
    {
        $this->manifest = new Manifest($manifest);
    }

    /**
     * Compares an October CMS installation against the manifest to find the version installed.
     *
     * @param Install $install
     * @throws Exception If the manifest file is not specified.
     * @return array|null Will return an array with the build, modified state and the probability that it is the
     *  version specified. If the detected version does not look like a likely candidate, this will return null.
     */
    public function compare(Install $install)
    {
        if (empty($this->manifest)) {
            throw new \Exception('A manifest file must be specified.');
        }

        $sum = $install->getChecksum();

        // Look for an unmodified version
        foreach ($this->manifest->getBuilds() as $build => $details) {
            if ($details['md5sum'] === $sum) {
                return [
                    'build' => $build,
                    'modified' => false,
                    'probability' => 100
                ];
            }
        }

        // If we could not find an unmodified version, try to find the closest version and assume this is a modified
        // install.
        $buildMatch = [];

        foreach ($this->manifest->getBuilds() as $build => $details) {
            $state = $this->manifest->getState($build);
            $filesExpected = count($state);
            $filesFound = [];
            $filesChanged = [];

            foreach ($install->getFiles() as $file => $sum) {
                // Unknown new file
                if (!isset($state[$file])) {
                    $filesChanged[] = $file;
                    continue;
                }

                // Modified file
                if ($state[$file] !== $sum) {
                    $filesFound[] = $file;
                    $filesChanged[] = $file;
                    continue;
                }

                // Pristine file
                $filesFound[] = $file;
            }

            $foundPercent = count($filesFound) / $filesExpected;
            $changedPercent = count($filesChanged) / $filesExpected;

            $score = ((1 * $foundPercent) - $changedPercent);
            $buildMatch[$build] = round($score * 100, 2);
        }


        // Find likely version
        $likelyBuild = array_search(max($buildMatch), $buildMatch);

        if ($buildMatch[$likelyBuild] < 70) {
            return null;
        }

        return [
            'build' => $likelyBuild,
            'modified' => true,
            'probability' => $buildMatch[$likelyBuild],
        ];
    }
}
