<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Model\Content;

use EasyTranslate\Connector\Model\Project as ProjectModel;

class Generator
{
    /**
     * @var array
     */
    private $generators;

    public function __construct(array $generators)
    {
        $this->generators = $generators;
    }

    public function generateContent(ProjectModel $project, int $storeId): array
    {
        $content = [];
        foreach ($this->generators as $generator) {
            $content[] = $generator->getContent($project, $storeId);
        }

        return $content;
    }
}
