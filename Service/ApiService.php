<?php


namespace DigipolisGent\Domainator9k\SockBundle\Service;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

/**
 * Class ApiService
 * @package DigipolisGent\Domainator9k\SockBundle\Service
 */
class ApiService
{

    private $host;
    private $clientToken;
    private $userToken;

    private static $client = null;

    public function setHost($host)
    {
        $this->host = $host;
    }

    public function setClientToken($clientToken)
    {
        $this->clientToken = $clientToken;
    }

    public function setUserToken($userToken)
    {
        $this->userToken = $userToken;
    }

    /**
     * @return Client|null
     */
    private function getClient()
    {
        if (!self::$client) {
            self::$client = new Client();
        }

        return self::$client;
    }

    /**
     * @param $uri
     * @return mixed
     */
    private function get($uri, $query = array())
    {
        $client = $this->getClient();

        $response = $client->get(
            $this->host . $uri,
            [
                'form_params' => [
                    'client_token' => $this->clientToken,
                    'user_token' => $this->userToken,
                ],
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'query' => $query,
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param $uri
     * @param array $formParams
     * @return mixed
     */
    private function post($uri, $formParams = array())
    {
        $client = $this->getClient();

        $formParams = array_merge($formParams, [
            'client_token' => $this->clientToken,
            'user_token' => $this->userToken
        ]);

        $response = $client->post(
            $this->host . $uri,
            [
                RequestOptions::JSON => $formParams,
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param $uri
     * @param array $formParams
     * @return mixed
     */
    private function delete($uri, $formParams = array())
    {
        $client = $this->getClient();

        $formParams = array_merge($formParams, [
            'client_token' => $this->clientToken,
            'user_token' => $this->userToken
        ]);

        $response = $client->delete(
            $this->host . $uri,
            [
                'form_params' => $formParams,
                'headers' => [
                    'Accept' => 'application/json',
                ]
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param string $name
     * @param string|null $serverId
     *
     * @return bool|Account
     */
    public function findAccountByName($name, $serverId = null)
    {
        $accounts = $this->get('/accounts');

        foreach ($accounts as $account) {
            if ($account['name'] === $name && ($serverId === null || $account['virtual_server_id'] == $serverId)) {
                return $account;
            }
        }

        return false;
    }

    /**
     * @param $name
     * @param $serverId
     * @return mixed
     */
    public function createAccount($name, $serverId, array $sshKeyIds = array())
    {
        return $this->post(
            '/accounts',
            [
                'name' => $name,
                'virtual_server_id' => $serverId,
                'ssh_key_ids' => $sshKeyIds
            ]
        );
    }

    /**
     * @param $serverId
     * @return mixed
     */
    public function getVirtualServer($serverId)
    {
        return $this->get('/virtual_servers/' . $serverId);
    }

    /**
     * @return mixed
     */
    public function getVirtualServers()
    {
        return $this->get('/virtual_servers');
    }

    /**
     * @param $serverId
     * @return mixed
     */
    public function getAccount($accountId)
    {
        return $this->get('/accounts/' . $accountId);
    }

    /**
     * @param $name
     * @param null $accountId
     * @return bool
     */
    public function findApplicationByName($name, $accountId = null)
    {
        $query = [];

        if ($accountId) {
            $query['account_id'] = $accountId;
        }

        $applications = $this->get('/applications', $query);

        foreach ($applications as $application) {
            if ($application['name'] === $name) {
                return $application;
            }
        }

        return false;
    }

    /**
     * @param $accountId
     * @param $name
     * @param array $aliases
     * @param string $documentrootSuffix
     * @return mixed
     */
    public function createApplication($accountId, $name, $aliases = array(), $documentrootSuffix = 'current')
    {
        return $this->post('/applications',
            [
                'account_id' => $accountId,
                'name' => $name,
                'aliases' => $aliases,
                'documentroot_suffix' => $documentrootSuffix,
            ]
        );
    }

    /**
     * @param $name
     * @param null $accountId
     * @return bool
     */
    public function findDatabaseByName($name, $accountId)
    {
        $query = [];
        $query['account_id'] = $accountId;

        $databases = $this->get('/databases', $query);

        foreach ($databases as $database) {
            if ($database['name'] === $name) {
                return $database;
            }
        }

        return false;
    }

    /**
     * @param $accountId
     * @param $name
     * @param $username
     * @param $password
     * @return mixed
     */
    public function createDatabase($accountId, $name, $username, $password)
    {
        return $this->post('/databases',
            [
                'account_id' => $accountId,
                'name' => $name,
                'login' => $username,
                'password' => $password,
            ]
        );
    }

    /**
     * @param $databaseId
     * @param $login
     */
    public function removeDatabaseLogin($databaseId, $login)
    {
        $this->delete('/databases/' . $databaseId . '/remove_login', ['login' => $login]);
    }

    /**
     * @param $databaseId
     * @param $login
     */
    public function addDatabaseLogin($databaseId, $login, $password)
    {
        $this->post('/databases/' . $databaseId . '/add_login', ['login' => $login, 'password' => $password]);
    }

    /**
     * @return mixed
     */
    public function getSshKeys()
    {
        return $this->get('/ssh_keys');
    }

    /**
     * @param $accountId
     */
    public function removeAccount($accountId)
    {
        $this->delete('/accounts/' . $accountId);
    }

    /**
     * @param $applicationId
     */
    public function removeApplication($applicationId)
    {
        $this->delete('/applications/' . $applicationId);
    }

    /**
     * @param $databaseId
     */
    public function removeDatabase($databaseId)
    {
        $this->delete('/databases/' . $databaseId);
    }
}
