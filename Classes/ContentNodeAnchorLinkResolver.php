<?php

namespace Paessler\AnchorLink;

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\Eel\Exception as EelException;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\Flow\ObjectManagement\DependencyInjection\DependencyProxy;
use Neos\Neos\Service\LinkingService;
use Neos\Eel\EelEvaluatorInterface;
use Neos\Eel\Utility;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;

/**
 * Create link anchors based on all matching nodes within the target link node
 *
 * @see Paessler:AnchorLink:* Settings
 */
class ContentNodeAnchorLinkResolver implements AnchorLinkResolverInterface
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

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
    public function resolve(Node $node, string $link, string $searchTerm): array
    {
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
        $targetNode = null;

        if ((preg_match(LinkingService::PATTERN_SUPPORTED_URIS, $link, $matches) === 1) && $matches[1] === 'node') {
            $targetNode = $subgraph->findNodeById(NodeAggregateId::fromString($matches[2]));
            //$targetNode = $context->getNodeByIdentifier($matches[2]) ?? $node;
        }
        if ($targetNode === null) {
            return [];
        }
        $targetSubgraph = $this->contentRepositoryRegistry->subgraphForNode($targetNode);
        $nodes  = [];
        $search = ($searchTerm !== '' ? $searchTerm : null);
        $nodeFilter = FindDescendantNodesFilter::create(nodeTypes: $this->contentNodeType, searchTerm: $search);

        foreach ($targetSubgraph->findDescendantNodes($targetNode->nodeAggregateId, $nodeFilter) as $descendant) {
            $nodes[] = $descendant;
        }

        if ($this->eelEvaluator instanceof DependencyProxy) {
            $this->eelEvaluator->_activateDependency();
        }

        return array_values(array_map(function (Node $node) {
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
