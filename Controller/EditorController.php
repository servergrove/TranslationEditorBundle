<?php

namespace ServerGrove\Bundle\TranslationEditorBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller,
    Symfony\Component\HttpFoundation\Response;

class EditorController extends Controller
{
    public function getCollection()
    {
        return $this->container->get('server_grove_translation_editor.storage_manager')->getCollection();
    }

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
        $request = $this->getRequest();

        $locales = $request->request->get('locale');
        $key = $request->request->get('key');

        foreach($locales as $locale => $val ) {
            $values = $this->getCollection()->find(array('locale' => $locale));
            $values = iterator_to_array($values);
            if (!count($values)) {
                continue;
            }
            $found = false;
            foreach ($values as $data) {
                if (isset($data['entries'][$key])) {
                    $res = array(
                        'result' => false,
                        'msg' => 'The key already exists. Please update it instead.',
                    );
                    return new Response(json_encode($res));
                }
            }

            $data = array_pop($values);

            $data['entries'][$key] = $val;

            if (!$request->request->get('check-only')) {
                $this->updateData($data);
            }
        }

        if ($request->isXmlHttpRequest()) {
            $res = array(
                'result' => true,
            );

            return new Response(json_encode($res));
        }

        return new \Symfony\Component\HttpFoundation\RedirectResponse($this->generateUrl('sg_localeditor_list'));
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

    protected function updateData($data)
    {
        $this->getCollection()->update(
            array('_id' => $data['_id'])
            , $data, array('upsert' => true));
    }
}
