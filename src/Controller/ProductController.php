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
    #[Route('api/product', name: 'app_product', methods: ['GET'])]
    public function getAllProduct(ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
    {
        $productlist = $productRepository->getAllProduct();

        $jsonproductlist = $serializer->serialize($productlist, 'json');
        $response = new JsonResponse($jsonproductlist, Response::HTTP_OK, [], true);
        
        $response->setPublic();
        $response->setMaxAge(3600); 
        
        return $response;
    }

    #[Route('api/product/{id}', name: 'app_product_id', methods: ['GET'])]
    public function getProductById(Product $product, SerializerInterface $serializer): JsonResponse
    {
        $jsonProduct = $serializer->serialize($product, 'json');
        $response = new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
        
        $response->setPublic();
        $response->setMaxAge(3600); 
        
        return $response;
    }
    
    #[Route('api/productupdate/{id}', name: 'app_product_update', methods: ['PUT'])]
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

    #[Route('api/createproduct', name: 'app_product_create', methods: ['GET'])]
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

    #[Route('api/deleteproduct/{id}', name: 'app_product_delete', methods: ['DELETE'])]
    public function deleteProduct(Product $product, EntityManagerInterface $entityManager): JsonResponse
    {
        $entityManager->remove($product);
        $entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

}
