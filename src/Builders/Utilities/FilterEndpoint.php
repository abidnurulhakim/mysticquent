<?php

/*
 * This file is part of the ONGR package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mysticquent\Builders\Utilities;

use ONGR\ElasticsearchDSL\SearchEndpoint\QueryEndpoint;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Mysticquent\Builders\MapBuilder;

/**
 * Search filter dsl endpoint.
 */
class FilterEndpoint extends QueryEndpoint
{
    /**
     * Endpoint name
     */
    const NAME = 'filter';

    /**
     * {@inheritdoc}
     */
    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = [])
    {
        if (!$this->getBool()) {
            return null;
        }

        $this->addReference('filter_query', $this->getBool());
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
