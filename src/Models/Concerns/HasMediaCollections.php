<?php

declare(strict_types=1);

namespace Tarkontech\SivarCart\Models\Concerns;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Conversions\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media as MediaModel;
use Tarkontech\SivarCart\Support\Media;

/**
 * The thumbnails of the media of the store.
 *
 * The sizes come from the "media.thumbnails" section of the configuration of
 * the package, so the merchant decides how big the thumbnails of the catalog
 * are.
 */
trait HasMediaCollections
{
    /**
     * Build the thumbnails of the media.
     *
     * The manipulations are written with their names on purpose: they travel
     * to the database as JSON and the media library reads them back from
     * there. The name of "fit" keeps its key when it makes the round trip,
     * and that is what lets the library turn the text back into the enum it
     * needs. With positional arguments the key is lost and the thumbnail
     * breaks the next time the media is read from the database.
     */
    public function registerMediaConversions(?MediaModel $media = null): void
    {
        foreach (Media::thumbnailSizes() as $name => $size) {
            $conversion = $this->addMediaConversion($name);

            $this->queueConversion($conversion);

            $conversion->setManipulations(new Manipulations([
                'fit' => [
                    'fit' => Fit::Crop,
                    'desiredWidth' => $size['width'],
                    'desiredHeight' => $size['height'],
                ],
            ]));
        }
    }

    /**
     * Whether the thumbnail is built in the background or right away.
     */
    protected function queueConversion(Conversion $conversion): void
    {
        if (Media::queuesConversions()) {
            $conversion->queued();

            return;
        }

        $conversion->nonQueued();
    }
}
