<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('/pdfcontroller', 'PdfController::index');
$routes->post('/pdfcontroller/upload', 'PdfController::upload');

$routes->get('/info', 'InfoController::index');

$routes->get('/CropController', 'CropController::index');
$routes->post('/CropController/crop', 'CropController::crop');

$routes->get('/CropController2', 'CropController2::index');
$routes->post('/CropController2/upload', 'CropController2::upload');
