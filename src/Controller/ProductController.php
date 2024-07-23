<?php

namespace App\Controller;

use App\Entity\Product;
use App\Service\HateoasService;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Knp\Component\Pager\PaginatorInterface;



class ProductController extends AbstractController
{
    private $hateoas;

    public function __construct(HateoasService $hateoas)
    {
        $this->hateoas = $hateoas;
    }
    /**
     * Retourne la liste de tous les produits.
     *
     * Cette méthode retourne une liste de tous les produits disponibles.
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne la liste de tous les produits",
     *     @OA\JsonContent(
     *        type="array",
     *        @OA\Items(ref=@Model(type=Product::class))
     *     )
     * )
     * @OA\Tag(name="Product")
     */
    #[Route('api/products', name: 'app_product', methods: ['GET'])]
    public function getAllProduct(Request $request, ProductRepository $productRepository, SerializerInterface $serializer, PaginatorInterface $paginator): JsonResponse
    {
        // Récupère la page actuelle et le nombre d'éléments par page à partir de la requête
        $page = max(1, $request->query->getInt('page', 1));
        $limit = max(1, $request->query->getInt('limit', 10));
    
        // Crée une requête pour récupérer tous les produits
        $queryBuilder = $productRepository->createQueryBuilder('p');
        $query = $queryBuilder->getQuery();
    
        // Utilise le pagineur pour paginer les résultats
        $pagination = $paginator->paginate(
            $query,
            $page,
            $limit
        );
    
        // Ajoute des liens HATEOAS à chaque produit de la liste
        $data = array_map(function($product) {
            return [
                'data' => $product,
                'links' => [
                    'self' => $this->generateUrl('app_product_id', ['id' => $product->getId()]),
                    'list' => $this->generateUrl('app_product')
                ]
            ];
        }, $pagination->getItems());
    
        // Prépare les données pour la réponse JSON
        $responseData = [
            'items' => $data,
            'total' => $pagination->getTotalItemCount(),
            'current_page' => $pagination->getCurrentPageNumber(),
            'total_pages' => $pagination->getPaginationData()['pageCount']
        ];
    
        // Sérialise les données en format JSON
        $jsonProductList = $serializer->serialize($responseData, 'json');
    
        // Crée une réponse JSON avec les données sérialisées
        $response = new JsonResponse($jsonProductList, JsonResponse::HTTP_OK, [], true);
    
        // Définit la réponse comme publique et définit la durée de mise en cache à 3600 secondes
        $response->setPublic();
        $response->setMaxAge(3600);
    
        return $response;
    }

    
    /**
     * Récupère un produit par son ID.
     *
     * Cette méthode retourne les détails d'un produit spécifique par son ID.
     *
     * @OA\Response(
     *     response=200,
     *     description="Retourne les détails du produit",
     *     @OA\JsonContent(
     *        type="object",
     *        ref=@Model(type=Product::class)
     *     )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID du produit à récupérer",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Product")
     * @Security(name="Bearer")
     */
    #[Route('api/products/{id}', name: 'app_product_id', methods: ['GET'])]
    public function getProductById(Product $product, SerializerInterface $serializer): JsonResponse
    {
        // Ajoute des liens HATEOAS au produit
        $data = $this->hateoas->addLinks($product, [
            'self' => ['name' => 'app_product_id', 'params' => ['id' => $product->getId()]],
            'list' => ['name' => 'app_product']
        ]);

        // Sérialise les données en format JSON
        $jsonProduct = $serializer->serialize($data, 'json');
        $response = new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);

        // Définit la réponse comme publique et définit la durée de mise en cache à 3600 secondes
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }

    /**
     * Met à jour un produit spécifié par son ID.
     *
     * Cette méthode permet de mettre à jour les détails d'un produit existant. 
     * Elle accepte un corps de requête JSON contenant les nouveaux attributs du produit.
     *
     * @OA\RequestBody(
     *     required=true,
     *     description="Les données du produit à mettre à jour",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="modele", type="string"),
     *        @OA\Property(property="marque", type="string"),
     *        @OA\Property(property="prix", type="number"),
     *        @OA\Property(property="description", type="string"),
     *        @OA\Property(property="stock", type="integer"),
     *        @OA\Property(property="ram", type="integer"),
     *        @OA\Property(property="capaciteStockage", type="integer"),
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Produit mis à jour avec succès",
     *     @OA\JsonContent(ref=@Model(type=Product::class))
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID du produit à mettre à jour",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Product")
     * @Security(name="Bearer")
     */
    #[Route('api/products/{id}', name: 'app_product_update', methods: ['PUT'])]
    public function updateProduct(Request $request, Product $product, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupère les données de la requête
        $data = json_decode($request->getContent(), true);

        // Vérifie si les données sont valides
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        // Met à jour les attributs du produit
        $product->setModele($data['modele'] ?? null);
        $product->setMarque($data['marque'] ?? null);
        $product->setPrix($data['prix'] ?? null);
        $product->setDescription($data['description'] ?? null);
        $product->setStock($data['stock'] ?? null);
        $product->setRam($data['ram'] ?? null);
        $product->setCapaciteStockage($data['capaciteStockage'] ?? null);

        $entityManager->flush();

        // Ajoute des liens HATEOAS au produit
        $response = $this->hateoas->addLinks($product, [
            'self' => ['name' => 'app_product_id', 'params' => ['id' => $product->getId()]],
            'list' => ['name' => 'app_product']
        ]);

        // Sérialise les données en format JSON
        $jsonProduct = $serializer->serialize($response, 'json');
        $response = new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);

        // Définit la réponse comme publique et définit la durée de mise en cache à 3600 secondes
        $response->setPublic();
        $response->setMaxAge(3600);

         // Retourne une réponse JSON avec un code d'état HTTP 200
        return new JsonResponse(['message' => 'Produit mis à jour avec succès'], Response::HTTP_OK);
    }


    /**
     * Crée un nouveau produit.
     *
     * Cette méthode permet de créer un nouveau produit.
     * Elle accepte un corps de requête JSON contenant les attributs du nouveau produit.
     * 
     * @OA\RequestBody(
     *     required=true,
     *     description="Les données du produit à mettre à jour",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="modele", type="string"),
     *        @OA\Property(property="marque", type="string"),
     *        @OA\Property(property="prix", type="number"),
     *        @OA\Property(property="description", type="string"),
     *        @OA\Property(property="stock", type="integer"),
     *        @OA\Property(property="ram", type="integer"),
     *        @OA\Property(property="capaciteStockage", type="integer"),
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Produit créé avec succès",
     *     @OA\JsonContent(ref=@Model(type=Product::class))
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID du produit à mettre à jour",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Product")
     * @Security(name="Bearer")
     */
    #[Route('api/products', name: 'app_product_create', methods: ['POST'])]
    public function createProduct(Request $request, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupère les données de la requête
        $data = json_decode($request->getContent(), true);

        // Vérifie si les données sont valides
        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        // Crée un nouveau produit
        $product = new Product();
        $product->setModele($data['modele'] ?? null);
        $product->setMarque($data['marque'] ?? null);
        $product->setPrix($data['prix'] ?? null);
        $product->setDescription($data['description'] ?? null);
        $product->setStock($data['stock'] ?? null);
        $product->setRam($data['ram'] ?? null);
        $product->setCapaciteStockage($data['capaciteStockage'] ?? null);

        $entityManager->persist($product);
        $entityManager->flush();

        //  Ajoute des liens HATEOAS au produit
        $response = $this->hateoas->addLinks($product, [
            'self' => ['name' => 'app_product_id', 'params' => ['id' => $product->getId()]],
            'list' => ['name' => 'app_product']
        ]);

        // Sérialise les données en format JSON
        $jsonProduct = $serializer->serialize($response, 'json');
        $response = new JsonResponse($jsonProduct, Response::HTTP_CREATED, [], true);

        $response->setPublic();
        $response->setMaxAge(3600);

        return new JsonResponse(['message' => 'Produit créé avec succès'], Response::HTTP_OK);
    }

     /**
     * Supprime un produit spécifié par son ID.
     *
     * Cette méthode permet de supprimer un produit existant.
     * Elle accepte un ID de produit et supprime le produit correspondant.
     * 
     * @OA\RequestBody(
     *     required=true,
     *     description="Les données du produit à mettre à jour",
     *     @OA\JsonContent(
     *        type="object",
     *        @OA\Property(property="modele", type="string"),
     *        @OA\Property(property="marque", type="string"),
     *        @OA\Property(property="prix", type="number"),
     *        @OA\Property(property="description", type="string"),
     *        @OA\Property(property="stock", type="integer"),
     *        @OA\Property(property="ram", type="integer"),
     *        @OA\Property(property="capaciteStockage", type="integer"),
     *     )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Produit supprimé avec succès",
     *     @OA\JsonContent( type="null" )
     * )
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="ID du produit à supprimer",
     *     required=true,
     *     @OA\Schema(type="integer")
     * )
     * @OA\Tag(name="Product")
     * @Security(name="Bearer")
     */
    #[Route('api/products/{id}', name: 'app_product_delete', methods: ['DELETE'])]
    public function deleteProduct(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        // Récupère le produit par son ID
        $product = $entityManager->getRepository(Product::class)->find($id);

        // Vérifie si le produit existe
        if (!$product) {
            return new JsonResponse(['error' => 'Produit non enregistré.'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($product);
        $entityManager->flush();
        
        // Affiche un message de validation
        return new JsonResponse(['message' => 'Produit supprimé avec succès.'], Response::HTTP_OK);
    }
}
