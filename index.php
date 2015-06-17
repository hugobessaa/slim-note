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
  $result = false;

  // notes object
  $note = array(
    'title' => $_POST['title'],  
    'content' => $_POST['content'],
    'tags' => explode(',', $_POST['tags'])
  );

  // insert query
  $note_insert = run_query('INSERT INTO notes (title, content) '.
            'VALUES (\''.$note['title'].'\', \''.$note['content'].'\')');

  // insert successful
  if ($note_insert) {
    $note_id = get_last_note_id();

    // insert tags
    $tags_insert = insert_tags($note['tags'], $note_id);
    $result = $tags_insert;
  }

  // echo var_dump($result);
  // response 303 for success and 500 for error
  $app->response->redirect('/', $result ? 303 : 500);
});

/**
 * General helpers
 */

// Simple mysql query function
// FIXME: no SQL injection protection
function run_query($query) {
  $connection = mysqli_connect('localhost', 'root', 'root', 'slim-note');

  if (mysqli_connect_errno()) {
    echo 'connect error';
    return false;
  }

  $result = mysqli_query($connection, $query);
  mysqli_close($connection);

  return $result;
}

/**
 * Note helpers
 */

function get_last_note_id() {
  $last_note_query = run_query('SELECT id FROM notes ORDER BY id DESC LIMIT 1');
  if ($last_note_query) {
    $last_note = $last_note_query->fetch_array();
    return $last_note['id'];
  }

  return false;
}

/**
 * Tags helpers
 */

function insert_tags($tags, $note_id) {
  $sanitized_tags = sanitize_tags($tags);

  // insert each tag
  foreach($sanitized_tags as $tag) {
    $tag_id = get_tag_id($tag);
    if (!$tag_id) { continue; }

    insert_tag_note($tag_id, $note_id);
  }
}

function sanitize_tags($tags) {
  $trimmed = array_map(function($tag) {
    return trim($tag);
  }, $tags);

  $non_empty = array_filter($trimmed, function($tag) {
    return !empty($tag);
  });

  return $non_empty;
}

function get_tag_id($tag) {
  $tag_id = false;
  $tag_select = run_query('SELECT id FROM tags WHERE title = \''.$tag.'\'');

  // tag exists
  if ($tag_select->num_rows > 0) {
    $tag_id = $tag_select->fetch_array()['id'];

  // tag must be created
  } else {
    $tag_insert = run_query('INSERT INTO tags (title) VALUES (\''.$tag.'\')');
    if (!$tag_insert) { return false; }

    $tag_id = get_tag_id($tag);
  }

  return $tag_id;
}

function insert_tag_note($tag_id, $note_id) {
  // does this relationship already exists?
  $tag_note_count = run_query('SELECT COUNT(*) AS count FROM tag_note '.
                              'WHERE tag_id=\''.$tag_id.'\' AND note_id=\''.$note_id.'\'');
  $count = (int) $tag_note_count->fetch_array()['count'];
  if ($count > 0) { return false; }

  // insert new tag <-> note relationship
  $tag_note_insert = run_query('INSERT INTO tag_note (tag_id, note_id) '.
                               'VALUES (\''.$tag_id.'\', \''.$note_id.'\')');
  return $tag_note_insert;
}

// Run
$app->run();
