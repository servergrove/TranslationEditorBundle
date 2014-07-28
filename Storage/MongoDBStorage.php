<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Storage;

/**
 * Doctrine MongoDB Storage
 *
 * @author Ken Golovin <kengolovin@gmail.com>
 */
class MongoDBStorage extends AbstractStorage implements StorageInterface
{
    const CLASS_LOCALE      = 'ServerGrove\Bundle\TranslationEditorBundle\Document\Locale';
    const CLASS_ENTRY       = 'ServerGrove\Bundle\TranslationEditorBundle\Document\Entry';
    const CLASS_TRANSLATION = 'ServerGrove\Bundle\TranslationEditorBundle\Document\Translation';

    /**
     * {@inheritdoc}
     */
    protected function getLocaleClassName()
    {
        return self::CLASS_LOCALE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntryClassName()
    {
        return self::CLASS_ENTRY;
    }

    /**
     * {@inheritdoc}
     */
    protected function getTranslationClassName()
    {
        return self::CLASS_TRANSLATION;
    }

    /**
     * {@inheritdoc}
     */
    public function findLocaleList(array $criteria = array())
    {
        $builder = $this->manager->createQueryBuilder($this->getLocaleClassName());

        $this->hydrateCriteria($builder, $criteria);

        return iterator_to_array($builder->getQuery()->execute());
    }

    /**
     * {@inheritdoc}
     */
    public function findEntryList(array $criteria = array())
    {
        $builder = $this->manager->createQueryBuilder($this->getEntryClassName());

        $this->hydrateCriteria($builder, $criteria);

        return iterator_to_array($builder->getQuery()->execute());
    }

    /**
     * {@inheritdoc}
     */
    public function findTranslationList(array $criteria = array())
    {
        $builder = $this->manager->createQueryBuilder($this->getTranslationClassName());

        if(isset($criteria['locale']) && $criteria['locale'] instanceof \ServerGrove\Bundle\TranslationEditorBundle\Document\Locale) {
            $criteria['locale'] = $criteria['locale']->getId();
        }
        if(isset($criteria['entry']) && $criteria['entry'] instanceof \ServerGrove\Bundle\TranslationEditorBundle\Document\Entry) {
            $criteria['entry'] = $criteria['entry']->getId();
        }
        
        $this->hydrateCriteria($builder, $criteria);

        return iterator_to_array($builder->getQuery()->execute());
    }

    /**
     * Populate a criteria builder
     *
     * @param \Doctrine\ODM\MongoDB\Query\Builder $builder
     * @param array $criteria
     */
    protected function hydrateCriteria($builder, array $criteria = array())
    {
        foreach ($criteria as $fieldName => $fieldValue) {
            $builder->addOr($builder->expr()->field($fieldName)->equals($fieldValue));
        }
    }
}
