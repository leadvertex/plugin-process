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
use Leadvertex\Plugin\Components\Process\Components\Result\ResultInterface;
use Leadvertex\Plugin\Components\Process\Components\Skipped;
use Leadvertex\Plugin\Components\Process\Components\Handled;
use Leadvertex\Plugin\Components\Process\Exceptions\AlreadyInitializedException;
use Leadvertex\Plugin\Components\Process\Exceptions\NotInitializedException;
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
    private $handleUrl;
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
    /**
     * @var Init
     */
    private $init = null;

    public function __construct(
        string $id,
        string $initUrl,
        string $handleUrl,
        string $errorUrl,
        string $skipUrl,
        string $resultUrl
    )
    {
        $this->id = $id;
        $this->initUrl = $initUrl;
        $this->handleUrl = $handleUrl;
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
     * @throws AlreadyInitializedException
     * @throws GuzzleException
     */
    public function initWebhook(Init $init): void
    {
        if ($this->init !== null) {
            throw new AlreadyInitializedException("Process '{$this->getId()}' already initialized");
        }

        $this->init = $init;
        $this->getClient()->request('post', $this->initUrl, ['json' => [
            'count' => $init->getCount()
        ]]);
    }

    /**
     * @param Handled $handled
     * @throws GuzzleException
     * @throws NotInitializedException
     */
    public function handleWebhook(Handled $handled): void
    {
        $this->guardNotInitialized();
        if ($handled->getCount() > 0) {
            $this->getClient()->request('post', $this->handleUrl, ['json' => [
                'count' => $handled->getCount()
            ]]);
        }
    }

    /**
     * @param Error[] $errors
     * @throws GuzzleException
     * @throws NotInitializedException
     */
    public function errorWebhook(array $errors): void
    {
        $this->guardNotInitialized();
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
     * @param Skipped $skipped
     * @throws GuzzleException
     * @throws NotInitializedException
     */
    public function skipWebhook(Skipped $skipped): void
    {
        $this->guardNotInitialized();
        if ($skipped->getCount() > 0) {
            $this->getClient()->request('post', $this->skipUrl, ['json' => [
                'count' => $skipped->getCount()
            ]]);
        }
    }

    /**
     * @param ResultInterface $result
     * @throws GuzzleException
     * @throws NotInitializedException
     */
    public function resultWebhook(ResultInterface $result): void
    {
        $this->guardNotInitialized();
        $this->getClient()->request('post', $this->skipUrl, ['json' => [
            'type' => $result->getType(),
            'value' => $result->getValue(),
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

    /**
     * @throws NotInitializedException
     */
    private function guardNotInitialized()
    {
        if ($this->init === null) {
            throw new NotInitializedException("Process '{$this->getId()}' not yet initialized");
        }
    }


}