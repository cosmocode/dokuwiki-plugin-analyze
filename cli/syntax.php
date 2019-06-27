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
        $num = $options->getOpt('pages', 0);
        $tf = new TableFormatter($this->colors);
        $result = $this->searchSyntaxUsage();

        foreach ($result as $name => $pages) {
            $count = count($pages);
            $color = Colors::C_YELLOW;
            if ($count === 0) $color = Colors::C_RED;

            echo $tf->format(
                [20, '*'],
                [$name, $count],
                [Colors::C_GREEN, $color]
            );

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
     * Get all installed plugins that contain syntax components
     *
     * Returns disabled plugins as well
     *
     * @return array
     */
    protected function getSyntaxPlugins()
    {
        $list = array_merge(
            glob(DOKU_PLUGIN . '*/syntax/'),
            glob(DOKU_PLUGIN . '*/syntax.php')
        );

        $plugins = [];

        foreach ($list as $item) {
            $item = preg_replace('/syntax(\.php|\/)?$/', '', $item);
            $plugins[] = basename($item);
        }
        array_unique($plugins);

        return $plugins;
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

        $result = array_fill_keys($this->getSyntaxPlugins(), []);
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

