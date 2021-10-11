<?php

declare(strict_types=1);

use EasyTranslate\Connector\Api\Data\ProjectInterfaceFactory;
use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Config\Source\Status;
use EasyTranslate\Connector\Model\Project;
use EasyTranslate\Connector\Model\Task;
use EasyTranslate\Connector\Model\TaskFactory;
use EasyTranslate\RestApiClient\Workflow;
use Magento\TestFramework\Helper\Bootstrap;

$projectRepository = Bootstrap::getObjectManager()->create(ProjectRepositoryInterface::class);
$projectFactory    = Bootstrap::getObjectManager()->create(ProjectInterfaceFactory::class);
$taskFactory    = Bootstrap::getObjectManager()->create(TaskFactory::class);

/** @var Project $project */
$project = $projectFactory->create();
$project->setExternalId('external_id');
$project->setName('Fixture Project');
$project->setTeam('team');
$project->setSourceStoreId(1);
$project->setTargetStoreIds([1]);
$project->setStatus(Status::OPEN);
$project->setWorkflow(Workflow::TYPE_TRANSLATION);
$project->setAutomaticImport(true);

$project = $projectRepository->save($project);

/** @var Task $task */
$task = $taskFactory->create();
$task->setData('project_id', $project->getProjectId());
$task->setData('external_id', 'external_task_id');
$task->setData('store_id', 1);
$task->save();
