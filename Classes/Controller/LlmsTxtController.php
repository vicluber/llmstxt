<?php
declare(strict_types=1);

namespace Effective\LlmsTxt\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Annotation as Extbase;
use Psr\Http\Message\ResponseInterface;

class LlmsTxtController extends ActionController
{
    /**
     * Index action – default entry point
     *
     * @return string|ResponseInterface
     */
    public function indexAction()
    {
        // Example view assignment
        $this->view->assign('message', 'Hello from LlmsTxtController!');
        return $this->htmlResponse();
    }

    /**
     * Show action example
     *
     * @param int $id
     * @return string|ResponseInterface
     */
    public function showAction(int $id)
    {
        // Simulate fetching some record based on $id
        $this->view->assignMultiple([
            'id' => $id,
            'content' => 'Simulated content for ID ' . $id,
        ]);
        return $this->htmlResponse();
    }

    /**
     * Json action example
     *
     * @return ResponseInterface
     */
    public function jsonAction(): ResponseInterface
    {
        $data = [
            'success' => true,
            'timestamp' => time(),
            'message' => 'This is a JSON response.',
        ];

        return $this->jsonResponse($data);
    }
}
