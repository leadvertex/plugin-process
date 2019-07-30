<?php
/**
 * Created for plugin-process
 * Datetime: 30.07.2019 17:27
 * @author Timur Kasumov aka XAKEPEHOK
 */

namespace Leadvertex\Plugin\Components\Process\Components\Result;


interface ResultInterface
{

    public function getType(): string;

    public function getValue();

}