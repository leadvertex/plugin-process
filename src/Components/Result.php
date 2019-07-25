<?php
/**
 * Created for plugin-process
 * Datetime: 25.07.2019 15:34
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Process\Components;


class Result
{

    /**
     * @var bool
     */
    private $success;

    public function __construct(bool $success)
    {
        $this->success = $success;
    }

    /**
     * @return bool
     */
    public function get(): bool
    {
        return $this->success;
    }

}