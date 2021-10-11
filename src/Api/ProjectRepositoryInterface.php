<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Api;

use EasyTranslate\Connector\Api\Data\ProjectInterface;

interface ProjectRepositoryInterface
{
    public function save(ProjectInterface $project): ProjectInterface;

    public function get(int $projectId): ProjectInterface;

    public function delete(ProjectInterface $project): bool;

    public function deleteById(int $projectId): bool;
}
