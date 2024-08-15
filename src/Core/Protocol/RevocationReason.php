<?php

/*
 * This file is part of the Acme PHP project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AcmePhp\Core\Protocol;

use Webmozart\Assert\Assert;

/**
 * @url https://github.com/certbot/certbot/blob/c326c021082dede7c3b2bd411cec3aec6dff0ac5/certbot/constants.py#L124
 */
class RevocationReason
{
    public const DEFAULT_REASON = self::REASON_UNSPECIFIED;
    public const REASON_UNSPECIFIED = 0;
    public const REASON_KEY_COMPROMISE = 1;
    public const REASON_AFFILLIATION_CHANGED = 3;
    public const REASON_SUPERCEDED = 4;
    public const REASON_CESSATION_OF_OPERATION = 5;

    /** @var int|null */
    private $reasonType;

    public function __construct(int $reasonType)
    {
        Assert::oneOf($reasonType, self::getReasons(), 'Revocation reason type "%s" is not supported by the ACME server (supported: %2$s)');

        $this->reasonType = $reasonType;
    }

    public function getReasonType(): int
    {
        return $this->reasonType;
    }

    public static function createDefaultReason(): self
    {
        return new static(self::DEFAULT_REASON);
    }

    public static function getFormattedReasons(): array
    {
        $formatted = array();
        foreach (self::getReasonLabelMap() as $reason => $label) {
            $formatted[] = $reason . ' - ' . $label;
        }

        return $formatted;
    }

    private static function getReasonLabelMap(): array
    {
        return array(
            self::REASON_UNSPECIFIED => 'unspecified',
            self::REASON_KEY_COMPROMISE => 'key compromise',
            self::REASON_AFFILLIATION_CHANGED => 'affiliation changed',
            self::REASON_SUPERCEDED => 'superceded',
            self::REASON_CESSATION_OF_OPERATION => 'cessation of operation',
        );
    }

    public static function getReasons(): array
    {
        return array(
            self::REASON_UNSPECIFIED,
            self::REASON_KEY_COMPROMISE,
            self::REASON_AFFILLIATION_CHANGED,
            self::REASON_SUPERCEDED,
            self::REASON_CESSATION_OF_OPERATION,
        );
    }
}
