<?php

namespace Aws\Magerun\Modman\Generate;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Console\Helper\Table;
use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate a modman file for the current directory.
 */
class AbsoluteCommand extends AbstractMagentoCommand
{
    public $dir = '';
    public $raw = false;

    protected function configure()
    {
        $this
          ->setName('modman:generate:absolute')
          ->setDescription('Generate a modman file for the current directory (Absolute modification)')
          ->addOption('dir', 'd', InputOption::VALUE_OPTIONAL, 'Directory in which the module files are located.')
          ->addOption('raw', 'r', InputOption::VALUE_NONE, 'Ouput the raw paths, without rewriting to the shortest variant.')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dir = $input->getOption('dir');
        $this->raw = $input->getOption('raw');

        // Create a finder instance for all files in the dir.
        $finder = new Finder();
        $finder
          ->files()
          ->depth('> 0')
          ->in($this->dir ? $this->dir : '.')
          ->sortByName();

        $paths = $this->processPaths($finder);

        // Print paths to screen
        $this->outputPaths($output, $paths, $this->dir);
    }

    /**
     * @param Finder|array $finder
     * @return array
     */
    public function processPaths($finder)
    {
        $paths = array();
        /* @var $file SplFileInfo */
        foreach ($finder as $file) {
            // Get the relative path
            $path = is_a($finder, 'Symfony\Component\Finder\Finder') ? $file->getRelativePathname() : $file;

            // On windows, correct directory seperators
            if (DIRECTORY_SEPARATOR === '\\') {
                $path = str_replace('\\', '/', $path);
            }

            $target = $path;
            if (false !== strpos($path, '/emulate/')) {
                $path = preg_replace('#^app/code/(local|community|core|absolute)/([^\/]+)/([^\/]+)/emulate/#', '', $path);
            }
            if (!$this->raw) {
                // Rewrite file to shortest path
                $target = $this->rewritePath($target, true);
                $path = $this->rewritePath($path);
            }

            // Use path as key to prevent duplicates
            $paths[$target] = $path;
        }
        return $paths;
    }

    /**
     * Rewrite path. Based on https://gist.github.com/schmengler/88fa071822a95224373f
     *
     * app/code/community/VENDOR/PACKAGE/etc/config.xml -> app/code/community/VENDOR/PACKAGE
     * Exclude Mage/Zend/Varien code from app/code and lib
     * @param $path
     * @param bool $emulateFlag
     * @return mixed
     */
    public function rewritePath($path, $emulateFlag = false)
    {
        $emulatePrefix = $emulateFlag ? '{/emulate/' : '{^';
        $emulateSuffix = $emulateFlag ? '/emulate/' : '';
        $rules = array(
            '{^\./}' => '',
            $emulatePrefix . 'app/code/(local|community|core|absolute)/((?![Mage|Zend])\w+)/(\w+)/(.*)$}' => 'app/code/$1/$2/$3',
            $emulatePrefix . 'lib/((?![Mage|Zend|Varien])\w+)/(.*)$}' => $emulateSuffix . 'lib/$1',
            $emulatePrefix . 'app/design/(.*?)/(.*?)/default/layout/(.*?)/(.*)$}' => $emulateSuffix . 'app/design/$1/$2/default/layout/$3',
            $emulatePrefix . 'app/design/(.*?)/(.*?)/default/template/(.*?)/(.*)$}' => $emulateSuffix . 'app/design/$1/$2/default/template/$3',
            $emulatePrefix . 'skin/(.*?)/(.*?)/default/(.*?)/(.*?)/(.*)$}' => $emulateSuffix . 'skin/$1/$2/default/$3/$4',
            $emulatePrefix . 'js/(.*?)/(.*?)/(.*)$}' => $emulateSuffix . 'js/$1/$2'
        );
        foreach ($rules as $pattern => $replacement) {
            $path = preg_replace($pattern, $replacement, $path);
        }
        return $path;
    }

    /**
     * Render the modman paths in a nice format
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param array $paths
     * @param string $prefix
     */
    protected function outputPaths(OutputInterface $output, $paths, $prefix = '')
    {
        // Make sure the prefix ends with 1 slash
        $prefix = $prefix ? rtrim($prefix, '/') . '/' : '';

        $rows = array();
        foreach ($paths as $target => $path) {
            // Add dir prefix + space suffix
            $rows[] = array($prefix . $target . ' ', $path);
        }

        // Write output in nice format
        $table = new Table($output);
        $table->setRows($rows);

        // Set spaceless
        $table->setStyle('compact');
        $table->getStyle()->setBorderFormat('');

        // Output to screen
        $table->render();
    }
}

/*
 * false => array(
                '{^\./}' => '',
                '{^app/code/(local|community|core|absolute)/((?![Mage|Zend])\w+)/(\w+)/(.*)$' => 'app/code/$1/$2/$3',
                '{^lib/((?![Mage|Zend|Varien])\w+)/(.*)$}' => 'lib/$1',
                '{^app/design/(.*?)/(.*?)/default/layout/(.*?)/(.*)$}' => '{app/design/$1/$2/default/layout/$3}',
                '{^app/design/(.*?)/(.*?)/default/template/(.*?)/(.*)$}' => 'app/design/$1/$2/default/layout/$3',
                '^skin/(.*?)/(.*?)/default/(.*?)/(.*?)/(.*)$}' => 'skin/$1/$2/default/$3/$4',
            ),
 */