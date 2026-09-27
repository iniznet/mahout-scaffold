<?php

/**
 * The field groups this theme registers: one panel per (post type, group) pair.
 *
 * `StorageTarget` is required on every field and has no default, because the target
 * is decided when the field is declared, not when it is read: a field that is
 * filtered, sorted, aggregated or counted is a Table; one that is only read with its
 * object is Meta. A repeater that is queried is Table leaves; one that is only
 * displayed is Meta rows keyed by address.
 *
 * The field package derives the whole admin surface from this list - the metaboxes,
 * the save entry, the field route, the write-failure notice and the settings pages.
 * A theme that declares no panel and no option screen attaches none of them, which is
 * what an empty list here means: nothing is registered, nothing is enqueued, and the
 * absence is the declaration rather than a disabled feature.
 *
 * @return list<\Iniznet\Mahout\Fields\FieldPanel>
 */

declare(strict_types=1);

return [];
