<?php

namespace AC\WebServicesBundle\Fixture;

abstract class CachedMongoFixture extends CachedFixture
{
    private $templateLoaded = false;
    private $objMan = null;
    private $collCopyMongo;

    public function __construct()
    {
        parent::__construct();

        $this->collCopyMongo = new \MongoCode(
            "function(src, tgt) {
                db[src].find().forEach(function(i){db[tgt].insert(i)})
            }"
        );
    }

    final protected function loadImpl($container)
    {
        $this->objMan = $container->get('doctrine_mongodb')->getManager();

        $databases = [];
        foreach ($this->objMan->getMetadataFactory()->getAllMetadata() as $m) {
            if ($m->isMappedSuperclass) { continue; }
            $db = $this->objMan->getDocumentDatabase($m->name);
            $databases[$db->getName()] = $db;
        }

        $savingTemplates = false;
        if (!$this->templateLoaded) {
            foreach ($databases as $db) {
                $db->drop();
            }
            $this->execFixture();
            $this->templateLoaded = true;
            $savingTemplates = true;
        }

        foreach ($databases as $db) {
            $srcPrefix = "TEMPLATE__";
            $tgtPrefix = "";
            if ($savingTemplates) {
                list($srcPrefix, $tgtPrefix) = [$tgtPrefix, $srcPrefix];
            }

            $collNames = $db->getMongoDB()->getCollectionNames();

            foreach ($collNames as $coll) {
                if (strpos($coll, "TEMPLATE__") === 0) {
                    continue;
                }
                $src = $srcPrefix . $coll;
                $tgt = $tgtPrefix . $coll;

                if (in_array($tgt, $collNames)) {
                    $db->getMongoDB()->selectCollection($tgt)->drop();
                }

                $db->getMongoDB()->execute($this->collCopyMongo, [$src, $tgt]);
            }
        }

        $this->objMan = null;
    }

    final protected function getFixtureObjectManager()
    {
        return $this->objMan;
    }

    final protected function getNamespaceAliases($objMan)
    {
        return $objMan->getConfiguration()->getDocumentNamespaces();
    }
}
