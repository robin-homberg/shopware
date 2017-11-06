<?php declare(strict_types=1);

namespace Shopware\Framework\Writer\Resource;

use Shopware\Api\Write\Field\IntField;
use Shopware\Api\Write\Field\StringField;
use Shopware\Api\Write\Flag\Required;
use Shopware\Api\Write\WriteResource;
use Shopware\Context\Struct\TranslationContext;
use Shopware\Framework\Event\CampaignsBannerWrittenEvent;

class CampaignsBannerWriteResource extends WriteResource
{
    protected const PARENTID_FIELD = 'parentID';
    protected const IMAGE_FIELD = 'image';
    protected const LINK_FIELD = 'link';
    protected const LINKTARGET_FIELD = 'linkTarget';
    protected const DESCRIPTION_FIELD = 'description';

    public function __construct()
    {
        parent::__construct('s_campaigns_banner');

        $this->fields[self::PARENTID_FIELD] = (new IntField('parentID'))->setFlags(new Required());
        $this->fields[self::IMAGE_FIELD] = (new StringField('image'))->setFlags(new Required());
        $this->fields[self::LINK_FIELD] = (new StringField('link'))->setFlags(new Required());
        $this->fields[self::LINKTARGET_FIELD] = (new StringField('linkTarget'))->setFlags(new Required());
        $this->fields[self::DESCRIPTION_FIELD] = (new StringField('description'))->setFlags(new Required());
    }

    public function getWriteOrder(): array
    {
        return [
            self::class,
        ];
    }

    public static function createWrittenEvent(array $updates, TranslationContext $context, array $rawData = [], array $errors = []): CampaignsBannerWrittenEvent
    {
        $uuids = [];
        if ($updates[self::class]) {
            $uuids = array_column($updates[self::class], 'uuid');
        }

        $event = new CampaignsBannerWrittenEvent($uuids, $context, $rawData, $errors);

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
