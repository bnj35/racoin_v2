<?php
require 'vendor/autoload.php';

use Controller\Annonce\GetCategorieController;
use Controller\Annonce\GetDepartementController;
use Controller\Annonce\IndexController;
use Controller\Annonce\AddItemController;
use Controller\Annonce\ItemController;
use Controller\Annonce\SearchController;
use Controller\Annonce\ViewAnnonceurController;
use Controller\Api\KeyGeneratorController;
use Database\Connection;

use Model\Annonce;
use Model\Categorie;
use Model\Annonceur;
use Model\Departement;
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

Connection::CreateConnection();

// Initialisation de Slim
$responseFactory = new \Slim\Psr7\Factory\ResponseFactory();
$app = new App($responseFactory);
$app->addErrorMiddleware(true, true, true);

// Initialisation de Twig
$loader = new FilesystemLoader(__DIR__ . '/template');
$twig = new Environment($loader);

// Ajout d'un middleware pour le trailing slash
$app->add(function (Request $request, RequestHandler $handler) : Response {
    $uri = $request->getUri();
    $path = $uri->getPath();
    if ($path != '/' && str_ends_with($path, '/')) {
        $uri = $uri->withPath(substr($path, 0, -1));
        if ($request->getMethod() == 'GET') {
            $response = new \Slim\Psr7\Response();
            return $response->withHeader('Location', (string)$uri)->withStatus(301);
        } else {
            return $handler->handle($request->withUri($uri));
        }
    }
    return $handler->handle($request);
});

if (!isset($_SESSION)) {
    session_start();
    $_SESSION['formStarted'] = true;
}

if (!isset($_SESSION['token'])) {
    $token = md5(uniqid(rand(), true));
    $_SESSION['token'] = $token;
    $_SESSION['token_time'] = time();
} else {
    $token = $_SESSION['token'];
}

$menu = [
    [
        'href' => './index.php',
        'text' => 'Accueil'
    ]
];

$chemin = dirname($_SERVER['SCRIPT_NAME']);

$categorieController = new GetCategorieController();
$departementController = new GetDepartementController();

$app->get('/', function (Request $request, Response $response) use ($twig, $menu, $chemin, $categorieController) {
    $indexController = new IndexController();
    $indexController->displayAllAnnonce($twig, $menu, $chemin, $categorieController->getCategories());
    return $response;
});

$app->get('/item/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $categorieController) {
    $itemId = $args['n'];
    $itemController = new ItemController();
    $itemController->afficherItem($twig, $menu, $chemin, $itemId, $categorieController->getCategories());
    return $response;
});

$app->get('/add', function (Request $request, Response $response) use ($twig, $menu, $chemin, $categorieController, $departementController) {
    $addItemController = new AddItemController();
    $addItemController->addItemView($twig, $menu, $chemin, $categorieController->getCategories(), $departementController->getAllDepartments());
    return $response;
});

$app->post('/add', function (Request $request, Response $response) use ($twig, $menu, $chemin) {
    $allPostVars = $request->getParsedBody();
    $addItemController = new AddItemController();
    $addItemController->addNewItem($twig, $menu, $chemin, $allPostVars);
    return $response;
});

$app->get('/item/{id}/edit', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin) {
    $itemId = $args['id'];
    $itemController = new ItemController();
    $itemController->modifyGet($twig, $menu, $chemin, $itemId);
    return $response;
});

$app->post('/item/{id}/edit', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $categorieController, $departementController) {
    $itemId = $args['id'];
    $allPostVars = $request->getParsedBody();
    $itemController = new ItemController();
    $itemController->modifyPost($twig, $menu, $chemin, $itemId, $allPostVars, $categorieController->getCategories(), $departementController->getAllDepartments());
    return $response;
});

$app->map(['GET', 'POST'], '/item/{id}/confirm', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin) {
    $itemId = $args['id'];
    $allPostVars = $request->getParsedBody();
    $itemController = new ItemController();
    $itemController->edit($twig, $menu, $chemin, $itemId, $allPostVars);
    return $response;
});

$app->get('/search', function (Request $request, Response $response) use ($twig, $menu, $chemin, $categorieController) {
    $searchController = new SearchController();
    $searchController->show($twig, $menu, $chemin, $categorieController->getCategories());
    return $response;
});

$app->post('/search', function (Request $request, Response $response) use ($twig, $menu, $chemin, $categorieController) {
    $searchParams = $request->getParsedBody();
    $searchController = new SearchController();
    $searchController->research($searchParams, $twig, $menu, $chemin, $categorieController->getCategories());
    return $response;
});

$app->get('/annonceur/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $categorieController) {
    $annonceurId = $args['n'];
    $viewAnnonceurController = new ViewAnnonceurController();
    $viewAnnonceurController->afficherAnnonceur($twig, $menu, $chemin, $annonceurId, $categorieController->getCategories());
    return $response;
});

$app->get('/delete/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin) {
    $itemId = $args['n'];
    $itemController = new ItemController();
    $itemController->supprimerItemGet($twig, $menu, $chemin, $itemId);
    return $response;
});

$app->post('/delete/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $categorieController) {
    $itemId = $args['n'];
    $itemController = new ItemController();
    $itemController->supprimerItemPost($twig, $menu, $chemin, $itemId, $categorieController->getCategories());
    return $response;
});

$app->get('/categorie/{n}', function (Request $request, Response $response, array $args) use ($twig, $menu, $chemin, $categorieController) {
    $categorieId = $args['n'];
    $categorieController = new GetCategorieController();
    $categorieController->displayCategorie($twig, $menu, $chemin, $categorieController->getCategories(), $categorieId);
    return $response;
});

$app->get('/api(/)', function (Request $request, Response $response) use ($twig, $menu, $chemin, $categorieController) {
    $template = $twig->load('api.html.twig');
    $menu = [
        [
            'href' => $chemin,
            'text' => 'Accueil'
        ],
        [
            'href' => $chemin . '/api',
            'text' => 'Api'
        ]
    ];
    $response->getBody()->write($template->render(['breadcrumb' => $menu, 'chemin' => $chemin]));
    return $response;
});

$app->group('/api', function () use ($app, $twig, $menu, $chemin, $categorieController) {

    $app->group('/annonce', function () use ($app) {

        $app->get('/{id}', function (Request $request, Response $response, array $args) use ($app) {
            $annonceId = $args['id'];
            $annonceList = ['id_annonce', 'id_categorie as categorie', 'id_annonceur as annonceur', 'id_departement as departement', 'prix', 'date', 'titre', 'description', 'ville'];
            $annonce = Annonce::select($annonceList)->find($annonceId);

            if (isset($annonce)) {
                $response = $response->withHeader('Content-Type', 'application/json');
                $annonce->categorie = Categorie::find($annonce->categorie);
                $annonce->annonceur = Annonceur::select('email', 'nom_annonceur', 'telephone')->find($annonce->annonceur);
                $annonce->departement = Departement::select('id_departement', 'nom_departement')->find($annonce->departement);
                $links = [];
                $links['self']['href'] = '/api/annonce/' . $annonce->id_annonce;
                $annonce->links = $links;
                $response->getBody()->write($annonce->toJson());
                return $response;
            } else {
                $response->getBody()->write('Not Found');
                return $response->withStatus(404);
            }
        });
    });

    $app->group('/annonces(/)', function () use ($app) {

        $app->get('/', function (Request $request, Response $response) use ($app) {
            $annonceList = ['id_annonce', 'prix', 'titre', 'ville'];
            $response = $response->withHeader('Content-Type', 'application/json');
            $annonces = Annonce::all($annonceList);
            $links = [];
            foreach ($annonces as $annonce) {
                $links['self']['href'] = '/api/annonce/' . $annonce->id_annonce;
                $annonce->links = $links;
            }
            $links['self']['href'] = '/api/annonces/';
            foreach ($annonces as $annonce) {
                $annonce->links = $links;
            }
            $response->getBody()->write($annonces->toJson());
            return $response;
        });
    });

    $app->group('/categorie', function () use ($app) {

        $app->get('/{id}', function (Request $request, Response $response, array $args) use ($app) {
            $categorieId = $args['id'];
            $response = $response->withHeader('Content-Type', 'application/json');
            $annonces = Annonce::select('id_annonce', 'prix', 'titre', 'ville')->where('id_categorie', '=', $categorieId)->get();
            $links = [];

            foreach ($annonces as $annonce) {
                $links['self']['href'] = '/api/annonce/' . $annonce->id_annonce;
                $annonce->links = $links;
            }

            $categorie = Categorie::find($categorieId);
            $links['self']['href'] = '/api/categorie/' . $categorieId;
            $categorie->links = $links;
            $categorie->annonces = $annonces;
            $response->getBody()->write($categorie->toJson());
            return $response;
        });
    });

    $app->group('/categories(/)', function () use ($app) {
        $app->get('/', function (Request $request, Response $response) use ($app) {
            $response = $response->withHeader('Content-Type', 'application/json');
            $categories = Categorie::get();
            $links = [];
            foreach ($categories as $categorie) {
                $links['self']['href'] = '/api/categorie/' . $categorie->id_categorie;
                $categorie->links = $links;
            }
            $links['self']['href'] = '/api/categories/';
            $categories->links = $links;
            $response->getBody()->write($categories->toJson());
            return $response;
        });
    });

    $app->get('/key', function (Request $request, Response $response) use ($twig, $menu, $chemin, $categorieController) {
        $keyGeneratorController = new KeyGeneratorController();
        $keyGeneratorController->show($twig, $menu, $chemin, $categorieController->getCategories());
        return $response;
    });

    $app->post('/key', function (Request $request, Response $response) use ($twig, $menu, $chemin, $categorieController) {
        $nom = $request->getParsedBody()['nom'];
        $keyGeneratorController = new KeyGeneratorController();
        $keyGeneratorController->generateKey($twig, $menu, $chemin, $categorieController->getCategories(), $nom);
        return $response;
    });
});

$app->run();