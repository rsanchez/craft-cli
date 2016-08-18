<?php

namespace CraftCli\Console;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputDefinition;
use RuntimeException;

class GlobalArgvInput extends ArgvInput
{
    /**
     * Original state of tokens given to constructor
     * @var array
     */
    protected $originalTokens;

    /**
     * {@inheritdoc}
     */
    public function __construct(array $argv = null, InputDefinition $definition = null)
    {
        if (null === $argv) {
            $argv = $_SERVER['argv'];
        }

        $tokens = $argv;

        // strip the application name
        array_shift($tokens);

        $this->originalTokens = $tokens;

        parent::__construct($argv, $definition);
    }

    /**
     * {@inheritdoc}
     */
    protected function parse()
    {
        $parsed = $tokens = $this->originalTokens;

        while (null !== $token = array_shift($parsed)) {
            $this->setTokens(array($token));
            try {
                parent::parse();
            } catch (RuntimeException $e) {
                // ignore these errors, otherwise re-throw it
                if (! preg_match('/^Too many arguments\.$|^No arguments expected|does not exist\.$/', $e->getMessage())) {
                    throw $e;
                }
            }
        }

        $this->setTokens($tokens);
    }
}
