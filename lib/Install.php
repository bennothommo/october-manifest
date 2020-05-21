<?php
namespace October\Manifest;

/**
 * Represents an installation of October CMS.
 *
 * @author Ben Thomson
 */
class Install
{
    /**
     * @var string Root folder of the installation.
     */
    protected $root;

    /**
     * @var array Expected paths.
     */
    protected $paths = [
        '/modules/backend',
        '/modules/system',
    ];

    /**
     * @var array Files cache.
     */
    protected $files = [];

    /**
     * Constructor.
     *
     * @param string $root
     */
    public function __construct(string $root = '')
    {
        if (!empty($root)) {
            $this->setRoot($root);
        }
    }

    /**
     * Sets the root folder.
     *
     * @param string $root
     */
    public function setRoot(string $root)
    {
        $this->root = realpath($root);
        $this->validateInstall();

        return $this;
    }

    /**
     * Gets a list of files and their corresponding hashes.
     *
     * @throws Exception If no root path has been specified.
     * @return array
     */
    public function getFiles(): array
    {
        if (empty($this->root)) {
            throw new \Exception('No root has been specified.');
        }
        if (count($this->files)) {
            return $this->files;
        }

        $files = [];

        foreach ($this->paths as $path) {
            foreach ($this->findFiles($path) as $file) {
                $files[$this->getFilename($file)] = md5_file($file);
            }
        }

        return $this->files = $files;
    }

    /**
     * Gets the checksum of a specific install.
     *
     * @throws Exception If no root path has been specified.
     * @return string
     */
    public function getChecksum(): string
    {
        if (!count($this->files)) {
            $this->getFiles();
        }

        $sum = '';

        foreach (array_values($this->files) as $hash) {
            $sum .= $hash;
        }

        return md5($sum);
    }

    /**
     * Finds all files within the path.
     *
     * @param string $path
     * @return array
     */
    protected function findFiles(string $path): array
    {
        $files = [];
        $basePath = $this->root . $path;

        $iterator = function ($path) use (&$iterator, &$files, $basePath) {
            foreach (new \DirectoryIterator($path) as $item) {
                if ($item->isDot() === true) {
                    continue;
                }
                if ($item->isFile()) {
                    // Ignore hidden files
                    if (substr($item->getFilename(), 0, 1) === '.') {
                        continue;
                    }

                    // Check for specific extensions.
                    $validExtensions = ['php', 'js', 'css', 'less'];
                    $pathinfo = $item->getFileInfo();

                    if (!in_array(strtolower($pathinfo->getExtension()), $validExtensions)) {
                        continue;
                    }

                    $files[] = $item->getPathName();
                }
                if ($item->isDir()) {
                    // Ignore hidden directories
                    if (substr($item->getFilename(), 0, 1) === '.') {
                        continue;
                    }

                    $iterator($item->getPathname());
                }
            }
        };
        $iterator($basePath);

        return $files;
    }

    /**
     * Validates the installation.
     *
     * Really, all this does is checks that the Backend and System modules are installed.
     *
     * @throws Exception If either module is missing.
     * @return void
     */
    protected function validateInstall(): void
    {
        foreach ($this->paths as $path) {
            if (!is_dir($this->root . $path)) {
                throw new \Exception('This does not appear to be a valid October CMS installation.');
            }
        }
    }

    /**
     * Returns the filename without the root.
     *
     * @param string $file
     * @return string
     */
    protected function getFilename(string $file): string
    {
        return str_replace($this->root, '', $file);
    }
}
