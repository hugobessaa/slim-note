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

$app->post('/notes', function() use ($app) {
  // notes object
  $note = array(
    'title' => $_POST['title'],  
    'content' => $_POST['content']
  );

  // insert query
  $result = run_query('INSERT INTO notes (title, content) '.
            'VALUES (\''.$note['title'].'\', \''.$note['content'].'\')');

  // echo var_dump($result);
  // response 303 for success and 500 for error
  $app->response->redirect('/', $result ? 303 : 500);
});


// Simple mysql query function
// FIXME: no SQL injection protection
function run_query($query) {
  $connection = mysqli_connect('localhost', 'root', 'root', 'slim-note');

  if (mysqli_connect_errno()) {
    echo 'connect error';
    return false;
  }

  $result = mysqli_query($connection, $query);
  echo 'result'.$result;
  mysqli_close($connection);

  return $result;
}

// Run
$app->run();
