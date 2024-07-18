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
     * Récupère la liste des utilisateurs.
     * 
     * Cette méthode retourne la liste de tous les utilisateurs.
     * 
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
        // Récupérer l'utilisateur actuel
        $currentClient = $this->getUser();
        if (!$currentClient instanceof Client) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier si l'utilisateur actuel est le propriétaire du client
        if ($currentClient->getId() !== $clientId) {
            return new JsonResponse(['error' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
        }

        // Récupérer les utilisateurs du client
        $users = $userRepository->findBy(['client' => $clientId]);

        // Vérifier si aucun utilisateur n'a été trouvé
        if (empty($users)) {
            return new JsonResponse(['error' => 'Aucun utilisateur correspondant'], Response::HTTP_NOT_FOUND);
        }

        // Utiliser le sérialiseur pour convertir les objets en chaînes JSON
        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            },
            'groups' => ['client_users'],
            'json_encode_options' => JSON_PRETTY_PRINT
        ];

        // Convertir les objets en chaînes JSON
        $jsonUsers = $serializer->serialize($users, 'json', $context);

        // Convertir les chaînes JSON en tableaux PHP
        $userData = json_decode($jsonUsers, true);

        // Ajouter des liens HATEOAS aux données utilisateur
        $data = array_map(function($user) use ($currentClient) {
            return $this->hateoas->addLinks($user, [
                'self' => ['name' => 'detailUser', 'params' => ['id' => $user['id']]],
                'list' => ['name' => 'listForClient', 'params' => ['clientId' => $currentClient->getId()]]
            ]);
        }, $userData);

        // Retourner une réponse JSON
        $response = [
            'message' => 'Succès, voici la liste des utilisateurs',
            'data' => $data
        ];

        // Retourner une réponse JSON avec un code d'état HTTP 200
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
        // Récupérer le contenu de la requête
        $content = $request->getContent();

        // Convertir le contenu en tableau PHP
        $data = json_decode($content, true);

        // Vérifier si le contenu est un JSON valide
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si les données requises sont présentes
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier si l'utilisateur actuel est un client
        $client = $entityManager->getRepository(Client::class)->find($currentUser->getId());
        if (!$client) {
            return new JsonResponse(['error' => 'Client not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur actuel est le propriétaire du client
        $existingUser = $userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Email déjà utilisé'], Response::HTTP_CONFLICT);
        }

        // Créer un nouvel utilisateur
        $user = new User();
        $user->setUsername($data['username'] ?? '');
        $user->setEmail($data['email'] ?? '');
        $user->setClient($client);

        // Valider les données de l'utilisateur
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

        // Convertir l'objet utilisateur en chaîne JSON
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'user_detail']);

        // Convertir la chaîne JSON en tableau PHP
        $userData = json_decode($jsonUser, true);

        // Ajouter des liens HATEOAS aux données utilisateur
        $response = $this->hateoas->addLinks($userData, [
            'self' => ['name' => 'detailUser', 'params' => ['id' => $user->getId()]],
            'list' => ['name' => 'listForClient', 'params' => ['clientId' => $client->getId()]]
        ]);

        // Retourner une réponse JSON avec un code d'état HTTP 201
        return new JsonResponse(['message' => 'Utilisateur créé avec succès'], Response::HTTP_OK);
    }

    /**
     * Su.
     *
     * Cette méthode met à jour un utilisateur existant.
     *
     * @OA\Response(
     *     response=200,
     *     description="Met à jour un utilisateur existant",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Items(ref=@Model(type=User::class))
     *     )
     * )
     * @OA\Tag(name="User")
     */
    #[Route('/api/users/{userId}', name: 'deleteUser', methods: ['DELETE'])]
    public function deleteUser(int $userId, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupérer l'utilisateur actuel
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Client) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_UNAUTHORIZED);
        }

        // Récupérer l'utilisateur à supprimer
        $user = $userRepository->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur actuel est le propriétaire de l'utilisateur
        if ($user->getClient() !== $currentUser) {
            return new JsonResponse(['error' => 'Cet utilisateur n\'appartient pas au client'], Response::HTTP_FORBIDDEN);
        }

        // Supprimer l'utilisateur
        $entityManager->remove($user);
        $entityManager->flush();

        // Retourner une réponse JSON avec un code d'état HTTP 204
        return new JsonResponse(['message' => 'Utilisateur supprimé avec succès'], Response::HTTP_NOT_FOUND);
    }
    

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
        // Récupérer l'utilisateur par son ID
        $user = $entityManager->getRepository(User::class)->find($id);

        // Vérifier si l'utilisateur n'a pas été trouvé
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], Response::HTTP_BAD_REQUEST);
        }

        // Vérifier si l'utilisateur actuel est un client
        $currentUser = $this->getUser();
        if (!$currentUser instanceof Client) {
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier si l'utilisateur actuel est le propriétaire de l'utilisateur
        if ($user->getClient() !== $currentUser) {
            return new JsonResponse(['error' => 'Cet utilisateur n\'appartient pas au client'], Response::HTTP_FORBIDDEN);
        }

        // Convertir l'objet utilisateur en chaîne JSON
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'user_detail']);

        // Convertir la chaîne JSON en tableau PHP
        $userData = json_decode($jsonUser, true);

        // Assurez-vous que $userData contient un tableau avec des clés correctes
        if (isset($userData['id'])) {
            $response = $this->hateoas->addLinks($userData, [
                'self' => ['name' => 'detailUser', 'params' => ['id' => $userData['id']]],
                'list' => ['name' => 'listForClient', 'params' => ['clientId' => $currentUser->getId()]]
            ]);

            // Utiliser json_encode pour convertir le tableau en chaîne JSON
            $jsonResponse = json_encode($response);

            // Retourner une réponse JSON avec un code d'état HTTP 200
            return new JsonResponse($jsonResponse, Response::HTTP_OK, [], true);
        }

        // Retourner une réponse JSON avec un code d'état HTTP 500
        return new JsonResponse(['error' => 'Erreur de sérialisation'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
