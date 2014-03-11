<?php

namespace AC\WebServicesBundle\Fixture;

abstract class CachedMongoFixture extends CachedFixture
{
    private $templateLoaded = false;
    private $objMan = null;

    final public function loadInto($container)
    {
        $this->objMan = $container->get('doctrine_mongodb')->getManager();

        $databases = [];
        foreach ($this->objMan->getMetadataFactory()->getAllMetadata() as $m) {
            if ($m->isMappedSuperclass) { continue; }
            $db = $this->objMan->getDocumentDatabase($m->name);
            $databases[$db->getName()] = $db;
        }

        foreach ($databases as $db) {
            $db->drop();
        }

        if ($this->templateLoaded) {
            foreach ($databases as $db) {
                $db->command([
                    "copydb" => 1,
                    "fromdb" => $db->getName() . "___TEMPLATE",
                    "todb" => $db->getName()
                ]);
            }
        } else {
            $this->execFixture();
            $this->templateLoaded = true;

            foreach ($databases as $db) {
                $db->command([
                    "copydb" => 1,
                    "fromdb" => $db->getName(),
                    "todb" => $db->getName() . "___TEMPLATE"
                ]);
            }
        }

        $this->objMan = null;
    }

    final protected function getFixtureObjectManager()
    {
        return $this->objMan;
    }
}
