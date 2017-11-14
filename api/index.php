<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';
require '../controller/database_pgsql.php';

$app = new \Slim\App;
$app->get('/hello/{name}', function (Request $request, Response $response) {
    $name = $request->getAttribute('name');
    $response->getBody()->write("Hello, $name");

    return $response;
});
$app->get('/graph/{local}/{day}-{year}-{month}', function (Request $request, Response $response) {
	require '../controller/historico_de_consumo_mes.php';
});
$app->run();
?>
