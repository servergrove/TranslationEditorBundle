<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Response;

/**
 * Editor Controller
 */
class EditorController extends Controller
{
    /**
     * Index action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $kernelService  = $this->container->get('kernel');

        $sourcePath     = realpath($kernelService->getRootDir() . '/../src');
        $kernelDefaultLocale  = $this->container->getParameter('kernel.default_locale');

        // Retrieving mandatory information
        $localeList = $storageService->findLocaleList();
        $entryList  = $storageService->findEntryList();

        // Processing registered bundles
        $bundleList = array_filter(
            $kernelService->getBundles(),
            function ($bundle) use ($sourcePath) {
                return (strpos($bundle->getPath(), $sourcePath) === 0);
            }
        );

        // Processing default locale
        $defaultLocale = array_filter(
            $localeList,
            function ($locale) use ($kernelDefaultLocale) {
                return $locale->equalsTo($kernelDefaultLocale);
            }
        );
        $defaultLocale = reset($defaultLocale);

        return $this->render(
            'ServerGroveTranslationEditorBundle:Editor:index.html.twig',
            array(
                'bundleList'    => $bundleList,
                'localeList'    => $localeList,
                'entryList'     => $entryList,
                'defaultLocale' => $defaultLocale,
            )
        );
    }

    /**
     * Remove Translation action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeTranslationAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $request        = $this->getRequest();

        if ( ! $request->isXmlHttpRequest()) {
            return new RedirectResponse($this->generateUrl('sg_localeditor_index'));
        }

        try {
            $id     = $request->request->get('id');
            $status = $storageService->deleteEntry($id);

            $result = array(
                'result' => $status
            );
        } catch (\Exception $e) {
            $result = array(
                'result'  => false,
                'message' => $e->getMessage()
            );
        }

        return new Response(json_encode($result), 200, array(
            'Content-type' => 'application/json'
        ));
    }

    /**
     * Add Translation action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addTranslationAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $request        = $this->getRequest();

        // Retrieve variables
        $translations = $request->request->get('translations');
        $fileName     = $request->request->get('fileName');
        $domain       = $request->request->get('domain');
        $alias        = $request->request->get('alias');

        // Check for existent domain/alias
        $entryList =  $storageService->findEntryList(array(
            'domain' => $domain,
            'alias'  => $alias
        ));

        if (count($entryList)) {
            $result = array(
                'result'  => false,
                'message' => 'The alias already exists. Please update it instead.',
            );

            return new Response(json_encode($result));
        }

        // Create new Entry
        $entry = $storageService->createEntry($domain, $fileName, $alias);

        // Create Translations
        $translations = array_filter($translations);

        foreach ($translations as $localeId => $translationValue) {
            $locale = $storageService->findLocaleList(array('id' => $localeId));
            $locale = reset($locale);

            $storageService->createTranslation($locale, $entry, $translationValue);
        }

        // Return reponse according to request type
        if ( ! $request->isXmlHttpRequest()) {
            return new RedirectResponse($this->generateUrl('sg_localeditor_index'));
        }

        $result = array(
            'result'  => true,
            'message' => 'New translation added successfully. Reload list for completion.'
        );

        return new Response(json_encode($result));
    }

    /**
     * Update Translation action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function updateTranslationAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $request        = $this->getRequest();

        if ( ! $request->isXmlHttpRequest()) {
            return new RedirectResponse($this->generateUrl('sg_localeditor_index'));
        }

        $value = $request->request->get('value');

        $localeList = $storageService->findLocaleList(array('id' => $request->request->get('localeId')));
        $locale     = reset($localeList);

        $entryList  = $storageService->findEntryList(array('id' => $request->request->get('entryId')));
        $entry      = reset($entryList);

        $translationList = $storageService->findTranslationList(array('locale' => $locale, 'entry'  => $entry));
        $translation     = reset($translationList);

        try {
            if ($translation) {
                $translation->setValue($value);

                $storageService->persist($translation);
            } else {
                $storageService->createTranslation($locale, $entry, $value);
            }

            $result = array(
                'result'  => true,
                'message' => 'Translation updated successfully.'
            );
        } catch (\Exception $e) {
            $result = array(
                'result'  => false,
                'message' => $e->getMessage()
            );
        }

        return new Response(json_encode($result), 200, array(
            'Content-type' => 'application/json'
        ));
    }

    /**
     * Remove Locale action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeLocaleAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $request        = $this->getRequest();

        if ( ! $request->isXmlHttpRequest()) {
            return new RedirectResponse($this->generateUrl('sg_localeditor_index'));
        }

        try {
            $id     = $request->request->get('id');
            $status = $storageService->deleteLocale($id);

            $result = array(
                'result' => $status
            );
        } catch (\Exception $e) {
            $result = array(
                'result'  => false,
                'message' => $e->getMessage()
            );
        }

        return new Response(json_encode($result), 200, array(
            'Content-type' => 'application/json'
        ));
    }

    /**
     * Add Locale action
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addLocaleAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $request        = $this->getRequest();

        // Retrieve variables
        $language = $request->request->get('language');
        $country  = $request->request->get('country');

        try {
            // Check for country
            $country = ( ! empty($country)) ? $country : null;

            // Create new Locale
            $storageService->createLocale($language, $country);

            $result = array(
                'result'  => true,
                'message' => 'New locale added successfully. Reload list for completion.'
            );
        } catch (\Exception $e) {
            $result = array(
                'result'  => false,
                'message' => $e->getMessage()
            );
        }

        // Return reponse according to request type
        if ( ! $request->isXmlHttpRequest()) {
            return new RedirectResponse($this->generateUrl('sg_localeditor_index'));
        }

        return new Response(json_encode($result), 200, array(
            'Content-type' => 'application/json'
        ));
    }
}
