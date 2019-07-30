<?php
/**
 * Created for plugin-process
 * Datetime: 30.07.2019 17:29
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Components\Process\Components\Result;


class ResultSuccess implements ResultInterface
{

    public function getType(): string
    {
        return 'success';
    }

    public function getValue()
    {
        return true;
    }

}