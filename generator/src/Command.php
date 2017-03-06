<?php

namespace Smart\Geo\Generator;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\QuestionHelper;

abstract class Command extends SymfonyCommand
{
    /**
     * @var QuestionHelper
     */
    protected $question;

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container = null)
    {
        $this->question = new QuestionHelper();
        parent::__construct();
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        if (null === $this->container) {
            $this->container = new Container();
        }
        return $this->container;
    }

    /**
     * @param Container $container
     * @return $this
     */
    public function setRegistry(Container $container)
    {
        $this->container = $container;
        return $this;
    }
}
