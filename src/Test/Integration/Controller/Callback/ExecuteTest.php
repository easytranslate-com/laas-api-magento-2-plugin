<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Test\Integration\Controller\Callback;

use EasyTranslate\Connector\Api\ProjectRepositoryInterface;
use EasyTranslate\Connector\Model\Callback\LinkGenerator;
use EasyTranslate\Connector\Model\Config\Source\Status;
use EasyTranslate\Connector\Model\Project;
use EasyTranslate\Connector\Model\Task;
use EasyTranslate\Connector\Model\TaskFactory;
use EasyTranslate\RestApiClient\Api\Callback\Event;
use EasyTranslate\RestApiClient\TaskInterface;
use Laminas\Http\Request;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Response;
use Magento\TestFramework\TestCase\AbstractController;

class ExecuteTest extends AbstractController
{
    /**
     * @var LinkGenerator
     */
    private $linkGenerator;

    /**
     * @var UrlInterface
     */
    private $url;

    /**
     * @var ProjectRepositoryInterface
     */
    private $projectRepository;

    /**
     * @var TaskFactory
     */
    private $taskFactory;

    /**
     * @var EncoderInterface
     */
    private $encoder;

    /**
     * @var int
     */
    private static $projectId;

    /**
     * @var int
     */
    private static $taskId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->linkGenerator     = $this->_objectManager->create(LinkGenerator::class);
        $this->url               = $this->_objectManager->create(UrlInterface::class);
        $this->projectRepository = $this->_objectManager->create(ProjectRepositoryInterface::class);
        $this->taskFactory       = $this->_objectManager->create(TaskFactory::class);
        $this->encoder           = $this->_objectManager->create(EncoderInterface::class);
    }

    public function testBlockNonPostRequests(): void
    {
        $project = $this->_objectManager->create(Project::class);
        $link    = $this->getRelativeCallbackUrl($project);
        $this->dispatch($link);
        /** @var HttpResponse $response */
        $response = $this->getResponse();
        // either blocked via HttpPostActionInterface marker or via custom check inside controller
        self::assertContains(
            $response->getHttpResponseCode(),
            [Exception::HTTP_BAD_REQUEST, Exception::HTTP_NOT_FOUND]
        );
    }

    public function testBlockWrongUserAgent(): void
    {
        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $request->setMethod(Request::METHOD_POST);
        $project = $this->_objectManager->create(Project::class);
        $link    = $this->getRelativeCallbackUrl($project);
        $this->dispatch($link);
        /** @var HttpResponse $response */
        $response = $this->getResponse();
        self::assertEquals(Exception::HTTP_BAD_REQUEST, $response->getHttpResponseCode());
    }

    /**
     * @dataProvider invalidCallbackContents
     */
    public function testInvalidParams(string $content): void
    {
        $request = $this->getValidBaseRequest();
        $request->setContent($content);
        $project = $this->_objectManager->create(Project::class);
        $link    = $this->getRelativeCallbackUrl($project);
        $this->dispatch($link);
        /** @var HttpResponse $response */
        $response = $this->getResponse();
        self::assertEquals(Exception::HTTP_BAD_REQUEST, $response->getHttpResponseCode());
    }

    /**
     * @magentoDataFixture loadProjectFixture
     */
    public function testInvalidSecret(): void
    {
        $request = $this->getValidBaseRequest();
        $content = [
            'event' => Event::TASK_COMPLETED,
            'data'  => [
                'id'         => 'id',
                'type'       => 'task',
                'attributes' => [
                    'project'         => [
                        'id' => 'project_id',
                    ],
                    'target_content'  => 'target_content',
                    'target_language' => 'target_language',
                ],
            ],
        ];
        $request->setContent($this->encoder->encode($content));
        $project = $this->projectRepository->get(self::$projectId);
        // generate callback URL with wrong secret
        $project->setData(LinkGenerator::SECRET_PARAM, 'wrong secret');
        $link = $this->getRelativeCallbackUrl($project);
        $this->dispatch($link);
        /** @var HttpResponse $response */
        $response = $this->getResponse();
        self::assertEquals(Exception::HTTP_BAD_REQUEST, $response->getHttpResponseCode());
    }

    /**
     * @magentoDataFixture loadProjectFixture
     */
    public function testPriceApprovalCallback(): void
    {
        $request  = $this->getValidBaseRequest();
        $price    = 999;
        $currency = 'EUR';
        $content  = [
            'event' => Event::PRICE_APPROVAL_NEEDED,
            'data'  => [
                'id'         => 'external_id',
                'type'       => 'project',
                'attributes' => [
                    'price' => [
                        // price is given in cents
                        'amount'   => $price * 100,
                        'currency' => $currency,
                    ],
                ],
            ],
        ];
        $request->setContent($this->encoder->encode($content));
        $project = $this->projectRepository->get(self::$projectId);
        $link    = $this->getRelativeCallbackUrl($project);
        $this->dispatch($link);
        /** @var HttpResponse $response */
        $response = $this->getResponse();
        self::assertEquals(Response::HTTP_OK, $response->getHttpResponseCode());
        $project = $this->projectRepository->get(self::$projectId);
        if (method_exists($this, 'assertEqualsWithDelta')) {
            self::assertEqualsWithDelta($price, $project->getPrice(), 0.0001);
        } else {
            // @phpstan-ignore-next-line
            self::assertEquals($price, $project->getPrice(), '', 0.0001);
        }
        self::assertEquals($currency, $project->getCurrency());
        self::assertEquals(Status::PRICE_APPROVAL_REQUEST, $project->getStatus());
    }

    /**
     * @magentoDataFixture loadProjectFixture
     */
    public function testTaskCompletedCallback(): void
    {
        $request       = $this->getValidBaseRequest();
        $targetContent = 'https://www.google.de';
        $content       = [
            'event' => Event::TASK_COMPLETED,
            'data'  => [
                'id'         => 'external_task_id',
                'type'       => 'task',
                'attributes' => [
                    'project'         => [
                        'id' => 'external_id',
                    ],
                    'target_content'  => $targetContent,
                    'target_language' => 'de',
                    'status'          => TaskInterface::STATUS_COMPLETED,
                ],
            ],
        ];
        $request->setContent($this->encoder->encode($content));
        $project = $this->projectRepository->get(self::$projectId);
        $link    = $this->getRelativeCallbackUrl($project);
        $this->dispatch($link);
        /** @var HttpResponse $response */
        $response = $this->getResponse();
        self::assertEquals(Response::HTTP_OK, $response->getHttpResponseCode());
        $task = $this->taskFactory->create()->load(self::$taskId);
        self::assertEquals($targetContent, $task->getData('content_link'));
        self::assertNull($task->getData('processed_at'));
    }

    public function invalidCallbackContents(): array
    {
        return [
            'missing data'        => [''],
            'invalid JSON'        => ['{]'],
            'missing event param' => ['{}'],
            'missing data param'  => ['{"event": "' . Event::TASK_COMPLETED . '"}'],
        ];
    }

    private function getRelativeCallbackUrl($project)
    {
        $link    = $this->linkGenerator->generateLink($project);
        $baseUrl = $this->url->getBaseUrl();

        return substr($link, strlen($baseUrl));
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public static function loadProjectFixture(): void
    {
        include __DIR__ . '/../../_files/project.php';
        /** @var Project $project */
        // @phpstan-ignore-next-line
        self::$projectId = (int)$project->getId();
        /** @var Task $task */
        // @phpstan-ignore-next-line
        self::$taskId = (int)$task->getId();
    }

    private function getValidBaseRequest(): HttpRequest
    {
        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $request->setMethod(Request::METHOD_POST);
        $serverParams = $request->getServer();
        $serverParams->set('HTTP_USER_AGENT', 'EasyTranslate');
        $request->setServer($serverParams);

        return $request;
    }
}
