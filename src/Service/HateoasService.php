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

    /**
     * Ajoute des liens aux données de l'entité.
     *
     * @param mixed $entity L'entité à laquelle ajouter des liens.
     * @param array $links Un tableau de liens à ajouter.
     * @return array Les données de l'entité modifiées avec les liens ajoutés.
     */
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

