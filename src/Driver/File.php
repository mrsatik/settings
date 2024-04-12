<?php
namespace mrsatik\Settings\Driver;

use InvalidArgumentException;
use mrsatik\Settings\Driver\FileInterface;
use mrsatik\Settings\Driver\BuilderInterface;

class File implements FileInterface, BuilderInterface
{
    private $variables;

    const ENV_FILE_NAME = '.env';

    public function __construct()
    {
        $this->variables = [];
    }

    /**
     * {@inheritDoc}
     * @see BuilderInterface::getVariable()
     */
    public function getVariable($varname): ?string
    {
        if (isset($this->variables[$varname])) {
            return $this->variables[$varname];
        } else {
            $this->variables = $this->readFile();
            if (isset($this->variables[$varname])) {
                return $this->variables[$varname];
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     * @see FileInterface::getFileName()
     */
    public function getFileName(): string
    {
        return self::ENV_FILE_NAME;
    }

    /**
     * возвращает список переменных
     * @return array[]
     */
    private function readFile()
    {
        $variables = [];
        $pathToFile = $this->getRootPath() . $this->getFileName();
        $fh = fopen($pathToFile,'r');
        if (!is_resource($fh)) {
            throw new InvalidArgumentException('File not exists: ' . $pathToFile);
        }

        do {
            $line = fgets($fh);
            if ($line !== false) {
                $line = trim($line);

                if ($line !== '' && strstr($line, '=')) {
                    if (!preg_match('/^(?P<key>.+)(?:\s=\s)(?:["\']{0,1})(?P<value>[^\'"]*)(?:["\']{0,1})$/iu', $line, $keyValue)) {
                        preg_match('/^(?P<key>.+)=(?:["\']{0,1})(?P<value>[^\'"]*)(?:["\']{0,1})$/iu', $line, $keyValue);
                    }

                    if (isset($keyValue['key']) && trim($keyValue['key']) !== '' && isset($keyValue['value'])) {
                        $variables[trim($keyValue['key'])] = trim($keyValue['value']);
                    }
                }
            }
        } while($line !== false);
        fclose($fh);

        return $variables;
    }

    /**
     * @return string
     */
    private function getRootPath()
    {
        $documentRoot = strstr(__DIR__, '/vendor/') ?
            preg_replace ('/^(.+)vendor.+$/iu', '\\1', __DIR__) :
            __DIR__ . '/../../tests/fixtures';

        $documentRoot = realpath($documentRoot) . '/';

        return $documentRoot;
    }
}