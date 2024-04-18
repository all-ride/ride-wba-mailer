<?php

namespace ride\web\base\table;

use ride\library\decorator\TableOptionDecorator;
use ride\library\form\Form;
use ride\library\html\table\decorator\DataDecorator;
use ride\library\html\table\decorator\ValueDecorator;
use ride\library\html\table\FormTable;
use ride\library\html\Element;
use ride\library\mail\template\MailTemplateProvider;
use ride\library\reflection\ReflectionHelper;

/**
 * Table to display an overview of mail types
 */
class MailTemplateTable extends FormTable {


    protected $model;
    protected $locale;
    protected $reflectionHelper;
    protected $mailTemplateProvider;
    protected $options;
    protected $values;
    protected $countRows;
    protected $pages;
    protected $page;

    /**
     * Constructs a new mail type table
     * @param array $mailTypes
     */
    public function __construct(ReflectionHelper $reflectionHelper, $action, MailTemplateProvider $mailTemplateProvider, $locale) {
        $this->reflectionHelper = $reflectionHelper;
        $this->mailTemplateProvider = $mailTemplateProvider;
        $this->options = array(
            'locale' => $locale,
        );

        $this->model = $mailTemplateProvider->model;
        $this->locale = $locale;

        parent::__construct(array());

        $decorator = new DataDecorator($reflectionHelper, $action);
        $decorator->mapProperty('title', 'name');
        $decorator->mapProperty('id', 'id');

        $this->addDecorator($decorator);
    }

    /**
     * Gets the HTML of this table
     * @param string $part The part to get
     * @return string
     */
    public function getHtml($part = Element::FULL) {
        if (!$this->isPopulated && $this->actions) {
            $decorator = new ValueDecorator(null, new TableOptionDecorator($this->reflectionHelper, 'id'));
            $decorator->setCellClass('option');

            $this->addDecorator($decorator, null, true);
        }

        return parent::getHtml($part);
    }

    /**
     * Processes and applies the actions, search, order and pagination of this
     * table
     * @param \ride\library\form\Form $form
     * @return null
     */
    public function processForm(Form $form) {
        if (!parent::processForm($form)) {
            return false;
        }

        if (!$this->pageRows || ($this->pageRows && $this->countRows)) {
            $this->values = $this->mailTemplateProvider->getMailTemplates($this->options);
        }

        return true;
    }

    /**
     * Applies the pagination to the model query of this table
     * @return null
     */
    protected function applyPagination() {
        if (!$this->pageRows) {
            return;
        }

        //$mailTemplates = $this->mailTemplateProvider->getMailTemplates($this->options);


        $this->countRows = $this->countTotalRows();
        $this->pages = ceil($this->countRows / $this->pageRows);

        if ($this->page > $this->pages) {
            $this->page = 1;
        }


        $this->options['page'] = $this->page;
        $this->options['limit'] = $this->pageRows;
        $this->options['offset'] = ($this->page - 1) * $this->pageRows;

    }

    /**
     * Adds the condition for the search query to the model query of this table
     * @return null
     */
    protected function applySearch() {
        if (empty($this->searchQuery)) {
            return;
        }

        $this->options['query'] = $this->searchQuery;
    }

    /**
     * Performs a count on the model query of this table
     * @return integer Number of rows
     */
    protected function countTotalRows() {
        return $this->model->createQuery($this->locale)->count();
    }

}
