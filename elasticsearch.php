<?php

namespace Grav\Plugin;

use Elasticsearch\ClientBuilder;
use Grav\Common\Plugin;

/**
 * Class ElasticsearchPlugin
 * @package Grav\Plugin
 */
class ElasticsearchPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        $uri = $this->grav['uri'];
        $route = $this->config->get('plugins.elasticsearch.route.api');

        if ($this->getMethod() === 'post' && $route && $route == $uri->path()) {
            $this->enable([
                'onPageInitialized' => ['onPageInitialized', 0]
            ]);
        }
    }

    public function onPageInitialized()
    {
        $this->setHeaders();

        $body = [];

        $search = $_POST['search'];

        // TODO: Use config data to create
        $clientES = ClientBuilder::create()->build();

        $params = [
            'index' => $this->config->get('plugins.elasticsearch.elasticsearch.index') ?? 'data',
            'type' => $this->config->get('plugins.elasticsearch.elasticsearch.type') ?? 'pages',
            'body' => [
                'query' => [
                    'bool' => [
                        'should' => [
                            ['match' => ['content' => $search]],
                            ['match' => ['title' => $search]],
                        ],
                    ],
                ],
                'size' => $this->config->get('plugins.elasticsearch.maxNumberOfResults') ?? 10
            ],
        ];

        $searchRes = $clientES->search($params);

        $res = $this->parseES($searchRes);

        echo \json_encode($res);

        exit;
    }

    private function parseES(array $result): array
    {
        if(empty($result) || empty($result['hits']) || empty($result['hits']['total'])) {
            return ['total' => 0];
        }

        $res = [];

        foreach($result['hits']['hits'] as $page) {
            $res[] = [
                'score' => $page['_score'] ?? null,
                'title' => $page['_source']['title'] ?? null,
                'content' => $page['_source']['content'] ?? null,
            ];
        }

        return $res;
    }

    private function setHeaders()
    {
        \header('Content-type: application/json');
    }

    private function getMethod(): string
    {
        if (empty($_SERVER)) {
            return 'cli';
        }

        if (empty($_SERVER['REQUEST_METHOD'])) {
            return 'cli';
        }

        return \strtolower($_SERVER['REQUEST_METHOD']);
    }
}