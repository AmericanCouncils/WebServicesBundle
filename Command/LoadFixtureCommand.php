<?php

namespace AC\WebServicesBundle\Command;

use AC\FlagshipBundle\Document\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoadFixtureCommand extends Command
{
    protected function configure()
    {
        $this
        ->setName('web-services:load-fixture')
        ->setDescription('Loads data into database from a CachedFixture class')
        ->addArgument('fixtureClass', InputArgument::OPTIONAL, 'CachedFixture class', null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getApplication()->getKernel()->getContainer();

        if ($container->get('kernel')->getEnvironment() == 'prod') {
            throw new \RuntimeException(
                "You may not load fixture data into a production environment"
            );
        }

        $cls = $input->getArgument('fixtureClass');
        if (is_null($cls)) {
            $cls = $container->getParameter('ac_web_services.default_fixture_class');
        }
        if (is_null($cls)) {
            throw new \DomainException(
                "You must supply a class name, no default fixture class set"
            );
        }

        $f = new $cls;
        $f->loadInto($container);
    }
}
