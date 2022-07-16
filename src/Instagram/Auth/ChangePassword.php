<?php

namespace Instagram\Auth;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Instagram\Exception\InstagramAuthException;
use Instagram\Exception\InstagramFetchException;
use Instagram\Utils\Endpoints;
use Instagram\Utils\InstagramHelper;
use Instagram\Utils\UserAgentHelper;

class ChangePassword
{
    public $client;
    public $session;
    public $currentPassword;
    public $newPassword;
    public $time;
    public $sharedData;

    public function __construct(
        ClientInterface $client,
        ?Session $session)
    {
        $this->client = $client;
        $this->session = $session;
        if (empty($this->session)) {
            throw new InstagramAuthException('Please login first');
        }
        $this->sharedData = $this->getSharedData();
    }

    private function getSharedData()
    {
        $cookies = $this->session->getCookies();

        $query = $this->client->request('GET', Endpoints::SHARED_DATA, [
            'headers' => [
                'user-agent'  => UserAgentHelper::AGENT_DEFAULT,
            ],
            'cookies' => $cookies,
        ]);

        return json_decode((string) $query->getBody(), true);
    }

    private function encrypt($password): string
    {
        return '#PWD_INSTAGRAM_BROWSER:0:' . time() . ':' . $password;
    }

    public function change($currentPassword, $newPassword)
    {
        $oldPassword = $this->encrypt($currentPassword);
        $newPassword = $this->encrypt($newPassword);
        $cookieJar = $this->session->getCookies();

        $options = [
            'form_params' => [
                'enc_old_password' => $oldPassword,
                'enc_new_password1' => $newPassword,
                'enc_new_password2' => $newPassword,
            ],
            'headers' => [
                'user-agent' => UserAgentHelper::AGENT_DEFAULT,
                'x-requested-with' => 'XMLHttpRequest',
                'x-instagram-ajax' => $this->sharedData['rollout_hash'],
                'x-csrftoken' => $this->sharedData['config']['csrf_token']
            ],
            'cookies' => $cookieJar,
        ];

        try {
            $query = $this->client->request('POST', Endpoints::CHANGE_PASSWORD, $options);

            $data = json_decode((string) $query->getBody());

            if ($data === null) {
                throw new InstagramFetchException(json_last_error_msg());
            }

            $data->cookies = $cookieJar;
            return $data;
        } catch (ClientException $exception) {
            return json_decode((string) $exception->getResponse()->getBody());
        }
    }
}