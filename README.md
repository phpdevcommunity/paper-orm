# PaperORM - A Simple and Lightweight PHP ORM
PaperORM is a PHP ORM designed for projects requiring a lightweight yet performant object-relational mapping solution.

## 📖 Documentation

- [English](#english)
- [Français](#français)

## English

PaperORM is a PHP ORM designed for projects requiring a lightweight yet performant object-relational mapping solution. Specifically developed for PHP 7.4 and above, it positions itself as a lighter alternative to existing solutions.

At just 3MB compared to Doctrine's 75MB with dependencies, PaperORM offers the essential features of a modern ORM while maintaining a minimal footprint. It includes:
- Database schema management
- Migration system
- Repository pattern

## Installation

PaperORM is available via **Composer** and installs in seconds.

### 📦 Via Composer (recommended)
```bash
composer require phpdevcommunity/paper-orm:1.0.17-alpha
```  

### 🔧 Minimal Configuration
Create a simple configuration file to connect PaperORM to your database:

```php
<?php
require_once 'vendor/autoload.php';

use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\PaperConfiguration;

// --- Basic SQLite configuration ---
$configuration = PaperConfiguration::fromArray([
    'driver' => 'sqlite',
    'user' => null,
    'password' => null,
    'memory' => true
], false); // Set to true to enable debug mode (logs queries and ORM operations)

// Basic configuration MySQL/Mariadb
$configuration = PaperConfiguration::fromArray([
            'driver' => 'mariadb',
            'host' => '127.0.0.1',
            'port' => 3306,
            'dbname' => 'paper_orm_test',
            'user' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
], false);  // Set to true to enable debug mode (logs queries and ORM operations)

// --- Optional event listener registration ---
// Called automatically before any entity creation
$configuration->withListener(PreCreateEvent::class, new App\Listener\PreCreateListener());

// --- Optional SQL logger ---
// Use any PSR-3 compatible logger (e.g. Monolog) to log all executed queries
$configuration->withLogger(new Monolog());

$em = EntityManager::createFromConfig($configuration);
```

✅ **PaperORM is now ready to use!**

*Note: PDO and corresponding database extensions must be enabled (pdo_mysql, pdo_sqlite, etc.).*

## Basic Usage

> **Note**: The `repository` attribute/method is optional. If none is defined, a dummy repository will be automatically generated.

### Defining an Entity

#### PHP 7.4 < 8

```php
use PaperORM\Entity\EntityInterface;
use PaperORM\Mapping\{PrimaryKeyColumn, StringColumn, BoolColumn, DateTimeColumn, OneToMany, JoinColumn};

class User implements EntityInterface
{
    private ?int $id = null;
    private string $name;
    private string $email;
    private bool $isActive = true;
    private \DateTime $createdAt;
    
    public static function getTableName(): string 
    {
        return 'users';
    }
    
    public static function columnsMapping(): array
    {
        return [
            (new PrimaryKeyColumn())->bindProperty('id'),
            (new StringColumn())->bindProperty('name'),
            (new StringColumn())->bindProperty('email'),
            (new BoolColumn('is_active'))->bindProperty('isActive'), // 'is_active' is the column name
            (new DateTimeColumn('created_at'))->bindProperty('createdAt') // 'created_at' is the column name
        ];
    }
    
    // Getters/Setters...
}
```

#### PHP 8+ with attributes

```php
use PaperORM\Entity\EntityInterface;
use PaperORM\Mapping\{PrimaryKeyColumn, StringColumn, BoolColumn, DateTimeColumn, OneToMany, JoinColumn};

#[Entity(table : 'user', repository : null)]
class User implements EntityInterface
{
    #[PrimaryKeyColumn]
    private ?int $id = null;
    #[StringColumn]
    private string $name;
    #[StringColumn]
    private string $email;
    #[BoolColumn(name: 'is_active')]
    private bool $isActive = true;
    #[DateTimeColumn(name: 'created_at')]
    private \DateTime $createdAt;
   
    
    // Getters/Setters...
}
```

### CRUD Operations

**Fetching Entities:**
```php
// Get user by ID
$user = $entityManager->getRepository(User::class)->find(1);

// Filtered query
$users = $entityManager->getRepository(User::class)
    ->findBy()
    ->where('isActive', true)
    ->orderBy('name', 'ASC')
    ->limit(10)
    ->toArray();
```

**Insert/Update:**
```php
$newUser = new User();
$newUser->setName('Jean Dupont')
        ->setEmail('jean@example.com');

$entityManager->persist($newUser);
$entityManager->flush();
```

**Delete:**
```php
$user = $entityManager->getRepository(User::class)->find(1);
$entityManager->remove($user);
$entityManager->flush();
```

### Entity Relationships

```php
// OneToMany relationship
class Article 
{
    // IF PHP >= 8
    #[\PhpDevCommunity\PaperORM\Mapping\OneToMany(Comment::class, 'article')] 
    private \PhpDevCommunity\PaperORM\Collection\ObjectStorage $comments;
    
    public function __construct() {
        $this->comments = new ObjectStorage();
    }
    
    // ...
    public static function columnsMapping(): array
    {
        return [
            new OneToMany('comments', Comment::class, 'article')
        ];
    }
}

// Fetch with join
$articleWithComments = $entityManager->getRepository(Article::class)
    ->find(1)
    ->with('comments')
    ->toObject();
```

### Result Formats

```php
// Associative array
$userArray = $repository->find(1)->toArray();

// Entity object
$userObject = $repository->find(1)->toObject();

// Object collection
$activeUsers = $repository->findBy()
    ->where('isActive', true)
    ->toObject();
```

> PaperORM offers a simple API while covering the essential needs of a modern ORM.

## Beta Version - Contribute to Development

PaperORM is currently in **beta version** and actively evolving. We invite interested developers to:

### 🐞 Report Bugs
If you encounter issues, open a [GitHub issue](https://github.com/phpdevcommunity/paper-orm/issues) detailing:
- Context
- Reproduction steps
- Expected vs. actual behavior

### 💡 Suggest Improvements
Ideas for:
- Performance optimization
- API improvements
- New features

### 📖 Contribute to Documentation
Complete documentation is being written. You can:
- Fix errors
- Add examples
- Translate sections

**Note:** This version is stable for development use but requires additional testing for production.

---

*Active development continues - stay tuned for updates!*

## Français

PaperORM est un ORM PHP conçu pour les projets qui nécessitent une solution de mapping objet-relationnel légère et performante. Développé spécifiquement pour PHP 7.4 et versions ultérieures, il se positionne comme une alternative plus légère aux solutions existantes.

Avec seulement 3Mo contre 75Mo pour Doctrine avec ses dépendances, PaperORM propose les fonctionnalités essentielles d'un ORM moderne tout en conservant une empreinte minimale. Il intègre notamment :
- La gestion des schémas de base de données
- Un système de migrations
- Le pattern Repository


## Installation

PaperORM est disponible via **Composer** et s'installe en quelques secondes.

### 📦 Via Composer (recommandé)
```bash
composer require phpdevcommunity/paper-orm:1.0.17-alpha
```  

### 🔧 Configuration minimale
Créez un fichier de configuration simple pour connecter PaperORM à votre base de données :

```php
<?php
require_once 'vendor/autoload.php';

use PhpDevCommunity\PaperORM\EntityManager;
use PhpDevCommunity\PaperORM\PaperConfiguration;

// --- Basic SQLite configuration ---
$configuration = PaperConfiguration::fromArray([
    'driver' => 'sqlite',
    'user' => null,
    'password' => null,
    'memory' => true
], false); // Mettre à true pour activer le mode debug (journalisation des requêtes et opérations ORM)

// Basic configuration MySQL/Mariadb
$configuration = PaperConfiguration::fromArray([
            'driver' => 'mariadb',
            'host' => '127.0.0.1',
            'port' => 3306,
            'dbname' => 'paper_orm_test',
            'user' => 'root',
            'password' => 'root',
            'charset' => 'utf8mb4',
], false);  // Set to true to enable debug mode (logs queries and ORM operations)

// --- Enregistrement optionnel d’un écouteur d’événement ---
// Appelé automatiquement avant chaque création d’entité
$configuration->withListener(PreCreateEvent::class, new App\Listener\PreCreateListener());

// --- Journalisation SQL optionnelle ---
// Permet de journaliser toutes les requêtes exécutées via un logger compatible PSR-3 (ex. Monolog
$configuration->withLogger(new Monolog());

$em = EntityManager::createFromConfig($configuration);
```

✅ **PaperORM est maintenant prêt à être utilisé !**  

*Remarque : PDO et les extensions correspondantes à votre SGBD doivent être activées (pdo_mysql, pdo_sqlite, etc.).*

## Utilisation de base

> **Note**: L’attribut ou la méthode `repository` est facultatif. Si aucun n’est défini, un repository fictif sera généré automatiquement.

### Définition d'une entité

#### PHP 7.4 < 8

```php
use PaperORM\Entity\EntityInterface;
use PaperORM\Mapping\{PrimaryKeyColumn, StringColumn, BoolColumn, DateTimeColumn, OneToMany, JoinColumn};

class User implements EntityInterface
{
    private ?int $id = null;
    private string $name;
    private string $email;
    private bool $isActive = true;
    private \DateTime $createdAt;
    
    public static function getTableName(): string 
    {
        return 'users';
    }
    
    public static function columnsMapping(): array
    {
        return [
            new PrimaryKeyColumn('id'),
            new StringColumn('name'),
            new StringColumn('email'),
            new BoolColumn('isActive'),
            new DateTimeColumn('createdAt')
        ];
    }
    
    // Getters/Setters...
}
```

#### PHP 8+ avec attributs

```php
use PaperORM\Entity\EntityInterface;
use PaperORM\Mapping\{PrimaryKeyColumn, StringColumn, BoolColumn, DateTimeColumn, OneToMany, JoinColumn};

#[Entity(table : 'user', repository : null)]
class User implements EntityInterface
{
    #[PrimaryKeyColumn]
    private ?int $id = null;
    #[StringColumn]
    private string $name;
    #[StringColumn]
    private string $email;
    #[BoolColumn(name: 'is_active')]
    private bool $isActive = true;
    #[DateTimeColumn(name: 'created_at')]
    private \DateTime $createdAt;
   
    
    // Getters/Setters...
}
```

### Opérations CRUD

**Récupération d'entités :**
```php
// Récupérer un utilisateur par ID
$user = $entityManager->getRepository(User::class)->find(1);

// Requête avec filtres
$users = $entityManager->getRepository(User::class)
    ->findBy()
    ->where('isActive', true)
    ->orderBy('name', 'ASC')
    ->limit(10)
    ->toArray();
```

**Insertion/Mise à jour :**
```php
$newUser = new User();
$newUser->setName('Jean Dupont')
        ->setEmail('jean@example.com');

$entityManager->persist($newUser);
$entityManager->flush();
```

**Suppression :**
```php
$user = $entityManager->getRepository(User::class)->find(1);
$entityManager->remove($user);
$entityManager->flush();
```

### Relations entre entités

```php
// Relation OneToMany
class Article 
{
    // IF PHP >= 8
    #[\PhpDevCommunity\PaperORM\Mapping\OneToMany(Comment::class, 'article')] 
    private \PhpDevCommunity\PaperORM\Collection\ObjectStorage $comments;
    
    public function __construct() {
        $this->comments = new ObjectStorage();
    }
    
    // ...
    public static function columnsMapping(): array
    {
        return [
            new OneToMany('comments', Comment::class, 'article')
        ];
    }
}

// Récupération avec jointure
$articleWithComments = $entityManager->getRepository(Article::class)
    ->find(1)
    ->with('comments')
    ->toObject();
```

### Format des résultats

```php
// Tableau associatif
$userArray = $repository->find(1)->toArray();

// Objet entité
$userObject = $repository->find(1)->toObject();

// Collection d'objets
$activeUsers = $repository->findBy()
    ->where('isActive', true)
    ->toCollection();
```

> PaperORM propose une API simple tout en couvrant les besoins essentiels d'un ORM moderne.

## Version Bêta - Contribuez au développement

PaperORM est actuellement en **version bêta** et évolue activement. Nous invitons tous les développeurs intéressés à :

### 🐞 Signaler des bugs
Si vous rencontrez un problème, ouvrez une [issue GitHub](https://github.com/phpdevcommunity/paper-orm/issues) en détaillant :
- Le contexte
- Les étapes pour reproduire
- Le comportement attendu vs. observé

### 💡 Proposer des améliorations
Des idées pour :
- Optimiser les performances
- Améliorer l'API
- Ajouter des fonctionnalités

### 📖 Contribuer à la documentation
La documentation complète est en cours de rédaction. Vous pouvez :
- Corriger des erreurs
- Ajouter des exemples
- Traduire des sections

**Note** : Cette version est stable pour un usage en développement, mais nécessite des tests supplémentaires pour la production.

---

*Le développement actif continue - restez à l'écoute pour les mises à jour !*
