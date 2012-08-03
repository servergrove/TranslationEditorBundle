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
        $builder = $this->manager->createQueryBuilder();
        $builder
            ->select('l')->from($this->getLocaleClassName(), 'l')
            ->addSelect('t')->leftJoin('l.translations', 't')
            ->addSelect('e')->leftJoin('t.entry', 'e');

        $this->hydrateCriteria($builder, $criteria);

        return $builder->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findEntryList(array $criteria = array())
    {
        $builder = $this->manager->createQueryBuilder();
        $builder
            ->select('e')->from($this->getEntryClassName(), 'e')
            ->addSelect('t')->leftJoin('e.translations', 't')
            ->addSelect('l')->leftJoin('t.locale', 'l');

        $this->hydrateCriteria($builder, $criteria);

        return $builder->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function findTranslationList(array $criteria = array())
    {
        $builder = $this->manager->createQueryBuilder();
        $builder
            ->select('t')->from($this->getTranslationClassName(), 't')
            ->addSelect('e')->leftJoin('t.entry', 'e')
            ->addSelect('l')->leftJoin('t.locale', 'l');

        $this->hydrateCriteria($builder, $criteria);

        return $builder->getQuery()->getResult();
    }

    /**
     * Populate a criteria builder
     *
     * @param \Doctrine\ORM\QueryBuilder $builder
     * @param array $criteria
     */
    protected function hydrateCriteria($builder, array $criteria = array())
    {
        $parameterIndex = 1;

        foreach ($criteria as $fieldName => $fieldValue) {
            $fieldName = sprintf('%s.%s', $builder->getRootAlias(), $fieldName);

            switch ($fieldValue) {
                case null:
                    $builder->andWhere(sprintf('%s IS NULL', $fieldName));
                    break;

                default:
                    $builder->andWhere(sprintf('%s = ?%d', $fieldName, $parameterIndex));
                    $builder->setParameter($parameterIndex, $fieldValue);

                    $parameterIndex++;
                    break;
            }
        }
    }
}
