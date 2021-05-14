<?php

namespace Service;

use App\System\Collection;

/**
 * Class XmlGenerator
 * @package Service
 *
 * @property \XMLWriter $writer
 * @property XmlSettings $settings
 */

class XmlGenerator
{
    /**
     * @var \XMLWriter $writer
     */
    protected $writer;
    /**
     * @var XmlSettings $settings
     */
    protected $settings;

    public function __construct(XmlSettings $settings)
    {
        $this->writer = new \XMLWriter();

        $this->settings = $settings;

        if($this->settings->memory()) {
            $this->writer->openMemory();
        } else {
            if (null !== $this->settings->getTpl()) {
                $this->writer->openURI($this->settings->getTpl());
            } else {
                header("Content-Type: text/html/force-download");
                header(sprintf("Content-Disposition: attachment; filename=%s", $this->settings->getFileName()));

                $this->writer->openURI($this->settings::OUTPUT);
            }
        }

        if (null !== $this->settings->getIndentString()) {
            $this->writer->setIndentString($this->settings->getIndentString());
            $this->writer->setIndent(true);
        }
    }

    /**
     * @param Collection $collection
     * @return string|null
     */
    public function generate(Collection $collection): ?string
    {
        $this->startXmlDocument();

        $this->createAuthenticationElement($collection->get('authentication', []));

        $this->addParameters($collection->get('parameters', []));

        $this->endXmlDocument();

        if ($this->settings->memory()) {
            return $this->writer->outputMemory();
        }

        return null;
    }

    /**
     *
     * @param array $authentication
     */
    protected function createAuthenticationElement(array $authentication)
    {
        $this->writer->startElement('Authentication');

        $this->writer->startElement('Login');
        $this->writer->text($authentication['login']);
        $this->writer->endElement();

        $this->writer->startElement('TransactionID');
        $this->writer->text($authentication['transaction_id']);
        $this->writer->endElement();

        $this->writer->startElement('MethodName');
        $this->writer->text($authentication['method']);
        $this->writer->endElement();

        $this->writer->startElement('Hash');
        $this->writer->text($authentication['hash']);
        $this->writer->endElement();

        $this->writer->fullEndElement();
    }

    /**
     * Добавить элемент параметров
     * @param array $parameters
     */
    protected function addParameters(array $parameters)
    {
        if (count($parameters) === 0) return;

        if (isset($parameters['products']) && $parameters['products']) {

            $this->writer->startElement('Parameters');
            $this->writer->startElement('Products');

            foreach ($parameters['products'] as $product) {
                $this->addProduct($product);
            }

            $this->writer->fullEndElement();

        } elseif (isset($parameters['categories']) && $parameters['categories']) {

            $this->writer->startElement('Parameters');
            if (isset($parameters['limit'])) {
                $this->addLimitElement(is_array($parameters['limit']) ? $parameters['limit'] : []);
            }
            $this->writer->startElement('Categories');

            foreach ($parameters['categories'] as $category) {
                $this->addCategory($category);
            }

            $this->writer->fullEndElement();

        } else {
            $this->writer->startElement('Parameters');
            $this->writer->endElement();
        }
    }

    protected function addLimitElement(array $limit)
    {
        $this->writer->startElement('Limit');
        $this->writer->writeAttribute('offset', isset($limit['offset']) ? $limit['offset'] : 0);
        $this->writer->writeAttribute('row_count', isset($limit['count']) ? $limit['count'] : 1000);
        $this->writer->endElement();
    }

    /**
     * Добавить элемент продукта
     * @param array $product
     */
    protected function addProduct(array $product)
    {
        if (!isset($product['id'])) return;

        $this->writer->startElement('Product');
        $this->writer->text($product['id']);
        $this->writer->endElement();
    }

    /**
     * Добавить элемент категории
     * @param array $category
     */
    protected function addCategory(array $category)
    {
        if (!isset($category['id'])) return;

        $this->writer->startElement('Category');
        $this->writer->text($category['id']);
        $this->writer->endElement();
    }

    /**
     * Начало документа
     */
    protected function startXmlDocument()
    {
        $this->writer->startElement('Request');
    }

    /**
     * Закрываем документ
     */
    protected function endXmlDocument()
    {
        $this->writer->endDocument();
    }
}