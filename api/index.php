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
$app->get('/graph/{local}/{year}-{month}-{day}', function (Request $request, Response $response) {
	$year = $request->getAttribute("year");
	$day = $request->getAttribute("day");
	$month = $request->getAttribute("month");
	$local = $request->getAttribute("local");
	require '../controller/historico_de_consumo_mes.php';
	return $newResponse;
});
$app->run();
?>
