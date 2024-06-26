<?php

namespace Paessler\AnchorLink;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;

interface AnchorLinkResolverInterface
{
    /**
     * Return an array of options for the anchor link selectbox:
     *
     * [
     *      [
     *           'icon'  => 'icon-foo', // optional
     *           'group' => 'first', // optional
     *           'value' => 'bar',
     *           'label' => 'Bar',
     *      ],
     *      [
     *           'icon'  => 'icon-foo', // optional
     *           'group' => 'second',
     *           'value' => 'baz',
     *           'label' => 'Baz',
     *      ]
     * ];
     *
     * @param Node $node Currently focused node
     * @param string $link Current link target (for example "node://<some-identifier>" or "https://www.external.url")
     * @param string $searchTerm Search term (term that has been entered in the "Choose link anchor" search field, defaults to an empty string)
     * @return array
     */
    public function resolve(Node $node, string $link, string $searchTerm): array;
}
