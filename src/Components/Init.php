<?php
/**
 * Created for plugin-process
 * Datetime: 25.07.2019 12:15
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Components\Process\Components;


class Init
{

    /**
     * @var int
     */
    private $count;

    public function __construct(?int $count)
    {
        $this->count = $count;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }
}