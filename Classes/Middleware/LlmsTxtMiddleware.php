<?php

declare(strict_types=1);

namespace Effective\LlmsTxt\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Effective\LlmsTxt\Service\LlmsTxtGenerator;
use TYPO3\CMS\Core\Site\SiteFinder;

class LlmsTxtMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LlmsTxtGenerator $llmsTxtGenerator
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if ($path !== '/llms.txt' && !str_ends_with($path, '/llms.txt')) {
            return $handler->handle($request);
        }

        $host = $request->getUri()->getHost();
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);

        // Getting all sites and loping to get the current one get from the request. ($host)
        $site = null;
        foreach ($siteFinder->getAllSites() as $candidate) {
            $normalizedBase = rtrim(preg_replace('#^https?://#', '', (string)$candidate->getBase()), '/');
            if ($normalizedBase === $host) {
                $site = $candidate;
            }
        }

        if (!$site instanceof Site) {
            return $handler->handle($request);
        }

        try {
            $content = $this->llmsTxtGenerator->generate($site);

            $body = new Stream('php://temp', 'rw');
            $body->write($content);

            return (new Response())
                ->withHeader('Content-Type', 'text/plain; charset=utf-8')
                ->withBody($body)
                ->withStatus(200);
        } catch (\Throwable $e) {
            // Optionally log $e->getMessage()
            return (new Response())->withStatus(500);
        }
    }
}
