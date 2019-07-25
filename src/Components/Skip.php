<?php
/**
 * Created for plugin-process
 * Datetime: 25.07.2019 12:24
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Process\Components;


class Skip
{

    /**
     * @var int
     */
    private $count;

    public function __construct(int $count)
    {
        $this->count = $count;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }

}