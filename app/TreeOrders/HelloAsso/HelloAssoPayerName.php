<?php

namespace App\TreeOrders\HelloAsso;

/**
 * First and last name of the payer sent to HelloAsso, which refuses the whole
 * checkout when a name breaks its rules. Names are never altered: when one
 * of them would be refused, none is sent and HelloAsso asks the payer.
 */
class HelloAssoPayerName
{
    /**
     * Latin letters, accented or not (the sandbox accepts ë, ï, ô…),
     * apostrophe, hyphen and space.
     */
    private const ALLOWED = "/^[\\p{Latin}' -]+$/u";

    private const VOWEL = '/[aeiouyàâäéèêëîïôöùûüÿæœ]/iu';

    private const REPEATED = '/(.)\1\1/iu';

    private const RESERVED = ['firstname', 'lastname', 'unknown', 'anonyme', 'test'];

    private const MAX_LENGTH = 255;

    /**
     * @return array{firstName?: string, lastName?: string}
     */
    public static function fields(string $firstName, string $lastName): array
    {
        $firstName = trim($firstName);
        $lastName = trim($lastName);

        if (! self::isAccepted($firstName) || ! self::isAccepted($lastName) || mb_strtolower($firstName) === mb_strtolower($lastName)) {
            return [];
        }

        return ['firstName' => $firstName, 'lastName' => $lastName];
    }

    private static function isAccepted(string $name): bool
    {
        return mb_strlen($name) > 1
            && mb_strlen($name) <= self::MAX_LENGTH
            && preg_match(self::ALLOWED, $name) === 1
            && preg_match(self::VOWEL, $name) === 1
            && preg_match(self::REPEATED, $name) === 0
            && ! in_array(mb_strtolower($name), self::RESERVED, true);
    }
}
