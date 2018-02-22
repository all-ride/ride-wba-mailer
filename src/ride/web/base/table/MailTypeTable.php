<?php

namespace ride\web\base\table;

use ride\library\html\table\decorator\DataDecorator;
use ride\library\html\table\FormTable;
use ride\library\reflection\ReflectionHelper;

/**
 * Table to display an overview of mail types
 */
class MailTypeTable extends FormTable {

    /**
     * Constructs a new mail type table
     * @param array $mailTypes
     */
    public function __construct(ReflectionHelper $reflectionHelper, $action, array $mailTypes) {
        ksort($mailTypes);

        parent::__construct($mailTypes);

        $decorator = new DataDecorator($reflectionHelper, $action);
        $decorator->mapProperty('title', 'name');
        $decorator->mapProperty('id', 'name');

        $this->addDecorator($decorator);
    }

}
