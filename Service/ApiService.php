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
    private function get($uri, $query = [])
    {
        return $this->request('get', $uri, $query);
    }

    /**
     * @param $uri
     * @param array $formParams
     * @return mixed
     */
    private function post($uri, $formParams = [])
    {
        return $this->request('post', $uri, $formParams);
    }

    /**
     * @param $uri
     * @param array $formParams
     * @return mixed
     */
    private function patch($uri, $formParams = [])
    {
        return $this->request('patch', $uri, $formParams);
    }

    /**
     * @param $uri
     * @param array $formParams
     * @return mixed
     */
    private function delete($uri, $formParams = [])
    {
        return $this->request('delete', $uri, $formParams);
    }

    /**
     * Peform an API request.
     *
     * @param string $method
     *   The request method: get, post, patch or delete.
     * @param string $uri
     *   The URI to request.
     * @param array $data
     *   Array of request data.
     *
     * @return array
     *   The response body.
     */
    private function request(string $method, string $uri, array $data = [])
    {
        $dataKey = 'form_params';
        $query = [];

        switch ($method) {
            case 'get':
                $query = $data;
                $data = [];
                break;

            case 'post':
            case 'patch':
                $dataKey = RequestOptions::JSON;
                break;
        }

        $client = $this->getClient();
        $data = [
            'client_token' => $this->clientToken,
            'user_token' => $this->userToken
        ] + $data;

        $response = $client->$method(
            $this->host . $uri,
            [
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'query' => $query,
                $dataKey => $data,
            ]
        );

        if ($contents = $response->getBody()->getContents()) {
            return json_decode($contents, true);
        }

        return [];
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
    public function createAccount($name, $serverId, array $sshKeyIds = [])
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
    public function createApplication(
        $accountId,
        $name,
        $aliases = [],
        $documentrootSuffix = 'current',
        $technology = 'php-fpm'
    ) {
        return $this->post(
            '/applications',
            [
                'account_id' => $accountId,
                'name' => $name,
                'aliases' => $aliases,
                'documentroot_suffix' => $documentrootSuffix,
                'technology' => $technology,
            ]
        );
    }

    /**
     * @param $applicationId
     * @param string $alias
     * @return mixed
     */
    public function removeApplicationAlias(
        $applicationId,
        $alias
    ) {
        return $this->delete(
            '/applications/' . $applicationId . '/remove_alias',
            [
                'alias' => $alias
            ]
        );
    }

    /**
     * @param $applicationId
     * @param string $alias
     * @return mixed
     */
    public function addApplicationAlias(
        $applicationId,
        $alias
    ) {
        return $this->post(
            '/applications/' . $applicationId . '/add_alias',
            [
                'alias' => $alias
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
        return $this->post(
            '/databases',
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
     * @param $databaseId
     * @param $login
     */
    public function updateDatabaseLogin($databaseId, $login, $password)
    {
        $this->patch('/databases/' . $databaseId . '/update_login', ['login' => $login, 'password' => $password]);
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

    /**
     * @param $name
     * @param $serverId
     * @return mixed
     */
    public function getEvents($type, $id)
    {
        return $this->get('/'.$type.'/'.$id.'/events');
    }
}
