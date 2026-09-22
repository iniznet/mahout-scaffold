<?php

/**
 * The one declarative content model. Every entry is a mahout-content
 * declaration: a PostType, a Taxonomy or a RestRoute, never a raw
 * register_post_type() call. Schema declares data; the provider registers it
 * on init through the content package's Contracts surface.
 *
 * @return list<\Iniznet\Mahout\Content\PostType|\Iniznet\Mahout\Content\Taxonomy|\Iniznet\Mahout\Content\RestRoute>
 */

declare(strict_types=1);

return [];
