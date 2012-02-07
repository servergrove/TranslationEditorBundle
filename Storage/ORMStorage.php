<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Storage;

/**
 * Doctrine ORM Storage
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ORMStorage extends AbstractStorage implements StorageInterface
{
    const CLASS_LOCALE      = 'ServerGrove\Bundle\TranslationEditorBundle\Entity\Locale';
    const CLASS_ENTRY       = 'ServerGrove\Bundle\TranslationEditorBundle\Entity\Entry';
    const CLASS_TRANSLATION = 'ServerGrove\Bundle\TranslationEditorBundle\Entity\Translation';

    /**
     * Retrieve the Locale array
     *
     * @param boolean $onlyActive Optional (default=true)
     *
     * @return array
     */
    public function getLocaleList($onlyActive = true)
    {
        $repository = $this->manager->getRepository(self::CLASS_LOCALE);
        $builder    = $repository->createQueryBuilder('l');

        if ($onlyActive) {
            $builder->where('l.active = ?1');
            $builder->setParameter(1, true);
        }

        return $builder->getQuery()->getResult();
    }

    /**
     * Retireve the Entry array
     *
     * @return array
     */
    public function getEntryList()
    {
        $repository = $this->manager->getRepository(self::CLASS_ENTRY);
        $builder    = $repository->createQueryBuilder('e');

        $builder->leftJoin('e.translations', 't')
                ->leftJoin('t.locale', 'l');

        return $builder->getQuery()->getResult();
    }
}