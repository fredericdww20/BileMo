<?php

namespace App\Service;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HateoasService
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function addLinks($entity, array $links): array
    {
        $data = [
            'data' => $entity,
            'links' => [],
        ];

        foreach ($links as $rel => $route) {
            $data['links'][$rel] = $this->router->generate($route['name'], $route['params'] ?? [], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return $data;
    }
}

