<?php

namespace ride\web\base\controller;

use ride\library\mail\type\MailTypeProvider;
use ride\library\reflection\ReflectionHelper;

use ride\web\base\table\MailTypeTable;

class MailTypeController extends AbstractController {

    public function indexAction(ReflectionHelper $reflectionHelper, MailTypeProvider $mailTypeProvider) {
        $mailTypes = $mailTypeProvider->getMailTypes();

        $table = new MailTypeTable($reflectionHelper, $this->getUrl('system.mail.types.detail'), $mailTypes);

        $baseUrl = $this->getUrl('system.mail.types');

        $form = $this->processTable($table, $baseUrl, 99);
        if ($this->response->willRedirect() || $this->response->getView()) {
            return;
        }

        $translator = $this->getTranslator();

        $actions = array(
            (string) $this->getUrl('system.mail.templates') => $translator->translate('button.mail.templates.manage'),
        );

        $this->setTemplateView('mail/types', array(
            'form' => $form->getView(),
            'table' => $table,
            'actions' => $actions,
        ));
    }

    public function detailAction(MailTypeProvider $mailTypeProvider, $id) {
        $mailType = $mailTypeProvider->getMailType($id);

        $this->setTemplateView('mail/types.detail', array(
            'mailType' => $mailType,
        ));
    }

}
