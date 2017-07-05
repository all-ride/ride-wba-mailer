<?php

namespace ride\web\base\controller;

use ride\library\i18n\I18n;

use ride\service\MailService;

class MailTemplateSelectController extends AbstractController {

    public function indexAction(I18n $i18n, MailService $mailService, $locale = null) {
        if ($locale === null) {
            $locale = $this->getLocale();

            $this->response->setRedirect($this->getUrl('system.mail.templates.select.locale', array('locale' => $locale)));

            return;
        }

        $mailTypes = $mailService->getMailTypeProvider()->getGlobalMailTypes();

        $data = array();
        foreach ($mailTypes as $mailTypeId => $mailType) {
            unset($mailTypes[$mailTypeId]);
            $mailTypeId = str_replace(array('.', '-'), '', $mailTypeId);

            $data[$mailTypeId] = $mailService->getMailTemplatesForType($mailType, $locale);
            $mailTypes[$mailTypeId] = $mailType;
        }

        $translator = $this->getTranslator();

        $mailTemplateProvider = $mailService->getMailTemplateProvider();

        $form = $this->createFormBuilder($data);
        foreach ($mailTypes as $mailTypeId => $mailType) {
            $mailTemplates = $mailTemplateProvider->getMailTemplatesForType($mailType);

            $form->addRow($mailTypeId, 'object', array(
                'label' => $translator->translate('mail.type.' . $mailType->getName()),
                'options' => $mailTemplates,
                'property' => 'name',
                'multiple' => true,
                'widget' => 'option',
            ));
        }
        $form = $form->build();

        if ($form->isSubmitted()) {
            $data = $form->getData();

            foreach ($mailTypes as $mailTypeId => $mailType) {
                $mailService->setMailTemplatesForType($mailType, $data[$mailTypeId]);
            }

            $url = $this->getUrl('system.mail.templates.select.locale', array('locale' => $locale));

            $this->addSuccess('success.mail.templates.saved');

            $this->response->setRedirect($url);

            return;
        }

        $this->setTemplateView('mail/preferences', array(
            'form' => $form->getView(),
            'locale' => $locale,
            'locales' => $i18n->getLocales(),
        ));
    }

}
