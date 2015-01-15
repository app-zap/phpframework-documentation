# Mailing

PHPFrameworks comes with tools to send mails.

## Basic usage

    $mailService = new \AppZap\PHPFramework\Mail\MailService();
    $message = \AppZap\PHPFramework\Mail\MailMessage::newInstance();
    $message->addTo($recipient);
    $message->setFrom($senderMail, $senderName);
    $message->setSubject($subject);
    $message->setBody($content);
    $mailService->send($message);

## Background

We run [swiftmailer](https://github.com/swiftmailer/swiftmailer) under the hood to send mails. The `MailMessage` class is simply extending the `Swift_Message` class. Therefore you can use any [options](http://swiftmailer.org/docs/messages.html) it has.

The `MailService` is completely configurable throught. `settings.ini` options. See [Configuration](in-depth_documentation/configuration.md) for details.
