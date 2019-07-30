<?php
/**
 * Created for plugin-process
 * Datetime: 30.07.2019 17:32
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Components\Process\Components\Result;


class ResultUrl implements ResultInterface
{

    /**
     * @var string
     */
    private $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function getType(): string
    {
        return 'url';
    }

    public function getValue()
    {
        return $this->url;
    }
}