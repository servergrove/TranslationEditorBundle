<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Storage;

use Doctrine\Common\Persistence\ObjectManager;

/**
 * Doctrine ORM Storage
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
abstract class AbstractStorage
{
    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
     */
    protected $manager;

    /**
     * Constructor
     *
     * @param \Doctrine\Common\Persistence\ObjectManager $manager
     */
    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }
}