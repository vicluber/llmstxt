# effective TYPO3 llms.txt

This is a TYPO3 extension implementing the newly developing [llms.txt standard](https://llmstxt.org/).
The initial skeleton, including most of the initial version of this README was produced by Claude Opus 4.

## Background: llms.txt
Taken from [standard/proposal page](https://llmstxt.org/):
> Large language models increasingly rely on website information, but face a critical limitation: context windows are too small to handle most websites in their entirety. Converting complex HTML pages with navigation, ads, and JavaScript into LLM-friendly plain text is both difficult and imprecise.
>
> While websites serve both human readers and LLMs, the latter benefit from more concise, expert-level information gathered in a single, accessible location. This is particularly important for use cases like development environments, where LLMs need quick access to programming documentation and APIs.

## Prompt for generation

The following prompt was used to generate the initial skeleton (which as usual wasn't
quite working yet but needed manual corrections):

> I would like to create a TYPO3 (v12) extension that will add support for llms.txt as defined in this proposal: https://llmstxt.org/
>
> In its initial iteration, it should simply provide introductory text taken from the home page of the website, followed by sections for each page in the page tree (using similar iteration logic as used for XML sitemaps).
>
> For actual llms.txt generation, it should use the llms-txt-php PHP library available here: https://github.com/raphaelstolt/llms-txt-php
>
> All our TYPO3 installs are composer based. They are also all multi-lingual, but we will only need the llms.txt in English for now.
>
> Please ask me if you need further information. If you have all information, please create a basic extension doing what I outlined above.

## Key Features

1. Middleware-based Implementation: Uses TYPO3's middleware system to intercept requests to /llms.txt and generate the content dynamically.
2. Uses llms-txt-php Library: Leverages the official PHP library for proper formatting and validation of the llms.txt file.
3. Page Tree Iteration: Similar to XML sitemap generation, it recursively walks through the page tree to create structured sections.
4. Content Extraction:
  - Uses the home page content for the description and details sections
  - Extracts page descriptions or content snippets for each page link
5. Multi-language Ready: While currently focused on English (language ID 0), the structure supports future multi-language expansion.
6. Composer-based: Fully compatible with composer-based TYPO3 installations.

## How it Works

1. When a request comes in for /llms.txt, the middleware intercepts it
2. The generator service reads the site configuration and home page content
3. It walks through the page tree, creating links for each accessible page
4. The llms-txt-php library formats everything according to the specification
5. The result is returned as a plain text response

### Rationale for Middleware vs Controller
(Claude)
> The implementation uses a middleware-based approach rather than a controller-based one. This is actually better for this use case because:
>
> 1. Direct Request Handling: The middleware can intercept /llms.txt requests before they reach the TYPO3 page routing system
> 2. No Page Required: We don't need to create a TYPO3 page or plugin to serve the llms.txt file
> 3. Better Performance: Middleware runs earlier in the request cycle, avoiding unnecessary processing
>
> The LlmsTxtMiddleware handles everything:
>
> - Detects requests for /llms.txt
> - Calls the LlmsTxtGenerator service
> - Returns the generated content with the appropriate headers
>
> If you prefer a controller-based approach (perhaps for more complex routing or to integrate with TYPO3's caching framework), I can create an alternative implementation using an Extbase controller and routing configuration. Would you like me to show that approach instead?

## Installation

1. Copy the files to typo3conf/ext/llms_txt/
2. Add the local path repository to your root composer.json
3. Run composer require vendor/llms-txt:@dev
4. Activate the extension

### Detailed Installation Instructions

Create the extension folder structure in your TYPO3 project:

```bash
cd typo3conf/ext/
mkdir -p llms_txt/{Classes/{Middleware,Service},Configuration,Resources/Private/Language}
```

Add the extension to your root composer.json:
```json
"repositories": [
    {
        "type": "path",
        "url": "typo3conf/ext/llms_txt"
    }
],
```

Install via composer:
```bash
composer require vendor/llms-txt:@dev
```

Activate the extension in the TYPO3 backend or via CLI:
```bash
vendor/bin/typo3 extension:activate llms_txt
```

## Usage
Once installed and activated, the extension will automatically provide a /llms.txt file at the root of each site configured in TYPO3. The file will:

1. Use the site's title and home page content for the header and description
2. Generate sections based on the page tree structure
3. Group pages that share a custom "section" into the same section of the llms.txt output
4. Include page titles and descriptions (or content excerpts) for each page
5. Only include visible, standard pages (doktype 1)

## Configuration Options (Future Enhancement)

Consider adding these features in future versions:

- TypoScript configuration for customizing output
- Backend module for preview and manual editing
- Support for additional languages
- Integration with SEO extensions for better descriptions
- Support for .md versions of pages as per the llms.txt spec

## Notes

- The extension currently only supports English content (language ID 0).
- Page descriptions are extracted from either the page's description field or the first 100 characters of content.
- Hidden pages, deleted pages, and special page types are excluded from the output.

## Future Enhancements to Consider:

- Proper URL generation using TYPO3's routing system
- Backend module for preview and configuration
- Support for page .md versions as mentioned in the spec
- TypoScript configuration options
- Caching for better performance
- Integration with existing SEO extensions

The extension provides a solid foundation that follows TYPO3 best practices and can be easily extended based on your specific needs.RetryClaude can make mistakes. Please double-check responses.Research Opus 4
