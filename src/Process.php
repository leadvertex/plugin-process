<?php
/**
 * Created for plugin-process
 * Datetime: 25.07.2019 12:00
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Components\Process;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Leadvertex\Plugin\Components\Process\Components\Error;
use Leadvertex\Plugin\Components\Process\Components\Init;
use Leadvertex\Plugin\Components\Process\Components\Result;
use Leadvertex\Plugin\Components\Process\Components\Skip;
use Leadvertex\Plugin\Components\Process\Components\Success;
use TypeError;

class Process
{

    /**
     * @var string
     */
    private $id;
    /**
     * @var string
     */
    private $initUrl;
    /**
     * @var string
     */
    private $successUrl;
    /**
     * @var string
     */
    private $errorUrl;
    /**
     * @var string
     */
    private $skipUrl;
    /**
     * @var string
     */
    private $resultUrl;
    /**
     * @var Client
     */
    private $client;

    public function __construct(
        string $id,
        string $initUrl,
        string $successUrl,
        string $errorUrl,
        string $skipUrl,
        string $resultUrl
    )
    {
        $this->id = $id;
        $this->initUrl = $initUrl;
        $this->successUrl = $successUrl;
        $this->errorUrl = $errorUrl;
        $this->skipUrl = $skipUrl;
        $this->resultUrl = $resultUrl;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param Init $init
     * @throws GuzzleException
     */
    public function init(Init $init): void
    {
        $this->getClient()->request('post', $this->initUrl, ['json' => [
            'count' => $init->getCount()
        ]]);
    }

    /**
     * @param Success $success
     * @throws GuzzleException
     */
    public function success(Success $success): void
    {
        if ($success->getCount() > 0) {
            $this->getClient()->request('post', $this->successUrl, ['json' => [
                'count' => $success->getCount()
            ]]);
        }
    }

    /**
     * @param Error[] $errors
     * @throws GuzzleException
     */
    public function error(array $errors): void
    {
        $requestErrors = [];
        foreach ($errors as $error) {
            if (!($error instanceof Error)) {
                throw new TypeError('Error should be instance of ' . Error::class);
            }

            $requestErrors[] = [
                'message' => $error->getMessage()->get(),
                'entityId' => $error->getEntityId(),
            ];
        }

        if (count($requestErrors) > 0) {
            $this->getClient()->request('post', $this->errorUrl, ['json' => [
                'errors' => $requestErrors
            ]]);
        }
    }

    /**
     * @param Skip $skip
     * @throws GuzzleException
     */
    public function skip(Skip $skip): void
    {
        if ($skip->getCount() > 0) {
            $this->getClient()->request('post', $this->skipUrl, ['json' => [
                'count' => $skip->getCount()
            ]]);
        }
    }

    /**
     * @param Result $result
     * @throws GuzzleException
     */
    public function result(Result $result): void
    {
        $this->getClient()->request('post', $this->skipUrl, ['json' => [
            'count' => $result->get()
        ]]);
    }

    private function getClient(): Client
    {
        if (!$this->client) {
            $this->client = new Client([
                'headers' => [
                    'User-Agent' => 'lv-plugin-process',
                    'X-Process-Id' => $this->id,
                ],
            ]);
        }
        return $this->client;
    }


}