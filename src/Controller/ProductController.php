<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ProductController extends AbstractController
{
    #[Route('api/product', name: 'app_product')]
    public function getAllProduct(ProductRepository $productRepository): JsonResponse
    {
        $productlist = $productRepository->getAllProduct();

        return new JsonResponse([
            'products' => $productlist
        ]);
    }
}
