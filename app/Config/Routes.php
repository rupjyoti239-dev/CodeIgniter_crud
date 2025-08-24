<?php

use App\Controllers\Api\ProductController;
use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');



$routes->group('api/product', function ($routes) {
     $routes->post('add', [ProductController::class, 'addProduct']);
     $routes->get('list', [ProductController::class, 'list']);
     $routes->get('(:num)', [ProductController::class, 'product']);
     $routes->post('(:num)', [ProductController::class, 'updateProduct']);
     $routes->delete('(:num)', [ProductController::class, 'deleteProduct']);
});