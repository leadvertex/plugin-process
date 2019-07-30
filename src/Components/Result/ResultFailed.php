<?php
/**
 * Created for plugin-process
 * Datetime: 30.07.2019 17:29
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Components\Process\Components\Result;


class ResultFailed implements ResultInterface
{

    public function getType(): string
    {
        return 'failed';
    }

    public function getValue()
    {
        return false;
    }
}