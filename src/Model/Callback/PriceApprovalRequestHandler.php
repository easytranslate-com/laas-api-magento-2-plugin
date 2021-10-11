<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Callback;

use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Config\Source\Status;
use EasyTranslate\Connector\Model\ProjectFactory;
use EasyTranslate\RestApiClient\Api\Callback\DataConverter\PriceApprovalConverter;
use Magento\Framework\Exception\LocalizedException;

class PriceApprovalRequestHandler
{
    /**
     * @var ProjectFactory
     */
    private $projectFactory;

    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    public function __construct(ProjectFactory $projectFactory, ProjectRepositoryInterface $projectRepository)
    {
        $this->projectFactory    = $projectFactory;
        $this->projectRepository = $projectRepository;
    }

    /**
     * @throws LocalizedException
     */
    public function handle(array $data): void
    {
        $secret    = $data[LinkGenerator::SECRET_PARAM];
        $converter = new PriceApprovalConverter();
        $response  = $converter->convert($data);
        $project   = $this->projectFactory->create()->load($response->getProjectId(), 'external_id');
        if ($project->getData('secret') !== $secret) {
            throw new LocalizedException(__('Secret does not match.'));
        }
        $project->setData('price', $response->getPrice());
        $project->setData('currency', $response->getCurrency());
        $project->setData('status', Status::PRICE_APPROVAL_REQUEST);
        $this->projectRepository->save($project);
    }
}
