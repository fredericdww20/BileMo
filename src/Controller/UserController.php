<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\ClientRepository;
use App\Entity\User;
use App\Entity\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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
    #[Route('/api/usersall', name: 'users', methods: ['GET'])]
    public function getAllUser(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {

        $bookList = $userRepository->findAll();

        $jsonBookList = $serializer->serialize($bookList, 'json', ['groups' => 'getUsers']);
        return new JsonResponse($jsonBookList, Response::HTTP_OK, [], true);
    }

    /**
     * Récupère la liste des utilisateurs d'un client par son ID.
     *
     * Cette méthode retourne la liste des utilisateurs d'un client spécifique par son ID.
     *
     * @Route("/api/user/{id}", methods={"GET"})
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste des utilisateurs d'un client spécifique par son ID",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Items(ref=@Model(type=User::class))
     *     )
     * )
     * @OA\Tag(name="User")
     */
    #[Route('/api/client/{clientId}', name: 'detailUser', methods: ['GET'])]
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

    /**
     * Crée un nouvel utilisateur.
     *
     * Cette méthode crée un nouvel utilisateur.
     *
     * @Route("/api/user", methods={"POST"})
     * @OA\Response(
     *     response=201,
     *     description="Crée un nouvel utilisateur",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Items(ref=@Model(type=User::class))
     *     )
     * )
     * @OA\Tag(name="User")
     */
    #[Route('/api/user', name: 'createUser', methods: ['POST'])]
    public function createUser(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $entityManager, ClientRepository $clientRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setUsername($data['username'] ?? '');
        $user->setEmail($data['email'] ?? '');
    
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse(['error' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $jsonUser = $serializer->serialize($user, 'json');
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
    }
}
