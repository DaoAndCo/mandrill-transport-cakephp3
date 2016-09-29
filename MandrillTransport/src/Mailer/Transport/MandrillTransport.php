<?php
/**
 * Send mail using mail() function
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         2.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace MandrillTransport\Mailer\Transport;

use Cake\Mailer\AbstractTransport;
use Cake\Mailer\Email;
use Cake\Utility\Hash;
use Cake\Core\Configure;
use Cake\Network\Http\Client;
use Cake\Network\Exception\SocketException;


class MandrillTransport extends AbstractTransport {
  public $transportConfig = [
    'host'           => 'mandrillapp.com',
    'api_key'        => null,
    'api_key_test'   => null,
    'from'           => null,
    'merge_language' => 'handlebars',
    'inline_css'     => true,
    'subaccount'     => null
  ];

  public $defaultRequest = [
    'key'              => null,
    'template_name'    => null,
    'template_content' => [],
    'message'          => [],
    'async'            => false,
    'ip_pool'          => 'Main Pool',

  ];
  public $isDebug;

  public function send(Email $email) {
    $this->isDebug         = Configure::read('debug');
    $this->transportConfig = Hash::merge($this->transportConfig, $this->_config);
    $http = new Client([
      'host'    => $this->transportConfig['host'],
      'scheme'  => 'https',
      'headers' => [
        'User-Agent' => 'CakePHP Mandrill API Plugin'
      ]
    ]);
    $request = $this->defaultRequest;

    if (isset($email->viewVars['template_name']) && !empty($email->viewVars['template_name']))
    {
      $request['template_name']   = $email->viewVars['template_name'];
      $request['message']['tags'] = [$email->viewVars['template_name']];
      unset( $email->viewVars['template_name']);
    }

    if ($this->isDebug)
    {
      $request['key'] = (empty($this->transportConfig['api_key_test'])?$this->transportConfig['api_key']:$this->transportConfig['api_key_test']);
    } else {
      $request['message']['key'] = $this->transportConfig['api_key'];
    }

    $request['message'] += $this->_from($email);
    $request['message'] += $this->_to($email);
    $request['message'] += $this->_attachments($email);

    if (!empty($email->subject()))
    {
      $request['message']['subject'] = $email->subject();
    }

    foreach (['merge_language','inline_css','subaccount'] as $key) {
      $request['message'][$key] = $this->transportConfig[$key];
    }

    foreach ($email->viewVars as $key => $value)
    {
      $request['message']['global_merge_vars'][] = [
        'name' => $key,
        'content' => $value
      ];
    }
    $response = $http->post(
      '/api/1.0/messages/send-template.json',
      json_encode($request),
      ['type' => 'json']
    );
    if (!$response) {
      throw new SocketException($response->code);
    }
    return $response->json;
  }

  protected function _from(Email $email)
  {
    return [
      'from_email' => key($email->from()),
      'from_name' => current($email->from())
    ];
  }

  protected function _to(Email $email)
  {
    foreach (['to', 'cc', 'bcc'] as $type) {
        foreach ($email->{$type}() as $mail => $name) {
            $to['to'][] = [
                'email' => $mail,
                'name'  => $name,
                'type'  => $type
            ];
        }
    }
    return $to;
  }

  protected function _attachments(Email $email) {
    foreach ($email->attachments() as $filename => $file) {
      $content = base64_encode(file_get_contents($file['file']));
      if (isset($file['contentId'])) {
        $message['images'][] = [
          'type'    => $file['mimetype'],
          'name'    => $file['contentId'],
          'content' => $content,
        ];
      } else {
        $message['attachments'][] = [
          'type'    => $file['mimetype'],
          'name'    => $filename,
          'content' => $content,
          ];
        }
      }
      return $message;
  }


}
