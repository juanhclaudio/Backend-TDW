<?php

namespace TDW\IPanel\Handler;

use Psr\Log\LoggerInterface;
use Slim\Exception\{ HttpMethodNotAllowedException, HttpNotFoundException };
use Slim\Interfaces\ErrorRendererInterface;
use TDW\IPanel\Utility\ExceptionDetail;
use Throwable;

/**
 * Html Error Renderer.
 */
readonly class HtmlErrorRenderer implements ErrorRendererInterface
{
    /**
     * The constructor.
     *
     * @param LoggerInterface $logger
     */
    public function __construct(
        private LoggerInterface $logger
    )
    { }

    /**
     * Invoke.
     *
     * @param Throwable $exception The exception
     * @param bool $displayErrorDetails Show error details
     *
     * @return string The result
     */
    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        $detailedErrorMessage = ExceptionDetail::getExceptionText($exception);

        // Add error log entry
        $this->logger->error($detailedErrorMessage);
        if ($displayErrorDetails) {
            return $detailedErrorMessage;
        }

        // Detect error type
        if ($exception instanceof HttpNotFoundException) {
            $errorMessage = '404 Not Found';
        } elseif ($exception instanceof HttpMethodNotAllowedException) {
            $errorMessage = '405 Method Not Allowed';
        } else {
            $errorMessage = '500 Internal Server Error';
        }

        return $errorMessage;
    }
}
