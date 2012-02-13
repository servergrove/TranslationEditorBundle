<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpFoundation\Response;

class EditorController extends Controller
{
    public function listAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $defaultLocale  = $this->container->getParameter('locale', 'en');

        $localeList    = $storageService->findLocaleList();
        $defaultLocale = array_filter(
            $localeList,
            function ($locale) use ($defaultLocale) {
                return $locale->equalsTo($defaultLocale);
            }
        );
        $defaultLocale = reset($defaultLocale);

        $entryList = $storageService->findEntryList();

        return $this->render(
            'ServerGroveTranslationEditorBundle:Editor:list.html.twig',
            array(
                'localeList'    => $localeList,
                'entryList'     => $entryList,
                'defaultLocale' => $defaultLocale,
            )
        );
    }

    public function removeAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $request        = $this->getRequest();

        if ($request->isXmlHttpRequest()) {
            $id     = $request->request->get('id');
            $result = array(
                'result' => $storageService->deleteEntry($id)
            );

            return new Response(json_encode($result), 200, array(
                'Content-type' => 'application/json'
            ));
        }
    }

    public function addAction()
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
                'result' => false,
                'msg' => 'The alias already exists. Please update it instead.',
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
        if ($request->isXmlHttpRequest()) {
            $result = array(
                'result' => true,
            );

            return new Response(json_encode($result));
        }

        return new RedirectResponse($this->generateUrl('sg_localeditor_list'));
    }

    public function updateAction()
    {
        $storageService = $this->container->get('server_grove_translation_editor.storage');
        $request        = $this->getRequest();

        if ($request->isXmlHttpRequest()) {
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

                $result = array('result' => true);
            } catch (\Exception $e) {
                $result = array('result' => false);
            }

            return new Response(json_encode($result), 200, array(
                'Content-type' => 'application/json'
            ));
        }
    }
}
