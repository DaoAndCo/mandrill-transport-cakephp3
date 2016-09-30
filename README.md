# MandrillTransport plugin for CakePHP 3+

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org).

The recommended way to install composer packages is:

```
composer require orken/mandrill-transport-cakephp3
```

## Setting up your CakePHP application
In your bootstrap.php

```
Plugin::load('MandrillTransport');
```


In your app.php file.

```
'EmailTransport' => [
  'Mandrill' => [
    'className'      => 'MandrillTransport.Mandrill',
    'api_key'        => 'YOUR_API_KEY',
    'api_key_test'   => 'YOUR_TEST_API_KEY',
    'from'           => 'no-reply@example.com',
    'merge_language' => 'handlebars', //optional, default is handlebars
    'inline_css'     => true, //optional, default is true
  ],
],
'Email' => [
    'mandrill' => [
        'transport' => 'Mandrill',
        'from' => 'you@localhost',
        //'charset' => 'utf-8',
        //'headerCharset' => 'utf-8',
    ],
],
```

## Utilisation

It can be used like normal Mail transport in cakephp.

If you want to use a template from mailchimp/mandrill, you just have to add the key '*template_name*' with the name of the template in your *viewVars*. And optionnaly other vars which are used in the template.

```
$email = new Email('mandrill');
$email->from(['me@example.com' => 'My Site'])
    ->to('you@example.com')
    ->cc('yourcc@exmaple.com') // optional
    ->bcc('yourbcc@exmaple.com') // optional
    ->attachments('/path/to/your/file') // optional
    ->viewVars([
      'template_name' => 'your template name at mandrill',
      'other_var' => 'values', // all the vars from yout template
      ...
    ])
    ->subject('About')  // optional, if missing it takes the template subject
    ->send('My message');
```