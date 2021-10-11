<?php

namespace EasyTranslate\Connector\Model\Adminhtml;

use EasyTranslate\Connector\Api\Data\ProjectInterface;
use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use Magento\Framework\App\RequestInterface;

class ProjectGetter
{
    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    public function __construct(
        RequestInterface $request,
        ProjectRepositoryInterface $projectRepository
    ) {
        $this->projectRepository = $projectRepository;
        $this->request           = $request;
    }

    public function getProject(): ?ProjectInterface
    {
        $projectId = $this->request->getParam(ProjectInterface::PROJECT_ID);
        if ($projectId) {
            return $this->projectRepository->get($projectId);
        }

        return null;
    }
}
