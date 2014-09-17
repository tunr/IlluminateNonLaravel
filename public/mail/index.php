<?php

/**
 * Illuminate/Mail
 *
 * todo: provide a module description
 *
 * Requires: illuminate/container
 *           illuminate/log
 *           illuminate/support
 *           illuminate/view
 *           swiftmailer/swiftmailer
 *
 * @source https://github.com/illuminate/mail
 */

require_once '../../vendor/autoload.php';

use Swift_Mailer as SwiftMailer;
use Swift_SmtpTransport as SmtpTransport;
use Swift_SendmailTransport as SendmailTransport;
use Swift_MailTransport as MailTransport;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Factory;
use Illuminate\Events\Dispatcher;
use Illuminate\Mail\Mailer;
use Illuminate\Log\Writer;
use Monolog\Logger;

$app = new \Slim\Slim();

$app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware);

$app->get('/', function ()
{
    // SMTP configuration is pulled from web server environnent variables
    $host     = getenv('SMTP_HOST');
    $port     = getenv('SMTP_PORT');
    $username = getenv('SMTP_USERNAME');
    $password = getenv('SMTP_PASSWORD');

    // chose a transport (PHP Mail, Sendmail, SMTP)
    // $transport = MailTransport::newInstance();
    // $transport = SendmailTransport::newInstance('/usr/sbin/sendmail -bs');
    $transport = SmtpTransport::newInstance($host, $port);

    // SMTP specific configuration, remove these if you're not using SMTP
    $transport->setUsername($username);
    $transport->setPassword($password);
    $transport->setEncryption(true);

    $swift    = new SwiftMailer($transport);
    $finder   = new FileViewFinder(new Filesystem, [__DIR__ . '/../../app/views']);
    $resolver = new EngineResolver;

    // determine which template engine to use
    $resolver->register('php', function()
    {
        return new PhpEngine;
    });

    $view   = new Factory($resolver, $finder, new Dispatcher());
    $mailer = new Mailer($view, $swift);
    $logger = new Writer(new Logger('local'));

    // note: make sure log file is writable
    $logger->useFiles('../../logs/laravel.log');

    $mailer->setLogger($logger);
    // $mailer->setQueue($app['queue']); // queue functionality is not available if the queue module is not set
    // $mailer->setContainer($app);      // the message builder must be a callback if the container is not set

    // pretend method can be used for testing
    $mailer->pretend(false);

    $data = [
        'greeting' => 'You have arrived, girl.',
    ];

    $mailer->send('email.welcome', $data, function($message)
    {
        $message->from(getenv('MAIL_FROM_ADDRESS'), 'Code Guy');
        $message->to(getenv('MAIL_TO_ADDRESS'), 'Keira Knightley');
        $message->subject('Yo!');
    });

    var_dump('Done');
});

$app->run();

