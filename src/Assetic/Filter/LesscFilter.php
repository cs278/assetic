<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2011 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Util\ProcessBuilder;

/**
 * Filters LESS files using the Lessc binary.
 *
 * @author Chris Smith <chris.smith@widerplan.com>
 */
class LesscFilter implements FilterInterface
{
    private $lesscBin;
    private $compress;
    private $optimization;
    private $includePaths = array();

    /**
     * Constructor.
     *
     * @param string $nodeBin   The path to the node binary
     */
    public function __construct($lesscBin = '/usr/bin/lessc')
    {
        $this->lesscBin = $lesscBin;
    }

    public function setOptimization($level)
    {
        $this->optimization = $level;
    }

    public function setCompress($compress)
    {
        $this->compress = $compress;
    }

    public function setIncludePaths(array $paths)
    {
        $this->includePaths = $paths;
    }

    public function filterLoad(AssetInterface $asset)
    {
        $pb = new ProcessBuilder();
        $pb->inheritEnvironmentVariables();

        $input = tempnam(sys_get_temp_dir(), 'assetic_lessc');
        $output = tempnam(sys_get_temp_dir(), 'assetic_lessc');

        file_put_contents($input, $asset->getContent());

        $pb->add($this->lesscBin);

        if ($this->compress) {
            $pb->add('--compress');
        }

        if (null !== $this->optimization) {
            $this->add('-O' . (int) $this->optimization);
        }

        $root = $asset->getSourceRoot();
        $path = $asset->getSourcePath();

        if ($root && $path) {
            $paths = array(dirname($root.'/'.$path));
        }
        else {
            $paths = array();
        }

        $paths = array_merge($paths, $this->includePaths);
        $paths = implode(':', array_map('realpath', $paths));

        $pb->setWorkingDirectory('/');

        $pb->add('--include-path='.$paths);

        $pb->add($input);
        $pb->add($output);

        $proc = $pb->getProcess();

        $code = $proc->run();
        unlink($input);

        if (0 < $code) {
            throw new \RuntimeException($proc->getErrorOutput());
        }

        $asset->setContent(file_get_contents($output));

        unlink($output);
    }

    public function filterDump(AssetInterface $asset)
    {
    }
}
