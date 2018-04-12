<?php

namespace ride\web\base\controller;

use ride\library\i18n\I18n;
use ride\library\mail\exception\TemplateNotFoundMailException;
use ride\library\mail\exception\TypeNotFoundMailException;
use ride\library\mail\template\MailTemplateProvider;
use ride\library\mail\type\MailTypeProvider;
use ride\library\reflection\ReflectionHelper;
use ride\library\validation\exception\ValidationException;

use ride\web\base\form\AttachmentComponent;
use ride\web\base\table\MailTemplateTable;

class MailTemplateController extends AbstractController {

    public function __construct(I18n $i18n, MailTemplateProvider $mailTemplateProvider) {
        $this->i18n = $i18n;
        $this->mailTemplateProvider = $mailTemplateProvider;
    }

    public function indexAction(ReflectionHelper $reflectionHelper, $locale = null) {
        if ($locale === null) {
            $locale = $this->getLocale();

            $this->response->setRedirect($this->getUrl('system.mail.templates.locale', array('locale' => $locale)));

            return;
        } else {
            $this->locale = $locale;
        }

        $translator = $this->getTranslator();
        $referer = $this->request->getUrl();
        $baseUrl = $this->getUrl('system.mail.templates.locale', array('locale' => $locale));

        $url = $this->getUrl('system.mail.templates.edit', array('locale' => $locale, 'id' => '%id%'));

        $table = new MailTemplateTable($reflectionHelper, $url, $this->mailTemplateProvider, $locale);
        $table->setPaginationOptions(array(10, 25, 50, 100));
        $table->setPaginationUrl($baseUrl . '?page=%page%');
        $table->addAction(
            $translator->translate('button.delete'),
            array($this, 'deleteTemplate'),
            $translator->translate('label.table.confirm.delete')
        );

        $form = $this->processTable($table, $baseUrl, 25);
        if ($this->response->willRedirect() || $this->response->getView()) {
            return;
        }

        $query = $this->request->getQueryParameter('query');
        $actions = array(
            (string) $this->getUrl('system.mail.templates.add', array('locale' => $locale), array('referer' => $referer)) => $translator->translate('button.mail.template.add'),
            (string) $this->getUrl('system.mail.types') => $translator->translate('button.mail.types.manage'),
        );

        $this->setTemplateView('mail/templates', array(
            'form' => $form->getView(),
            'table' => $table,
            'query' => $table->getSearchQuery(),
            'locale' => $locale,
            'locales' => $this->i18n->getLocales(),
            'actions' => $actions,
        ));
    }

    public function deleteTemplate($ids) {
        foreach ($ids as $id) {
            $mailTemplate = $this->mailTemplateProvider->getMailTemplate($id, $this->locale);
            $this->mailTemplateProvider->deleteMailTemplate($mailTemplate);
        }
    }

    public function addAction(MailTypeProvider $mailTypeProvider, $locale) {
        $translator = $this->getTranslator();

        $mailTypeOptions = array();

        $mailTypes = $mailTypeProvider->getMailTypes();
        foreach ($mailTypes as $mailType) {
            $mailTypeOptions[$mailType->getName()] = $translator->translate('mail.type.' . $mailType->getName());
        }

        $form = $this->createFormBuilder();
        $form->addRow('type', 'option', array(
            'label' => $translator->translate('label.mail.type'),
            'description' => $translator->translate('label.mail.type.description'),
            'options' => $mailTypeOptions,
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form = $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $url = $this->getUrl('system.mail.templates.add2', array('locale' => $locale), array('type' => $data['type']));

                $this->response->setRedirect($url);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $referer = $this->request->getQueryParameter('referer');

        $this->setTemplateView('mail/templates.add', array(
            'form' => $form->getView(),
            'referer' => $referer,
            'locale' => $locale,
            'locales' => $this->i18n->getLocales(),
        ));
    }

    /**
     * Action to manage the details of a mail template
     * @param \ride\library\mail\type\MailTypeProvider $mailTypeProvider
     * @param string $locale
     * @param string $id
     * @return null
     */
    public function formAction(MailTypeProvider $mailTypeProvider, $locale, $id = null) {
        if ($id) {
            try {
                $mailTemplate = $this->mailTemplateProvider->getMailTemplate($id, $locale);
            } catch (TemplateNotFoundMailException $exception) {
                return $this->response->setNotFound();
            }

            $mailType = $mailTemplate->getMailType();
        } else {
            $type = $this->request->getQueryParameter('type');

            try {
                $mailType = $mailTypeProvider->getMailType($type);
            } catch (TypeNotFoundMailException $exception) {
                $url = $this->getUrl('system.mail.templates.add', array('locale' => $locale));

                $this->response->setRedirect($url);

                return;
            }

            $mailTemplate = $this->mailTemplateProvider->createMailTemplate($locale);
            $mailTemplate->setMailType($mailType);
        }

        $translator = $this->getTranslator();

        $availableRecipients = $mailType->getRecipientVariables();
        foreach ($availableRecipients as $recipient => $translationKey) {
            $availableRecipients[$recipient] = $translator->translate($translationKey);
        }

        $attachments = $mailTemplate->getAttachments();
        if ($attachments) {
            foreach ($attachments as $index => $attachment) {
                $attachments[$index] = array('file' => $attachment);
            }
        } else {
            $attachments = array();
        }

        $cc = $mailTemplate->getCc();
        if ($cc) {
            $cc = implode(',', $cc);
        } else {
            $cc = '';
        }

        $bcc = $mailTemplate->getBcc();
        if ($bcc) {
            $bcc = implode(',', $bcc);
        } else {
            $bcc = '';
        }

        $data = array(
            'type' => $translator->translate('mail.type.' . $mailType->getName()),
            'name' => $mailTemplate->getName(),
            'subject' => $mailTemplate->getSubject(),
            'body' => $mailTemplate->getBody(),
            'senderName' => $mailTemplate->getSenderName(),
            'senderEmail' => $mailTemplate->getSenderEmail(),
            'attachments' => $attachments,
            'recipients' => $mailTemplate->getRecipients(),
            'cc' => $cc,
            'bcc' => $bcc,
        );

        $form = $this->createFormBuilder($data);
        $form->addRow('name', 'string', array(
            'label' => $translator->translate('label.mail.name'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('type', 'label', array(
            'label' => $translator->translate('label.mail.type'),
        ));
        $form->addRow('senderEmail', 'email', array(
            'label' => $translator->translate('label.sender.email'),
            'description' => $translator->translate('label.sender.email.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('senderName', 'string', array(
            'label' => $translator->translate('label.sender.name'),
            'description' => $translator->translate('label.sender.name.description'),
        ));
        $form->addRow('subject', 'string', array(
            'label' => $translator->translate('label.subject'),
            'description' => $translator->translate('label.subject.description'),
            'attributes' => array(
                'class' => 'js-content-variable-drop',
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('body', 'wysiwyg', array(
            'label' => $translator->translate('label.body'),
            'description' => $translator->translate('label.body.description'),
            'attributes' => array(
                'class' => 'js-content-variable-drop',
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('attachments', 'collection', array(
            'label' => $translator->translate('label.attachments'),
            'type' => 'component',
            'options' => array(
                'component' => new AttachmentComponent(),
            ),
        ));
        $form->addRow('recipients', 'option', array(
            'label' => $translator->translate('label.recipients'),
            'description' => $translator->translate('label.recipients.description'),
            'multiple' => true,
            'options' => $availableRecipients,
            'validators' => array(
                'required' => array(),
                'size' => array(
                    'minimum' => 1,
                ),
            ),
        ));
        $form->addRow('cc', 'text', array(
            'label' => $translator->translate('label.cc'),
            'description' => $translator->translate('label.cc.description'),
            'attributes' => array(
                'class' => 'js-recipient-variable-drop',
            ),
            'filters' => array(
                'trim' => array(
                    'trim.lines' => true,
                    'trim.empty' => true,
                ),
            ),
        ));
        $form->addRow('bcc', 'text', array(
            'label' => $translator->translate('label.bcc'),
            'description' => $translator->translate('label.bcc.description'),
            'attributes' => array(
                'class' => 'js-recipient-variable-drop',
            ),
            'filters' => array(
                'trim' => array(
                    'trim.lines' => true,
                    'trim.empty' => true,
                ),
            ),
        ));
        $form = $form->build();

        $referer = $this->request->getQueryParameter('referer');
        if (!$referer) {
            $referer = $this->getUrl('system.mail.templates.locale', array('locale' => $locale));
        }

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $data = $form->getData();

                $mailTemplate->setLocale($locale);
                $mailTemplate->setName($data['name']);
                $mailTemplate->setSenderName($data['senderName']);
                $mailTemplate->setSenderEmail($data['senderEmail']);
                $mailTemplate->setSubject($data['subject']);
                $mailTemplate->setBody($data['body']);
                $mailTemplate->setRecipients($data['recipients']);

                $attachments = array();
                if ($data['attachments']) {
                    foreach ($data['attachments'] as $attachment) {
                        $attachments[] = $attachment['file'];
                    }
                }
                $mailTemplate->setAttachments($attachments);

                $cc = array();
                if ($data['cc']) {
                    $cc = explode(',', $data['cc']);
                }
                $mailTemplate->setCc($cc);

                $bcc = array();
                if ($data['bcc']) {
                    $bcc = explode(',', $data['bcc']);
                }
                $mailTemplate->setBcc($bcc);

                $this->mailTemplateProvider->saveMailTemplate($mailTemplate);

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $view = $this->setTemplateView('mail/templates.form', array(
            'form' => $form->getView(),
            'locale' => $locale,
            'locales' => $this->i18n->getLocales(),
            'mailTemplate' => $mailTemplate,
            'referer' => $referer,
            'contentVariables' => $mailType->getContentVariables(),
            'recipientVariables' => $mailType->getRecipientVariables(),
        ));

        $form->processView($view);
    }

}
