<?php

namespace App;

require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Funct\Collection;
use function functions\auth;
use function functions\getLeadsThisMonth;
use function functions\addLead;
use function functions\addContact;
use function functions\bindLeadContact;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    if ($_SESSION['subdomain']) {
        $_SESSION['leads'] = getLeadsThisMonth($_SESSION['subdomain'])['_embedded']['items'];
    }
    $message = Collection\flattenAll($this->get('flash')->getMessages())[0];
    $_SESSION['message'] = $message;
    return $this->get('renderer')->render($response, 'index.phtml');
});

$app->post('/auth', function ($request, $response) {
    $user = $request->getParsedBody()['user'];
    $login = $user['login'];
    $subdomain = $user['subdomain'];
    $apiKey = $user['apiKey'];
    if (auth($login, $apiKey, $subdomain)) {
        $message = 'Авторизация успешна';
    } else {
        $message = 'Ошибка авторизации';
    }
    $this->get('flash')->addMessage('auth', $message);
    return $response->withStatus(302)->withHeader('Location', '/');
});

$app->post('/bindLeadContact', function ($request, $response) {
    $lead = $request->getParsedBody()['lead'];
    $contact = $request->getParsedBody()['contact'];
    $leadName = $lead['name'];
    $leadSale = $lead['sale'];
    $contactName = $contact['name'];
    $subdomain = $_SESSION['subdomain'];

    if ($lead['name'] && $contact['name']) {
        $idAddedContact = addContact($subdomain, $contactName)['_embedded']['items'][0]['id'];
        $idAddedLead = addLead($subdomain, $leadName, $leadSale)['_embedded']['items'][0]['id'];
        bindLeadContact($subdomain, $idAddedContact, $idAddedLead);
        $message = "Сделка №{$idAddedLead} и контакт №{$idAddedContact} успешно созданы и соединены";
    } elseif ($lead['name']) {
        $idAddedLead = addLead($subdomain, $leadName, $leadSale)['_embedded']['items'][0]['id'];
        $message = "Сделка №{$idAddedLead} успешно создана";
    } elseif ($contact['name']) {
        $idAddedContact = addContact($subdomain, $contactName)['_embedded']['items'][0]['id'];
        $message = "Контакт №{$idAddedContact} успешно создан";
    } else {
        $message = "Поле 'Имя сделки' или 'Имя контакта' должны быть запонены!";
    }

    $this->get('flash')->addMessage('bindLeadContact', $message);
    return $response->withStatus(302)->withHeader('Location', '/');
});

$app->run();