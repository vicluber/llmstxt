# effective TYPO3 llms.txt Extension
This extension adds support for llms.txt files in TYPO3 v12.

## File Structure

```
llms_txt/
├── Classes/
│   ├── Middleware/
│   │   └── LlmsTxtMiddleware.php
│   └── Service/
│       └── LlmsTxtGenerator.php
├── Configuration/
│   ├── RequestMiddlewares.php
│   └── Services.yaml
├── Resources/
│   └── Private/
│       └── Language/
│           └── locallang.xlf
├── ext_emconf.php
├── ext_localconf.php
└── composer.json
```
