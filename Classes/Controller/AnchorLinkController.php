<?php

namespace Paessler\AnchorLink\Controller;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Paessler\AnchorLink\AnchorLinkResolverInterface;

class AnchorLinkController extends ActionController
{
    /**
     * @var AnchorLinkResolverInterface
     * @Flow\Inject
     */
    protected AnchorLinkResolverInterface $resolver;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = array(
        'json' => JsonView::class
    );

    /**
     * @param Node $node
     * @param string $link
     * @param string $searchTerm
     * @return void
     */
    public function resolveAnchorsAction(Node $node, string $link, string $searchTerm): void
    {
        $options = $this->resolver->resolve($node, $link, $searchTerm);
        $this->view->assign('value', $options);
    }
}
