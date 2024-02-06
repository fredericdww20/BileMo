<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\User;
use App\Entity\Client;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        for ($i=0; $i < 10; $i++) {
            $product = new Product();
            $product->setModele('Modele'. $i);
            $product->setMarque('Marque'. $i);
            $product->setPrix(mt_rand(10, 100));
            $product->setDescription('Description '. $i);
            $product->setStock(mt_rand(10, 1000));
            $product->setRam(mt_rand(1, 16));
            $product->setCapaciteStockage(mt_rand(64, 512).'Go');
            $manager->persist($product);
        }

        $listeUser = [];
        for ($i=0; $i < 10; $i++) {
            $client = new Client();
            $client->setUserName('Client'. $i);
            $client->setEmail('client'.$i.'@example.com');
            $client->setAdresse('Adresse '. $i);
            $client->setApiKey(bin2hex(random_bytes(10)));

            $listeUser[] = $client;
            $manager->persist($client);
        }

        for ($i=0; $i < 10; $i++) {
            $user = new User();
            $user->setUserName('User'. $i);
            $user->setPassword(hash('sha256', 'password' . $i));
            $user->setEmail('user'.$i.'@example.com');

            $user->setClient($listeUser[mt_rand(0, 9)]);
            $manager->persist($user);
        }

        $manager->flush();
    }

}