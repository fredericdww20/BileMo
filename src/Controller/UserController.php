<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Entity\User;
use App\Entity\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class UserController extends AbstractController
{
    #[Route('/api/usersall', name: 'books', methods: ['GET'])]
    public function getAllUser(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        
        $bookList = $userRepository->findAll();

        $jsonBookList = $serializer->serialize($bookList, 'json', ['groups' => 'getBooks']);
        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/client/{clientId}', name: 'detailBook', methods: ['GET'])]
    public function listForClient(int $clientId, UserRepository $userRepository, SerializerInterface $serializer): Response
    {

        $users = $userRepository->findByClient($clientId);

        if (empty($users)) {
            return new JsonResponse(['error' => 'Aucun utilisateur correspondant'], Response::HTTP_NOT_FOUND);
        }

        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            },
        ];

        $json = $serializer->serialize($users, 'json', $context);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}

