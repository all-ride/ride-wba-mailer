<?php

namespace ride\web\base\form;

use ride\library\form\component\AbstractComponent;
use ride\library\form\FormBuilder;

/**
 * Form component for a item of the mail attachments
 */
class AttachmentComponent extends AbstractComponent {

    /**
     * Prepares the form by adding row definitions
     * @param ride\library\form\FormBuilder $builder
     * @param array $options
     * @return null
     */
    public function prepareForm(FormBuilder $builder, array $options) {
        $translator = $options['translator'];

        $builder->addRow('file', 'file', array(
            'label' => $translator->translate('label.file'),
            'validators' => array(
                'required' => array(),
            ),
        ));
    }

}
