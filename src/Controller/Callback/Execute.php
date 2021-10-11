<?php

declare(strict_types=1);

namespace EasyTranslate\Connector\Controller\Callback;

use EasyTranslate\Connector\Model\Callback\LinkGenerator;
use EasyTranslate\Connector\Model\Callback\PriceApprovalRequestHandler;
use EasyTranslate\Connector\Model\Callback\TaskCompletedHandler;
use EasyTranslate\RestApiClient\Api\Callback\Event;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Header;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Webapi\Exception;
use Magento\Framework\Webapi\Response;
use Zend_Json_Exception;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Execute extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private const VALID_CALLBACK_EVENTS
        = [
            Event::PRICE_APPROVAL_NEEDED,
            Event::TASK_COMPLETED,
        ];

    /**
     * @var Header
     */
    private $header;

    /**
     * @var DecoderInterface
     */
    private $decoder;

    /**
     * @var PriceApprovalRequestHandler
     */
    private $priceApprovalRequestHandler;

    /**
     * @var TaskCompletedHandler
     */
    private $taskCompletedHandler;

    /**
     * @var array
     */
    private $params;

    public function __construct(
        Context $context,
        Header $header,
        DecoderInterface $decoder,
        PriceApprovalRequestHandler $priceApprovalRequestHandler,
        TaskCompletedHandler $taskCompletedHandler
    ) {
        parent::__construct($context);
        $this->header                      = $header;
        $this->decoder                     = $decoder;
        $this->priceApprovalRequestHandler = $priceApprovalRequestHandler;
        $this->taskCompletedHandler        = $taskCompletedHandler;
    }

    public function execute(): ResultInterface
    {
        $request = $this->getRequest();
        if (!$this->validateRequest($request)) {
            return $this->getBadRequestResponse('Could not validate request.');
        }

        try {
            switch ($this->params['event']) {
                case Event::PRICE_APPROVAL_NEEDED:
                    $this->priceApprovalRequestHandler->handle($this->params);
                    break;
                case Event::TASK_COMPLETED:
                    $this->taskCompletedHandler->handle($this->params);
                    break;
                default:
                    break;
            }
        } catch (LocalizedException $e) {
            return $this->getBadRequestResponse($e->getMessage());
        }

        return $this->getSuccessResponse();
    }

    private function validateRequest(RequestInterface $request): bool
    {
        if (!$request instanceof Http || !$request->isPost()) {
            return false;
        }

        if (stripos($this->header->getHttpUserAgent(), 'EasyTranslate') === false) {
            return false;
        }

        $json = $request->getContent();
        try {
            $params               = $this->decoder->decode($json);
            $secretParam          = LinkGenerator::SECRET_PARAM;
            $params[$secretParam] = $request->getParam($secretParam);
            $this->params         = $params;
        } catch (Zend_Json_Exception $e) {
            return false;
        }

        if (!$this->validateParams()) {
            return false;
        }

        return true;
    }

    private function validateParams(): bool
    {
        if (empty($this->params)) {
            return false;
        }

        if (!isset($this->params['event']) || !in_array($this->params['event'], self::VALID_CALLBACK_EVENTS, true)) {
            return false;
        }

        if (!isset($this->params['data'])) {
            return false;
        }

        if (!isset($this->params[LinkGenerator::SECRET_PARAM])) {
            return false;
        }

        return true;
    }

    private function getBadRequestResponse(string $message): ResultInterface
    {
        $params = [
            'success' => false,
            'message' => 'Bad Request: ' . $message
        ];

        return $this->getJsonResponse($params, Exception::HTTP_BAD_REQUEST);
    }

    protected function getSuccessResponse(): ResultInterface
    {
        $params = [
            'success' => true,
        ];

        return $this->getJsonResponse($params);
    }

    private function getJsonResponse(array $params, int $responseCode = Response::HTTP_OK): ResultInterface
    {
        /** @var Json $resultJson */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData($params);
        $result->setHttpResponseCode($responseCode);

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
