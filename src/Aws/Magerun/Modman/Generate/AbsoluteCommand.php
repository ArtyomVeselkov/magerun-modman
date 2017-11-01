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
        $dir = $input->getOption('dir');
        $raw = $input->getOption('raw');

        // Create a finder instance for all files in the dir.
        $finder = new Finder();
        $finder
          ->files()
          ->depth('> 0')
          ->in($dir ? $dir : '.')
          ->sortByName();

        $paths = array();

        /* @var $file SplFileInfo */
        foreach ($finder as $file) {
            // Get the relative path
            $path = $file->getRelativePathname();

            // On windows, correct directory seperators
            if (DIRECTORY_SEPARATOR === '\\') {
                $path = str_replace('\\', '/', $path);
            }

            $target = $path;
            if (false !== strpos($path, '/emulate/')) {
                $path = preg_replace('#^app/code/(local|community|core|absolute)/([^\/]+)/([^\/]+)/emulate/#', '', $path);
            }
            if (!$raw) {
                // Rewrite file to shortest path
                $target = $this->rewritePath($target, true);
                $path = $this->rewritePath($path);
            }

            // Use path as key to prevent duplicates
            $paths[$target] = $path;
        }

        // Print paths to screen
        $this->outputPaths($output, $paths, $dir);
    }

    /**
     * Rewrite path. Based on https://gist.github.com/schmengler/88fa071822a95224373f
     *
     * app/code/community/VENDOR/PACKAGE/etc/config.xml -> app/code/community/VENDOR/PACKAGE
     * Exclude Mage/Zend/Varien code from app/code and lib
     */
    public function rewritePath($path, $emualteFlag = false)
    {
        $emulatePrefix = $emualteFlag ? '{^app/code/(local|community|core|absolute)/[^\/]+/[^\/]+/emulate/' : '{^';
        $path = preg_replace('{^\./}', '', $path);
        $path = preg_replace('{^app/code/(local|community|core|absolute)/((?![Mage|Zend])\w+)/(\w+)/(.*)$}', 'app/code/$1/$2/$3', $path);
        $path = preg_replace($emulatePrefix . 'lib/((?![Mage|Zend|Varien])\w+)/(.*)$}', 'lib/$1', $path);
        $path = preg_replace($emulatePrefix . 'js/(.*?)/(.*?)/(.*)$}', 'js/$1/$2', $path);
        $path = preg_replace($emulatePrefix . 'app/design/(.*?)/(.*?)/default/layout/(.*?)/(.*)$}', 'app/design/$1/$2/default/layout/$3', $path);
        $path = preg_replace($emulatePrefix . 'app/design/(.*?)/(.*?)/default/template/(.*?)/(.*)$}', 'app/design/$1/$2/default/template/$3', $path);
        $path = preg_replace($emulatePrefix . 'skin/(.*?)/(.*?)/default/(.*?)/(.*?)/(.*)$}', 'skin/$1/$2/default/$3/$4', $path);
        if ($emualteFlag) {

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
