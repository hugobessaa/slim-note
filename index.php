<?php
require 'vendor/autoload.php';

$app = new \Slim\Slim();

// Config
$app->config(array(
  'debug' => true,
  'templates.path' => __DIR__ . '/templates',
  'view' => new \Slim\Views\Twig()
));

// View config
$view = $app->view();
$view->parserOptions = array(
  'debug' => true,
  'cache' => dirname(__FILE__) . '/cache'
);

// Routes
$app->get('/', function() use ($app) {
  $notes = array('yo', 'now', 'wat');
  $app->view->setData(array(
    'notes' => $notes
  ));

  $app->render('index.html');
});

// Run
$app->run();
