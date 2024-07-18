# Projet API BileMo

## Table des matières
- [Description du projet](#description-du-projet)
- [Besoins du client](#besoins-du-client)
- [Instructions d'installation](#instructions-dinstallation)
- [Documentation de l'API](#documentation-de-lapi)
- [Diagrammes UML](#diagrammes-uml)
- [Utilisation des DataFixtures](#utilisation-des-datafixtures)
- [Tests avec Postman](#tests-avec-postman)
- [Licence](#licence)
  
## Description du projet
BileMo est une entreprise offrant une large sélection de téléphones mobiles haut de gamme. Le modèle commercial de BileMo n'est pas de vendre ses produits directement sur le site web, mais de fournir l'accès au catalogue via une API à toutes les plateformes intéressées. Il s'agit donc exclusivement d'un modèle de vente B2B (business-to-business).

En tant que développeur, vous êtes responsable de créer la vitrine des téléphones mobiles de BileMo. Vous devez exposer un certain nombre d'APIs pour que les applications des autres plateformes puissent effectuer des opérations.

## Besoins du client
Le premier client a signé un contrat de partenariat avec BileMo. Suite à une réunion dense avec le client, plusieurs besoins ont été identifiés :

1. Consulter la liste des produits BileMo.
2. Consulter les détails d'un produit BileMo.
3. Consulter la liste des utilisateurs liés à un client sur le site web.
4. Consulter le détail d'un utilisateur lié à un client.
5. Ajouter un nouvel utilisateur lié à un client.
6. Supprimer un utilisateur ajouté par un client.

Seuls les clients référencés peuvent accéder aux APIs. Les clients de l'API doivent être authentifiés via OAuth ou JWT. Vous pouvez soit mettre en place un serveur OAuth et y faire appel (en utilisant FOSOAuthServerBundle), soit utiliser Facebook, Google ou LinkedIn. Si vous décidez d'utiliser JWT, vous devez vérifier la validité du token et l'usage d'une bibliothèque est autorisé.

## Présentation des données
Le premier partenaire de BileMo est très exigeant : il requiert que vous exposiez vos données en suivant les règles des niveaux 1, 2 et 3 du modèle de Richardson. Il a demandé à ce que vous serviez les données en JSON. Si possible, le client souhaite que les réponses soient mises en cache afin d'optimiser les performances des requêtes vers l'API.

## Instructions d'installation
Pour installer et exécuter le projet localement, suivez ces étapes :

1. Clonez le repository :
   ```bash
   git clone https://github.com/username/nom_du_repo.git
   cd nom_du_repo
   
2. Installez les dépendances :
   ```bash
   composer install

3. Configurez les variables d'environnement :
   ```bash
   cp .env.example .env

4. Générez les clés JWT :
   ```bash
   mkdir -p config/jwt
   openssl genrsa -out config/jwt/private.pem -aes256 4096
   openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

5. Exécutez les migrations de base de données :
   ```bash
   php bin/console doctrine:migrations:migrate

6. Chargez les fixtures de données :
   ```bash
   php bin/console doctrine:fixtures:load

7. Démarrez le serveur local :
   ```bash
   symfony server:start


### Documentation de l'API
La documentation de l'API est disponible ici : http://127.0.0.1:8000/api/doc

Cette documentation inclut :

Les endpoints pour lister les produits
Les endpoints pour consulter les détails des produits
Les endpoints pour gérer les utilisateurs
Les méthodes d'authentification
Pour plus de détails sur l'utilisation de l'API, veuillez consulter le lien de la documentation fourni ci-dessus.

## Diagrammes-uml
Les diagrammes sont disponibles à la racine du projet dans le dossier diagrammes.

## Utilisation des DataFixtures
Les DataFixtures sont utilisées pour peupler la base de données avec des données de test. Pour charger les fixtures de données, exécutez la commande suivante :

bash
Copier le code
php bin/console doctrine:fixtures:load
Cela remplira la base de données avec des produits, des utilisateurs et d'autres données nécessaires pour les tests et le développement.


## Tests avec Postman
Les tests de l'API sont réalisés avec Postman. Vous pouvez importer la collection de requêtes Postman disponible dans la documentation pour tester les différents endpoints de l'API. Cette collection inclut des exemples de requêtes pour :

- Lister les produits
- Consulter les détails d'un produit
- Ajouter un utilisateur
- Supprimer un utilisateur
- Pour importer la collection dans Postman :

## Licence
Ce projet est sous licence MIT. Voir le fichier LICENSE pour plus de détails.
