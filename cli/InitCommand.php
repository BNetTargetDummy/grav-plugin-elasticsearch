<?php

namespace Grav\Plugin\Console;

use Grav\Common\Grav;
use Grav\Common\Page\Collection;
use Grav\Console\ConsoleCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\DomCrawler\Crawler;

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
            try {
                $content = $this->getHtmlContent($this->host . $route);

                $this->output->writeln('<green>Successful request on ' . $route . '</green>');

                $crawler = new Crawler($content);

                $isWithArticle = $crawler->filter('article.content')->count();

                if($isWithArticle) {
                    $content = $crawler->filter('div.container');
                } else {
                    $content = $crawler->filter('article.content');
                }
            } catch (\Exception $e) {
                $this->output->writeln('<red>[' . $route . '] ' . $e->getMessage() . '</red>');
            }
        }
    }

    /**
     * @param string $url
     * @return string
     * @throws \Exception
     */
    private function getHtmlContent(string $url): string
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $res = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($res === false) {
            throw new \Exception('Error CURL: ' . curl_error($curl));
        }

        if ($httpCode !== 200) {
            throw new \Exception('Error CURL: Got a ' . $httpCode . ' status code instead of a 200');
        }

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