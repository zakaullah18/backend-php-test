<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addGlobal('user', $app['session']->get('user'));

    return $twig;
}));

$app->register(new Silex\Provider\SessionServiceProvider());

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html', [
        'readme' => file_get_contents('README.md'),
    ]);
});


$app->match('/login', function (Request $request) use ($app) {
    $username = $request->get('username');
    $password = $request->get('password');

    if ($username) {
        $sql = "SELECT * FROM users WHERE username = '$username' and password = '$password'";
        $user = $app['db']->fetchAssoc($sql);

        if ($user){
            $app['session']->set('user', $user);
            return $app->redirect('/todo');
        }
    }

    return $app['twig']->render('login.html', array());
});


$app->get('/logout', function () use ($app) {
    $app['session']->set('user', null);
    return $app->redirect('/');
});


$app->get('/todo/{id}', function ($id,Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    if ($id){
      // List Single ToDo
        $sql = "SELECT * FROM todos WHERE id = '$id'";
        $todo = $app['db']->fetchAssoc($sql);

        return $app['twig']->render('todo.html', [
            'todo' => $todo,
        ]);
    } else {
      // List All Todos
        $sql = "SELECT * FROM todos WHERE user_id = '${user['id']}' ";
        $todos = $app['db']->fetchAll($sql);

        return $app['twig']->render('todos.html', [
            'todos' => $todos
        ]);
    }
})
->value('id', null);


$app->post('/todo/add', function (Request $request) use ($app) {
    if (null === $user = $app['session']->get('user')) {
        return $app->redirect('/login');
    }

    $user_id = $user['id'];
    $description = $request->get('description');

    if ($description) {
      $sql = "INSERT INTO todos (user_id, description) VALUES ('$user_id', '$description')";
      $app['db']->executeUpdate($sql);
      $app['session']->getFlashBag()->add('success', 'Todo has been Added Successfully.');
    } else {

      $app['session']->getFlashBag()->add('warning', 'Description is required.');
    }

    return $app->redirect('/todo');
});


$app->match('/todo/delete/{id}', function ($id) use ($app) {

    $sql = "DELETE FROM todos WHERE id = '$id'";
    $app['db']->executeUpdate($sql);
    // $app['session']->getFlashBag()->add('warning', 'Todo has been Deleted Successfully.');
    // return $app->redirect('/todo');
    return true;
});


$app->match('/todo/status/{id}/{status}', function ($id, $status) use ($app) {

    if ($status == 'Pending'){
      $updatedStatus = 'Completed';
    }

    $sql = "UPDATE todos SET status = '$updatedStatus' WHERE id = '$id'";
    $app['db']->executeUpdate($sql);
    // $app['session']->getFlashBag()->add('info', 'Todo has been Completed Successfully.');
    // return $app->redirect('/todo');
    return true;
});


$app->match('/todo/{id}/json', function ($id) use ($app) {
      $sql = "SELECT * FROM todos WHERE id = '$id'";
      $todo = $app['db']->fetchAssoc($sql);

      return json_encode($todo);
 });
