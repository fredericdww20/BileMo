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
    /**
     * Retourne la liste de tous les utilisateurs.
     *
     * Cette méthode retourne une liste de tous les utilisateurs disponibles.
     * 
     *
     * @Route("/api/users", methods={"GET"})
     * 
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste de tous les utilisateurs",
     *     @OA\JsonContent(ref=@Model(type=Product::class))
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Product")
     * @Security(name="Bearer")
     */
    #[Route('/api/usersall', name: 'books', methods: ['GET'])]
    public function getAllUser(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        
        $bookList = $userRepository->findAll();

        $jsonBookList = $serializer->serialize($bookList, 'json', ['groups' => 'getBooks']);
        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    /**
     * Récupère un utilisateur par son ID.
     *
     * Cette méthode retourne les détails d'un utilisateur spécifique par son ID.
     *
     * @Route("/api/user/{id}", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Retourne les détails de l'utilisateur",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Items(ref=@Model(type=User::class))
     *     )
     * )
     * @OA\Tag(name="User")
     */
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

