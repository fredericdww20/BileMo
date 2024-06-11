<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;



class ProductController extends AbstractController
{
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
    public function getAllProduct(ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
    {
        $productlist = $productRepository->getAllProduct();

        $jsonproductlist = $serializer->serialize($productlist, 'json');
        $response = new JsonResponse($jsonproductlist, Response::HTTP_OK, [], true);

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
        $jsonProduct = $serializer->serialize($product, 'json');
        $response = new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);

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
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

        $product->setModele($data['modele'] ?? null);
        $product->setMarque($data['marque'] ?? null);
        $product->setPrix($data['prix'] ?? null);
        $product->setDescription($data['description'] ?? null);
        $product->setStock($data['stock'] ?? null);
        $product->setRam($data['ram'] ?? null);
        $product->setCapaciteStockage($data['capaciteStockage'] ?? null);

        $entityManager->flush();

        $jsonProduct = $serializer->serialize($product, 'json');
        $response = new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);

        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
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
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return new JsonResponse(['error' => 'Invalid JSON body.'], Response::HTTP_BAD_REQUEST);
        }

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

        $jsonProduct = $serializer->serialize($product, 'json');
        $response = new JsonResponse($jsonProduct, Response::HTTP_CREATED, [], true);

        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
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
    public function deleteProduct(Product $product, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($product);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
