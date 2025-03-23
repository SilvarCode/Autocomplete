<?php
declare(strict_types=1);

/**
 * ***********************************
 * ||       CopyAssetsCommand       ||
 * ***********************************
 *
 * @copyright   2022 SilvarCode / SilvarCode.com
 *              All rights reserved.
 * @link        https://silvarcode.com
 * @since       1.0.0
 * @license     MIT License - see LICENSE.txt for more details.
 *              Redistributions of files must retain the above notice.
 *              https://opensource.org/licenses/mit-license.php MIT License
 */
namespace SilvarCode\Autocomplete\Command;

use Bake\Command\BakeCommand;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Plugin;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * CopyAssets command.
 */
class CopyAssetsCommand extends BakeCommand
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'bake copy-autocomplete-assets';
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $DS = DIRECTORY_SEPARATOR;
        $parser = parent::buildOptionParser($parser);
        $webroot = rtrim(WWW_ROOT, $DS);

        $parser->setDescription(
            'The sole purpose of this command is to <info>copy</info> ' .
            "the assets from the plugin's webroot into $webroot{$DS}autocomplete$DS"
        )->addOption('confirm', [
            'help' => 'Please confirm you would like to copy webroot assets',
            'required' => true,
            'choices' => ['yes', 'no'],
        ]);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io)
    {
        if (in_array(strtolower($args->getOption('confirm')), ['no', 'n'])) {
            return 1;
        }

        $source = Plugin::path('SilvarCode/Autocomplete');
        $source = rtrim($source, DS) . DS . 'webroot';
        $destination = WWW_ROOT . 'autocomplete' . DIRECTORY_SEPARATOR;

        if (!is_dir($destination)) {
            if (!mkdir($destination, 0755, true)) {
                $io->err('Could not create destination directory: ' . $destination);
                return 1;
            }
        }

        $files = $this->getFiles($source);
        foreach ($files as $file) {
            $target = $destination . basename($file);
            if (!copy($file, $target)) {
                $io->err('Failed to copy file: ' . $file);
                return 1;
            }
        }

        $io->out('Assets copied to directory: ' . $destination);

        return null;
    }

    /**
     * Get all files from the source directory and its subdirectories.
     *
     * @param string $dir The directory path
     * @return array List of file paths
     */
    private function getFiles(string $dir): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile()) {
                $files[] = $fileInfo->getRealPath();
            }
        }
        return $files;
    }
}
