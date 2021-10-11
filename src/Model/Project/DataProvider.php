<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Project;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Model\ResourceModel\Project\Collection as ProjectsCollection;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Zend_Currency_Exception;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var ProjectsCollection
     */
    protected $collection;

    /**
     * @var array
     */
    private $loadedData;

    /**
     * @var CurrencyInterface
     */
    private $currency;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ProjectsCollection $collection,
        DataPersistorInterface $dataPersistor,
        CurrencyInterface $currency,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection    = $collection;
        $this->dataPersistor = $dataPersistor;
        $this->currency      = $currency;
    }

    public function getData(): ?array
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $projects = $this->collection->getItems();
        foreach ($projects as $project) {
            $projectId                    = $project->getData(ProjectInterface::PROJECT_ID);
            $this->loadedData[$projectId] = $project->getData();
            $this->addDisabledAttribute($project);
            $this->formatPrice($project);
            $this->hidePriceForNewProject($project);
        }
        $data = $this->dataPersistor->get('easytranslate_project');
        if ($data === null) {
            $this->loadedData[null]['price-visibility']['visible'] = false;
        }
        if (!empty($data)) {
            $project = $this->collection->getNewEmptyItem();
            $project->setData($data);
            $this->loadedData[$project->getId()] = $project->getData();
            $this->addDisabledAttribute($project);
            $this->formatPrice($project);
            $this->hidePriceForNewProject($project);
            $this->dataPersistor->clear('easytranslate_project');
        }

        return $this->loadedData;
    }

    private function addDisabledAttribute(DataObject $project): void
    {
        $projectId                                = $project->getData(ProjectInterface::PROJECT_ID);
        $this->loadedData[$projectId]['disabled'] = false;
        if (!$project->canEditDetails()) {
            $this->loadedData[$projectId]['disabled'] = true;
        }
    }

    private function formatPrice(DataObject $project): void
    {
        $projectId = $project->getData(ProjectInterface::PROJECT_ID);
        if (empty($this->loadedData[$projectId]['price'])) {
            $this->loadedData[$projectId]['price'] = 'tbd';

            return;
        }
        if (empty($this->loadedData[$projectId]['currency'])) {
            return;
        }
        $currency = $this->currency->getCurrency($this->loadedData[$projectId]['currency']);
        try {
            $convertedCurrency                     = $currency->toCurrency($this->loadedData[$projectId]['price']);
            $this->loadedData[$projectId]['price'] = $convertedCurrency;
        } catch (Zend_Currency_Exception $e) {
            $this->loadedData[$projectId]['price'] .= ' ' . $this->loadedData[$projectId]['currency'];
        }
    }

    private function hidePriceForNewProject(DataObject $project): void
    {
        $projectId                                                   = $project->getData(ProjectInterface::PROJECT_ID);
        $this->loadedData[$projectId]['price-visibility']['visible'] = false;
        if ($projectId) {
            $this->loadedData[$projectId]['price-visibility']['visible'] = true;
        }
    }
}
