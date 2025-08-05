<?php

declare(strict_types=1);

namespace Effective\LlmsTxt\Service;

use Stolt\LlmsTxt\LlmsTxt;
use Stolt\LlmsTxt\Section;
use Stolt\LlmsTxt\Section\Link;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

class LlmsTxtGenerator
{
    private PageRepository $pageRepository;
    private ConnectionPool $connectionPool;
    private array $sectionTitles = [];

    public function __construct(PageRepository $pageRepository, ConnectionPool $connectionPool)
    {
        $this->pageRepository = $pageRepository;
        $this->connectionPool = $connectionPool;
    }

    public function generate(Site $site): string
    {
        $rootPageId = $site->getRootPageId();
        $languageId = 0;

            foreach ($site->getAllLanguages() as $language) {
                if (strtolower($language->getLocale()->getLanguageCode()) === 'en') {
                    $languageId = $language->getLanguageId();
                    break;
                }
            }

        $homePage = $this->pageRepository->getPage($rootPageId, true);

        if (!$homePage) {
            throw new \RuntimeException('Home page not found');
        }

        if ((int)$homePage['doktype'] === 4 && !empty($homePage['shortcut'])) {
            $rootPageId = (int)$homePage['shortcut'];
        }

        $llmsTxt = new LlmsTxt();

        $title = $site->getConfiguration()['websiteTitle'] ?? $homePage['title'] ?? 'Website';
        $llmsTxt->title($title);

        $homePageContent = null;
        $description = trim($homePage['tx_llmstxt_llms_description'] ?? '');
        if ($description === '') {
            $description = trim($homePage['description'] ?? '');
        }
        if ($description === '') {
            $homePageContent = $this->getPageContent($rootPageId, $languageId);
            $description = $this->extractDescription($homePageContent);
        }
        if ($description !== '') {
            $llmsTxt->description($description);
        }

        if ($homePageContent === null) {
            $homePageContent = $this->getPageContent($rootPageId, $languageId);
        }
        $details = $this->extractDetails($homePageContent);
        if ($details !== '') {
            $llmsTxt->details($details);
        }

        $pageTree = $this->getPageTree($rootPageId, $languageId);
        $sections = $this->createSectionsFromPageTree($pageTree, $site, $languageId);

        foreach ($sections as $section) {
            $llmsTxt->addSection($section);
        }

        return $llmsTxt->toString();
    }


    private function getPageContent(int $pageId, int $languageId): string
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');

        $content = $queryBuilder
            ->select('bodytext', 'header')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId)),
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0))
            )
            ->orderBy('sorting')
            ->executeQuery()
            ->fetchAllAssociative();

        $text = '';
        foreach ($content as $element) {
            if (!empty($element['header'])) {
                $text .= strip_tags($element['header']) . "\n\n";
            }
            if (!empty($element['bodytext'])) {
                $text .= strip_tags($element['bodytext']) . "\n\n";
            }
        }

        return trim($text);
    }

    private function extractDescription(string $content): string
    {
        $paragraphs = array_filter(explode("\n\n", $content));
        if (!empty($paragraphs)) {
            $firstParagraph = reset($paragraphs);
            return mb_substr($firstParagraph, 0, 200);
        }

        return mb_substr($content, 0, 200);
    }

    private function extractDetails(string $content): string
    {
        $paragraphs = array_filter(explode("\n\n", $content));
        if (count($paragraphs) > 1) {
            return $paragraphs[1];
        }

        return '';
    }


    private function getPageTree(int $rootPageId, int $languageId, int $depth = 99): array
    {
        $tree = [];
        $this->buildPageTree($tree, $rootPageId, $languageId, $depth);
        return $tree;
    }

    private function buildPageTree(array &$tree, int $parentId, int $languageId, int $depth, int $currentDepth = 0): void
    {
        if ($currentDepth >= $depth) {
            return;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');

        $pages = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($parentId)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0)),
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0))
            )
            ->orderBy('sorting')
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($pages as $page) {
            if (empty(trim($page['title'])) && empty(trim($page['nav_title']))) {
                continue;
            }

            $node = [
                'page' => $page,
                'children' => []
            ];

            $this->buildPageTree($node['children'], $page['uid'], $languageId, $depth, $currentDepth + 1);
            $tree[] = $node;
        }
    }

    private function getLocalizedPage(int $pageId, int $languageId): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');

        return $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageId)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId)),
                $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0))
            )
            ->executeQuery()
            ->fetchAssociative() ?: null;
    }

    private function createSectionsFromPageTree(array $pageTree, Site $site, int $languageId): array
    {
        $sections = [];

        foreach ($pageTree as $node) {
            $this->addSectionsRecursively($sections, $node, $site, $languageId);
        }
        return array_values($sections);
    }

    private function addSectionsRecursively(array &$sections, array $node, Site $site, int $languageId): void
    {
        $page = $node['page'];
        if ((int)($page['sys_language_uid'] ?? 0) !== $languageId) {
            $localized = $this->getLocalizedPage((int)$page['uid'], $languageId);
            if ($localized) {
                $page = $localized;
            } else {
                foreach ($node['children'] as $child) {
                    $this->addSectionsRecursively($sections, $child, $site, $languageId);
                }
                return;
            }
        }
        if ((int)($page['tx_llmstxt_index'] ?? 1) === 0) {
            foreach ($node['children'] as $child) {
                $this->addSectionsRecursively($sections, $child, $site, $languageId);
            }
            return;
        }
        if (in_array((int)$page['doktype'], [254, 199, 4], true)) {
            foreach ($node['children'] as $child) {
                $this->addSectionsRecursively($sections, $child, $site, $languageId);
            }
            return;
        }

        $sectionId = (int)($page['tx_llmstxt_section'] ?? 0);
        if ($sectionId > 0) {
            if (!isset($sections[$sectionId])) {
                $sectionTitle = $this->getSectionTitle($sectionId);
                $section = new Section();
                $section->name($sectionTitle);
                $sections[$sectionId] = $section;
            }
            $section = $sections[$sectionId];
        } else {
            $sectionKey = 'page-' . $page['uid'];
            if (!isset($sections[$sectionKey])) {
                $section = new Section();
                $section->name($page['nav_title'] ?: $page['title'] ?: '[untitled]');
                $sections[$sectionKey] = $section;
            }
            $section = $sections[$sectionKey];
        }

        $link = new Link();
        $url = $this->createPageUrl((int)$page['uid'], $site, $languageId);
        $link->url($url);
        $link->urlTitle($page['nav_title'] ?: $page['title'] ?: '[untitled]');

        $description = trim($page['tx_llmstxt_llms_description'] ?? '');
        if ($description === '') {
            $description = trim($page['description'] ?? '');
        }
        if ($description === '') {
            $content = $this->getPageContent((int)$page['uid'], $languageId);
            $description = $this->extractDescription($content);
        }
        if ($description !== '') {
            $link->urlDetails($description);
        }

        $section->addLink($link);

        foreach ($node['children'] as $child) {
            $this->addSectionsRecursively($sections, $child, $site, $languageId);
        }
    }

    private function getSectionTitle(int $sectionId): string
    {
        if (!isset($this->sectionTitles[$sectionId])) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tx_llmstxt_section');
            $row = $queryBuilder
                ->select('title')
                ->from('tx_llmstxt_section')
                ->where(
                    $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($sectionId)),
                    $queryBuilder->expr()->eq('hidden', $queryBuilder->createNamedParameter(0)),
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0))
                )
                ->executeQuery()
                ->fetchAssociative();
            $this->sectionTitles[$sectionId] = $row['title'] ?? ('Section ' . $sectionId);
        }

        return $this->sectionTitles[$sectionId];
    }



    private function addPagesToSection(Section $section, array $pageTree, Site $site, int $languageId, string $prefix = ''): void
    {
        foreach ($pageTree as $node) {
            $page = $node['page'];

            if ((int)($page['tx_llmstxt_index'] ?? 1) === 0) {
                if (!empty($node['children'])) {
                    $this->addPagesToSection($section, $node['children'], $site, $languageId, $prefix);
                }
                continue;
            }

            if (in_array((int)$page['doktype'], [254, 199, 4], true)) {
                if (!empty($node['children'])) {
                    $this->addPagesToSection($section, $node['children'], $site, $languageId, $prefix);
                }
                continue;
            }

            $url = $this->createPageUrl($page['uid'], $site, $languageId);

            $link = new Link();
            $linkTitle = $prefix . ($page['nav_title'] ?: $page['title'] ?: '[untitled]');
            $link->urlTitle($linkTitle)->url($url);

            $section->addLink($link);

            if (!empty($node['children'])) {
                $this->addPagesToSection($section, $node['children'], $site, $languageId, $prefix . '  ');
            }
        }
    }
    private function createPageUrl(int $pageId, Site $site, int $languageId): string
    {
        $language = $site->getLanguageById($languageId);
        if (!$language instanceof SiteLanguage) {
            throw new \RuntimeException(sprintf('Language with ID %d not found in site configuration.', $languageId));
        }

        $router = $site->getRouter();

        $uri = $router->generateUri(
            $pageId,
            [
                '_language' => $language,
            ]
        );

        return (string)$uri;
    }
}
