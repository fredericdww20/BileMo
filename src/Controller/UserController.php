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
use OpenApi\Annotations as OA;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


class UserController extends AbstractController
{

    /**
     * Affiche la liste des utilisateurs inscrits liés à un client.
     * 
     * Cette méthode retourne la liste des utilisateurs inscrits liés à un client spécifique.
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
        // Récupérer le client authentifié (vérification du token JWT automatique par Symfony)
        $currentClient = $this->getUser();
        if (!$currentClient instanceof Client) {
            // Vérifiez que l'utilisateur authentifié est bien un client
            return new JsonResponse(['error' => 'Accès refusé'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier que le client authentifié correspond bien au client demandé
        if ($currentClient->getId() !== $clientId) {
            // Empêcher un client de voir les utilisateurs d'un autre client
            return new JsonResponse(['error' => 'Accès interdit'], Response::HTTP_FORBIDDEN);
        }

        // Récupérer la liste des utilisateurs pour ce client spécifique
        $users = $userRepository->findBy(['client' => $clientId]);

        // Vérifier si aucun utilisateur n'a été trouvé
        if (empty($users)) {
            return new JsonResponse(['error' => 'Aucun utilisateur correspondant'], Response::HTTP_NOT_FOUND);
        }

        // Contexte pour gérer les références circulaires lors de la sérialisation et utiliser les groupes de sérialisation
        $context = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return $object->getId();
            },
            'groups' => ['client_users'], // Utiliser le groupe de sérialisation "client_users"
            'json_encode_options' => JSON_PRETTY_PRINT
        ];

        // Sérialiser la liste des utilisateurs en JSON avec JSON_PRETTY_PRINT pour un formatage lisible
        $jsonUsers = $serializer->serialize($users, 'json', $context);

        // Construire une réponse structurée
        $response = [
            'message' => 'Succès, voici la liste des utilisateurs',
            'data' => json_decode($jsonUsers, true) // Conserver les données en tant qu'objet JSON
        ];

        // Retourner la réponse JSON avec la liste des utilisateurs
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
    public function createUser(Request $request, SerializerInterface $serializer, ValidatorInterface $validator, EntityManagerInterface $entityManager, ClientRepository $clientRepository, UserRepository $userRepository): JsonResponse
    {
        // Récupérer le contenu de la requête
        $content = $request->getContent();
        $data = json_decode($content, true);

        // Vérifier si le JSON est valide
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('JSON decode error: ' . json_last_error_msg());
            error_log('Request content: ' . $content);
            return new JsonResponse(['error' => 'Invalid JSON data'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer l'utilisateur authentifié
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return new JsonResponse(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Associer l'utilisateur authentifié au client
        $client = $entityManager->getRepository(Client::class)->find($currentUser->getId());
        if (!$client) {
            return new JsonResponse(['error' => 'Client not found'], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'email existe déjà
        $existingUser = $userRepository->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return new JsonResponse(['error' => 'Email deja utilis'], Response::HTTP_CONFLICT);
        }

        // Créer un nouvel utilisateur
        $user = new User();
        $user->setUsername($data['username'] ?? '');
        $user->setEmail($data['email'] ?? '');
        $user->setClient($client); // Associer l'utilisateur au client

        // Valider l'entité utilisateur
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['error' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        // Persister et sauvegarder l'utilisateur dans la base de données
        $entityManager->persist($user);
        $entityManager->flush();

        // Sérialiser l'utilisateur et renvoyer la réponse
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'user_detail']);
        return new JsonResponse($jsonUser, Response::HTTP_CREATED, [], true);
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
        // Vérification du token (automatique via firewall Symfony)

        // Récupérer l'utilisateur par son ID
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouve'], Response::HTTP_BAD_REQUEST);
        }

        // Récupérer le client actuel (via l'utilisateur authentifié)
        $currentUser = $this->getUser();

        // Assurez-vous que l'utilisateur authentifié est de la classe Client
        if (!$currentUser instanceof Client) {
            return new JsonResponse(['error' => 'Acces refuse'], Response::HTTP_UNAUTHORIZED);
        }

        // Vérifier l'appartenance de l'utilisateur au client
        if ($user->getClient() !== $currentUser) {
            return new JsonResponse(['error' => 'Cette utilisateur appartient pas au client'], Response::HTTP_FORBIDDEN);
        }

        // Sérialiser l'utilisateur
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'user_detail']);
        $userData = json_decode($jsonUser, true);

        // Construire la réponse avec le message de succès et les informations de l'utilisateur
        $response = [
            'Message' => 'Succes, voici les informations de cette utilisateur',
            'Utilisateur' => $userData
        ];

        return new JsonResponse($response, Response::HTTP_OK);
    }
}
