<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->match(['get', 'post'], '/', 'Home::index');
$routes->get('/accueil', 'Home::accueil');
$routes->match(['get', 'post'], '/recherche', 'RechercheEtudiant::index');
$routes->match(['get', 'post'], '/ajouter', 'Home::ajouter');
$routes->match(['get', 'post'], '/importer', 'Home::importer');
$routes->get('/deconnexion', 'Home::logout');
$routes->post('/modifier', 'RechercheEtudiant::modifier');
