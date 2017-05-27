<?php

namespace Grav\Plugin\Console;

use Grav\Common\Grav;
use Grav\Common\Page\Collection;
use Grav\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;

class InitCommand extends ConsoleCommand
{
    private $host;
    private $route = array();

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

        $this->getRoutes();

        foreach ($this->route as $route => $title) {
            $res = $this->getHtmlContent($this->host . $route);
            $this->output->writeln($res);
        }
    }

    /**
     * @param string $url
     * @return string
     */
    private function getHtmlContent(string $url): string
    {
        // TODO: Error Handling

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

    private function getRoutes()
    {
        $page = Grav::instance()['page'];
        /** @var Collection $collection */
        $collection = $page->evaluate(['@root.descendants' => true]);
        $collection = $collection->routable();

        foreach ($collection as $page) {
            $this->route[$page->route()] = $page->title();
        }
    }

    private function initGrav()
    {
        $grav = Grav::instance();
        ob_start();
        $grav->process();
        ob_end_clean();

        $pages = self::getGrav()['pages'];
        $pages->init();
    }
}