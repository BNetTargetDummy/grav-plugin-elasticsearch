<?php

namespace Grav\Plugin\Console;

use Grav\Common\Grav;
use Grav\Common\Page\Collection;
use Grav\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;

class InitCommand extends ConsoleCommand
{
    private $host;

    protected function configure()
    {
        $this
            ->setName('init')
            ->addArgument(
                'host',
                InputArgument::REQUIRED,
                'The host that should be scrap'
            )
            ->setHelp('Init the elasticsearch Node with all routes');
    }

    protected function serve()
    {
        $this->host = $this->input->getArgument('host');
        $this->output->writeln('Init ElasticSearch with <cyan>' . $this->host . '</cyan>');

        $this->initGrav();

        $page = Grav::instance()['page'];
        /** @var Collection $collection */
        $collection = $page->evaluate(['@root.descendants' => true]);
        $collection = $collection->routable();

        $routes = [];

        foreach($collection as $page) {
            $routes[$page->route()] = $page->title();
        }
    }

    private function initGrav()
    {
        $grav = Grav::instance();
        ob_start();
        $grav->process();
        ob_end_clean();

        $pages =  self::getGrav()['pages'];
        $pages->init();
    }
}