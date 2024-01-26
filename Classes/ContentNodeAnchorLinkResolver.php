<?php

namespace Paessler\AnchorLink;

use Neos\Eel\Exception as EelException;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Neos\Service\LinkingService;
use Neos\Eel\EelEvaluatorInterface;
use Neos\Eel\Utility;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Neos\Domain\Service\NodeSearchService;

/**
 * Create link anchors based on all matching nodes within the target link node
 *
 * @see Paessler:AnchorLink:* Settings
 */
class ContentNodeAnchorLinkResolver implements AnchorLinkResolverInterface
{
    /**
     * @Flow\Inject
     * @var NodeSearchService
     */
    protected $nodeSearchService;

    /**
     * @Flow\Inject
     * @var EelEvaluatorInterface
     */
    protected $eelEvaluator;

    /**
     * @Flow\InjectConfiguration("eelContext")
     * @var array
     */
    protected $contextConfiguration;

    /**
     * @Flow\InjectConfiguration(path="contentNodeType")
     * @var string
     */
    protected $contentNodeType;

    /**
     * @Flow\InjectConfiguration(path="anchor")
     * @var string
     */
    protected $anchor;

    /**
     * @Flow\InjectConfiguration(path="label")
     * @var string
     */
    protected $label;

    /**
     * @Flow\InjectConfiguration(path="group")
     * @var string
     */
    protected $group;

    /**
     * @Flow\InjectConfiguration(path="icon")
     * @var string
     */
    protected $icon;

    /**
     * @inheritDoc
     * @throws EelException
     */
    public function resolve(NodeInterface $node, string $link, string $searchTerm): array
    {
        $context = $node->getContext();
        $targetNode = null;

        if ((preg_match(LinkingService::PATTERN_SUPPORTED_URIS, $link, $matches) === 1) && $matches[1] === 'node') {
            $targetNode = $context->getNodeByIdentifier($matches[2]) ?? $node;
        }
        if ($targetNode === null) {
            return [];
        }

        if ($searchTerm !== '') {
            $nodes = $this->nodeSearchService->findByProperties($searchTerm, [$this->contentNodeType], $context, $targetNode->getPrimaryChildNode());
        } else {
            $q = new FlowQuery([$targetNode]);
            /** @noinspection PhpUndefinedMethodInspection */
            $nodes = $q->children('[instanceof Neos.Neos:ContentCollection]')->find('[instanceof ' . $this->contentNodeType . ']')->get();
        }

        if ($this->eelEvaluator instanceof DependencyProxy) {
            $this->eelEvaluator->_activateDependency();
        }

        return array_values(array_map(function (NodeInterface $node) {
            $anchor = (string)Utility::evaluateEelExpression($this->anchor, $this->eelEvaluator, ['node' => $node], $this->contextConfiguration);
            $label = (string)Utility::evaluateEelExpression($this->label, $this->eelEvaluator, ['node' => $node], $this->contextConfiguration);
            $group = (string)Utility::evaluateEelExpression($this->group, $this->eelEvaluator, ['node' => $node], $this->contextConfiguration);
            $icon = (string)Utility::evaluateEelExpression($this->icon, $this->eelEvaluator, ['node' => $node], $this->contextConfiguration);
            return [
                'icon' => $icon,
                'group' => $group,
                'value' => $anchor,
                'label' => $label,
            ];
        }, $nodes));
    }
}
