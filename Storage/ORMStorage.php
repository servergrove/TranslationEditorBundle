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
     * {{@inheritdoc}}
     */
    public function findLocaleList(array $criteria = array())
    {
        $repository = $this->manager->getRepository(self::CLASS_LOCALE);
        $builder    = $repository->createQueryBuilder('l');

        $this->hydrateCriteria($builder, $criteria);

        return $builder->getQuery()->getResult();
    }

    /**
     * {{@inheritdoc}}
     */
    public function createLocale($language, $country = null)
    {
        $localeClass = self::CLASS_LOCALE;
        $locale      = new $localeClass;

        $locale->setLanguage($language);
        $locale->setCountry($country);
        $locale->setActive(true);

        return $this->persist($locale);
    }

    /**
     * {{@inheritdoc}}
     */
    public function findEntryList(array $criteria = array())
    {
        $repository = $this->manager->getRepository(self::CLASS_ENTRY);
        $builder    = $repository->createQueryBuilder('e');

        $builder->leftJoin('e.translations', 't')
                ->leftJoin('t.locale', 'l');

        $this->hydrateCriteria($builder, $criteria);

        return $builder->getQuery()->getResult();
    }

    /**
     * {{@inheritdoc}}
     */
    public function createEntry($domain, $fileName, $alias)
    {
        $entryClass = self::CLASS_ENTRY;
        $entry      = new $entryClass;

        $entry->setDomain($domain);
        $entry->setFileName($fileName);
        $entry->setAlias($alias);

        return $this->persist($entry);
    }

    /**
     * {{@inheritdoc}}
     */
    public function deleteEntry($id)
    {
        try {
            $entryProxy = $this->manager->getReference(self::CLASS_ENTRY, $id);

            $this->manager->remove($entryProxy);
            $this->manager->flush();

            return true;
        } catch (\Exception $e) {
            return $e->getMessage();

            // Do nothing
        }

        return false;
    }

    /**
     * {{@inheritdoc}}
     */
    public function findTranslationList(array $criteria = array())
    {
        $repository = $this->manager->getRepository(self::CLASS_TRANSLATION);
        $builder    = $repository->createQueryBuilder('t');

        $this->hydrateCriteria($builder, $criteria);

        return $builder->getQuery()->getResult();
    }

    /**
     * {{@inheritdoc}}
     */
    public function createTranslation($locale, $entry, $value)
    {
        $translationClass = self::CLASS_TRANSLATION;
        $translation      = new $translationClass;

        $translation->setLocale($locale);
        $translation->setEntry($entry);
        $translation->setValue($value);

        return $this->persist($translation);
    }

    public function persist($entity)
    {
        $this->manager->persist($entity);
        $this->manager->flush();

        return $entity;
    }

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