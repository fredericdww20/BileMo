<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('api/product', name: 'app_product')]
    public function getAllProduct(ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
    {
        $productlist = $productRepository->getAllProduct();

        $jsonproductlist = $serializer->serialize($productlist, 'json');
        return new JsonResponse($jsonproductlist, Response::HTTP_OK, [], true);
    }

    #[Route('api/product/{id}', name: 'app_product_id')]
    public function getProductById(ProductRepository $productRepository, SerializerInterface $serializer, int $id): JsonResponse
    {
     $product = $productRepository->getProductById($id);
     if ($product) {
         $jsonproduct = $serializer->serialize($product, 'json');
         return new JsonResponse($jsonproduct, Response::HTTP_OK, [], true);
     }
     
     return new JsonResponse('Product not found', Response::HTTP_NOT_FOUND);
    }
}
