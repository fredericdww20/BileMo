<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\ClientRepository;
use App\Service\HateoasService;
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
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class UserController extends AbstractController
{
    private $hateoas;

    public function __construct(HateoasService $hateoas)
    {
        $this->hateoas = $hateoas;
    }

    /**
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
    #[Route('/api/client/{clientId}', name: 'listForClient', methods: ['GET'])]
    public function listForClient(int $clientId, UserRepository $userRepository, SerializerInterface $serializer): Response
    {
        $currentClient = $this->getUser();
        if (!$currentClient instanceof Client) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_UNAUTHORIZED);
        }

        if ($currentClient->getId() !== $clientId) {
            return new JsonResponse(['error' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
        }

        $users = $userRepository->findBy(['client' => $clientId]);

        if (empty($users)) {
            return new JsonResponse(['error' => 'Aucun utilisateur correspondant'], Response::HTTP_NOT_FOUND);
        }

        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            },
            'groups' => ['client_users'],
            'json_encode_options' => JSON_PRETTY_PRINT
        ];

        $jsonUsers = $serializer->serialize($users, 'json', $context);
        $userData = json_decode($jsonUsers, true);

        $data = array_map(function($user) use ($currentClient) {
            return $this->hateoas->addLinks($user, [
                'self' => ['name' => 'detailUser', 'params' => ['id' => $user['id']]],
                'list' => ['name' => 'listForClient', 'params' => ['clientId' => $currentClient->getId()]]
            ]);
        }, $userData);

        $response = [
            'message' => 'Succès, voici la liste des utilisateurs',
            'data' => $data
        ];

        return new JsonResponse($response, Response::HTTP_OK);
    }

    /**
     * Crée un nouvel utilisateur.
     *
     * Cette méthode crée un nouvel utilisateur.
     *
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
    #[Route('/api/users', name: 'createUser', methods: ['POST'])]
    public function createUser(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $entityManager, UserRepository $userRepository): JsonResponse
    {
        $content = $request->getContent();
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $client = $entityManager->getRepository(Client::class)->find($currentUser->getId());
        if (!$client) {
            return new JsonResponse(['error' => 'Client not found'], Response::HTTP_NOT_FOUND);
        }

        $existingUser = $userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Email déjà utilisé'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setUsername($data['username'] ?? '');
        $user->setEmail($data['email'] ?? '');
        $user->setClient($client);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['error' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'user_detail']);
        $userData = json_decode($jsonUser, true);

        $response = $this->hateoas->addLinks($userData, [
            'self' => ['name' => 'detailUser', 'params' => ['id' => $user->getId()]],
            'list' => ['name' => 'listForClient', 'params' => ['clientId' => $client->getId()]]
        ]);

        return new JsonResponse($response, Response::HTTP_CREATED);
    }


    // fonction qui récuper un utilisateur par son id
    /**
     * Récupère un utilisateur par son ID.
     *
     * Cette méthode retourne un utilisateur spécifique par son ID.
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne un utilisateur spécifique par son ID",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Items(ref=@Model(type=User::class))
     *     )
     * )
     * @OA\Tag(name="User")
     */
    // src/Controller/UserController.php

    #[Route('/api/users/{id}', name: 'detailUser', methods: ['GET'])]
    public function getUserById(int $id, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_BAD_REQUEST);
        }

        $currentUser = $this->getUser();
        if (!$currentUser instanceof Client) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->getClient() !== $currentUser) {
            return new JsonResponse(['error' => 'Cet utilisateur n\'appartient pas au client'], Response::HTTP_FORBIDDEN);
        }

        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'user_detail']);
        $userData = json_decode($jsonUser, true);

        // Assurez-vous que $userData contient un tableau avec des clés correctes
        if (isset($userData['id'])) {
            $response = $this->hateoas->addLinks($userData, [
                'self' => ['name' => 'detailUser', 'params' => ['id' => $userData['id']]],
                'list' => ['name' => 'listForClient', 'params' => ['clientId' => $currentUser->getId()]]
            ]);

            // Utiliser json_encode pour convertir le tableau en chaîne JSON
            $jsonResponse = json_encode($response);

            return new JsonResponse($jsonResponse, Response::HTTP_OK, [], true);
        }

        return new JsonResponse(['error' => 'Erreur de sérialisation'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
