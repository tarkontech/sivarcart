<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Models\Concerns;

use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * The media of the models of the store.
 *
 * It brings the media library of Spatie and the thumbnails of the package
 * together. The two of them want to write the method that registers the
 * thumbnails, so the package wins that one.
 */
trait HasStoreMedia
{
    use HasMediaCollections;
    use InteractsWithMedia {
        HasMediaCollections::registerMediaConversions insteadof InteractsWithMedia;
    }
}
