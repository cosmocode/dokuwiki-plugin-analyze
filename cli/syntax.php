<?php
/**
 * DokuWiki Plugin analyze (CLI Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <andi@splitbrain.org>
 */

use splitbrain\phpcli\Colors;
use splitbrain\phpcli\Options;
use splitbrain\phpcli\TableFormatter;

class cli_plugin_analyze_syntax extends DokuWiki_CLI_Plugin
{

    /**
     * Register options and arguments on the given $options object
     *
     * @param Options $options
     *
     * @return void
     * @throws \splitbrain\phpcli\Exception
     */
    protected function setup(Options $options)
    {
        $options->setHelp('List which syntax plugins are actually used. Requires an up-to-date index!');
        $options->registerOption('pages', 'Print this many pages where the syntax is used, -1 for all', 'p', 'number');
    }

    /**
     * Your main program
     *
     * Arguments and options have been parsed when this is run
     *
     * @param Options $options
     *
     * @return void
     * @throws \splitbrain\phpcli\Exception
     */
    protected function main(Options $options)
    {
        $result = $this->searchSyntaxUsage();

        foreach ($result as $name => $pages) {
            $tf = new TableFormatter($this->colors);


            echo $tf->format(
                [20, '*'],
                [$name, count($pages)],
                [Colors::C_GREEN, Colors::C_YELLOW]
            );

            $num = $options->getOpt('pages', 0);
            if ($num) {
                if ($num > 0) {
                    $pages = array_slice($pages, 0, $num);
                }

                echo $tf->format(
                    [5, '*'],
                    ['', implode("\n", $pages)],
                    [Colors::C_LIGHTGRAY]
                );
            }
        }
    }


    /**
     * Search which pages use which syntax
     *
     * @return array (plugin => [pages,...]
     */
    protected function searchSyntaxUsage()
    {
        $idx = idx_get_indexer();
        $pages = $idx->getPages();

        $result = [];
        foreach ($pages as $id) {
            if (!page_exists($id)) continue;
            $this->info("Scanning $id...");
            $instructions = p_cached_instructions(wikiFN($id), false, $id);

            foreach ($instructions as $i) {
                if ($i[0] == 'plugin') {
                    list($plugin) = explode('_', $i[1][0]);
                    if (!isset($result[$plugin])) $result[$plugin] = [];
                    $result[$plugin][] = $id;
                }
            }
        }

        $result = array_map('array_unique', $result);
        ksort($result);

        return $result;
    }
}

