<?php declare(strict_types=1);

namespace Shopware\Unit\Writer\Resource;

use Shopware\Api\Write\Field\SubresourceField;
use Shopware\Api\Write\Field\TranslatedField;
use Shopware\Api\Write\Field\UuidField;
use Shopware\Api\Write\Flag\Required;
use Shopware\Api\Write\WriteResource;
use Shopware\Context\Struct\TranslationContext;
use Shopware\Shop\Writer\Resource\ShopWriteResource;
use Shopware\Unit\Event\UnitWrittenEvent;

class UnitWriteResource extends WriteResource
{
    protected const UUID_FIELD = 'uuid';
    protected const SHORT_CODE_FIELD = 'shortCode';
    protected const NAME_FIELD = 'name';

    public function __construct()
    {
        parent::__construct('unit');

        $this->primaryKeyFields[self::UUID_FIELD] = (new UuidField('uuid'))->setFlags(new Required());
        $this->fields[self::SHORT_CODE_FIELD] = new TranslatedField('shortCode', ShopWriteResource::class, 'uuid');
        $this->fields[self::NAME_FIELD] = new TranslatedField('name', ShopWriteResource::class, 'uuid');
        $this->fields['translations'] = (new SubresourceField(UnitTranslationWriteResource::class, 'languageUuid'))->setFlags(new Required());
    }

    public function getWriteOrder(): array
    {
        return [
            self::class,
            UnitTranslationWriteResource::class,
        ];
    }

    public static function createWrittenEvent(array $updates, TranslationContext $context, array $rawData = [], array $errors = []): UnitWrittenEvent
    {
        $uuids = [];
        if ($updates[self::class]) {
            $uuids = array_column($updates[self::class], 'uuid');
        }

        $event = new UnitWrittenEvent($uuids, $context, $rawData, $errors);

        unset($updates[self::class]);

        /**
         * @var WriteResource
         * @var string[]      $identifiers
         */
        foreach ($updates as $class => $identifiers) {
            if (!array_key_exists($class, $updates) || count($updates[$class]) === 0) {
                continue;
            }

            $event->addEvent($class::createWrittenEvent($updates, $context));
        }

        return $event;
    }
}
