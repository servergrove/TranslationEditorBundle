<?php

namespace ServerGrove\Bundle\TranslationEditorBundle;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAware;

class MongoStorageManager extends ContainerAware
{
    protected $mongo;

    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }


       function getMongo()
       {
           if (!$this->mongo) {
               $this->mongo = new \Mongo($this->container->getParameter('translation_editor.mongodb'));
           }

           if (!$this->mongo) {
               throw new \Exception("failed to connect to mongo");
           }
           return $this->mongo;
       }

      public function getDB()
      {
          return $this->getMongo()->translations;
      }

      public function getCollection()
      {
          return $this->getDB()->selectCollection($this->container->getParameter('translation_editor.collection'));
      }
}