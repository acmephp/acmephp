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
    const DEFAULT_REASON = self::REASON_UNSPECIFIED;
    const REASON_UNSPECIFIED = 0;
    const REASON_KEY_COMPROMISE = 1;
    const REASON_AFFILLIATION_CHANGED = 3;
    const REASON_SUPERCEDED = 4;
    const REASON_CESSATION_OF_OPERATION = 5;

    /**
     * @var int|null
     */
    private $reasonType = null;

    /**
     * @param int $reasonType
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($reasonType)
    {
        $reasonType = (int) $reasonType;

        Assert::oneOf($reasonType, self::getReasons(), 'Revocation reason type "%s" is not supported by the ACME server (supported: %2$s)');

        $this->reasonType = $reasonType;
    }

    /**
     * @return int
     */
    public function getReasonType()
    {
        return $this->reasonType;
    }

    /**
     * @return static
     */
    public static function createDefaultReason()
    {
        return new static(self::DEFAULT_REASON);
    }

    /**
     * @return array
     */
    public static function getFormattedReasons()
    {
        $formatted = [];

        foreach (self::getReasonLabelMap() as $reason => $label) {
            $formatted[] = $reason.' - '.$label;
        }

        return $formatted;
    }

    /**
     * @return array
     */
    public static function getReasonLabelMap()
    {
        return [
            self::REASON_UNSPECIFIED => 'unspecified',
            self::REASON_KEY_COMPROMISE => 'key compromise',
            self::REASON_AFFILLIATION_CHANGED => 'affiliation changed',
            self::REASON_SUPERCEDED => 'superceded',
            self::REASON_CESSATION_OF_OPERATION => 'cessation of operation',
        ];
    }

    /**
     * @return array
     */
    public static function getReasons()
    {
        return [
            self::REASON_UNSPECIFIED,
            self::REASON_KEY_COMPROMISE,
            self::REASON_AFFILLIATION_CHANGED,
            self::REASON_SUPERCEDED,
            self::REASON_CESSATION_OF_OPERATION,
        ];
    }
}
