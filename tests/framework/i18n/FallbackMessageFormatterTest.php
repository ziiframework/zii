<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\i18n;

use yiiunit\TestCase;
use yii\i18n\MessageFormatter;

/**
 * @author Carsten Brandt <mail@cebe.cc>
 *
 * @since 2.0
 *
 * @group i18n
 */
class FallbackMessageFormatterTest extends TestCase
{
    public const N = 'n';
    public const N_VALUE = 42;
    public const F = 'f';
    public const F_VALUE = 2e+8;
    public const F_VALUE_FORMATTED = '200,000,000';
    public const D = 'd';
    public const D_VALUE = 200000000.101;
    public const D_VALUE_FORMATTED = '200,000,000.101';
    public const D_VALUE_FORMATTED_INTEGER = '200,000,000';
    public const SUBJECT = 'сабж';
    public const SUBJECT_VALUE = 'Answer to the Ultimate Question of Life, the Universe, and Everything';

    public function patterns()
    {
        return [
            [
                '{' . self::SUBJECT . '} is {' . self::N . '}', // pattern
                self::SUBJECT_VALUE . ' is ' . self::N_VALUE, // expected
                [ // params
                    self::N => self::N_VALUE,
                    self::SUBJECT => self::SUBJECT_VALUE,
                ],
            ],

            [
                '{' . self::SUBJECT . '} is {' . self::N . ', number}', // pattern
                self::SUBJECT_VALUE . ' is ' . self::N_VALUE, // expected
                [ // params
                    self::N => self::N_VALUE,
                    self::SUBJECT => self::SUBJECT_VALUE,
                ],
            ],

            [
                '{' . self::SUBJECT . '} is {' . self::N . ', number, integer}', // pattern
                self::SUBJECT_VALUE . ' is ' . self::N_VALUE, // expected
                [ // params
                    self::N => self::N_VALUE,
                    self::SUBJECT => self::SUBJECT_VALUE,
                ],
            ],

            [
                'Here is a big number: {' . self::F . ', number}', // pattern
                'Here is a big number: ' . self::F_VALUE_FORMATTED, // expected
                [ // params
                    self::F => self::F_VALUE,
                ],
            ],

            [
                'Here is a big number: {' . self::F . ', number, integer}', // pattern
                'Here is a big number: ' . self::F_VALUE_FORMATTED, // expected
                [ // params
                    self::F => self::F_VALUE,
                ],
            ],

            [
                'Here is a big number: {' . self::D . ', number}', // pattern
                'Here is a big number: ' . self::D_VALUE_FORMATTED, // expected
                [ // params
                    self::D => self::D_VALUE,
                ],
            ],

            [
                'Here is a big number: {' . self::D . ', number, integer}', // pattern
                'Here is a big number: ' . self::D_VALUE_FORMATTED_INTEGER, // expected
                [ // params
                    self::D => self::D_VALUE,
                ],
            ],

            // This one was provided by Aura.Intl. Thanks!
            [<<<'_MSG_'
{gender_of_host, select,
  female {{num_guests, plural, offset:1
      =0 {{host} does not give a party.}
      =1 {{host} invites {guest} to her party.}
      =2 {{host} invites {guest} and one other person to her party.}
     other {{host} invites {guest} and # other people to her party.}}}
  male {{num_guests, plural, offset:1
      =0 {{host} does not give a party.}
      =1 {{host} invites {guest} to his party.}
      =2 {{host} invites {guest} and one other person to his party.}
     other {{host} invites {guest} and # other people to his party.}}}
  other {{num_guests, plural, offset:1
      =0 {{host} does not give a party.}
      =1 {{host} invites {guest} to their party.}
      =2 {{host} invites {guest} and one other person to their party.}
      other {{host} invites {guest} and # other people to their party.}}}}
_MSG_
                ,
                'ralph invites beep and 3 other people to his party.',
                [
                    'gender_of_host' => 'male',
                    'num_guests' => 4,
                    'host' => 'ralph',
                    'guest' => 'beep',
                ],
            ],

            [
                '{name} is {gender} and {gender, select, female{she} male{he} other{it}} loves Yii!',
                'Alexander is male and he loves Yii!',
                [
                    'name' => 'Alexander',
                    'gender' => 'male',
                ],
            ],

            // verify pattern in select does not get replaced
            [
                '{name} is {gender} and {gender, select, female{she} male{he} other{it}} loves Yii!',
                'Alexander is male and he loves Yii!',
                [
                    'name' => 'Alexander',
                    'gender' => 'male',
                    // following should not be replaced
                    'he' => 'wtf',
                    'she' => 'wtf',
                    'it' => 'wtf',
                ],
            ],

            // verify pattern in select message gets replaced
            [
                '{name} is {gender} and {gender, select, female{she} male{{he}} other{it}} loves Yii!',
                'Alexander is male and wtf loves Yii!',
                [
                    'name' => 'Alexander',
                    'gender' => 'male',
                    'he' => 'wtf',
                    'she' => 'wtf',
                ],
            ],

            // formatting a message that contains params but they are not provided.
            [
                'Incorrect password (length must be from {min, number} to {max, number} symbols).',
                'Incorrect password (length must be from {min, number} to {max, number} symbols).',
                ['attribute' => 'password'],
            ],

            // some parser specific verifications
            [
                '{gender} and {gender, select, female{she} male{{he}} other{it}} loves {nr} is {gender}!',
                'male and wtf loves 42 is male!',
                [
                    'nr' => 42,
                    'gender' => 'male',
                    'he' => 'wtf',
                    'she' => 'wtf',
                ],
            ],
        ];
    }

    /**
     * @dataProvider patterns
     *
     * @param string $pattern
     * @param string $expected
     * @param array $args
     */
    public function testNamedArguments($pattern, $expected, $args): void
    {
        $formatter = new FallbackMessageFormatter();
        $result = $formatter->fallbackFormat($pattern, $args, 'en-US');
        $this->assertEquals($expected, $result, $formatter->getErrorMessage());
    }

    public function testInsufficientArguments(): void
    {
        $expected = '{' . self::SUBJECT . '} is ' . self::N_VALUE;

        $formatter = new FallbackMessageFormatter();
        $result = $formatter->fallbackFormat('{' . self::SUBJECT . '} is {' . self::N . '}', [
            self::N => self::N_VALUE,
        ], 'en-US');

        $this->assertEquals($expected, $result);
    }

    public function testNoParams(): void
    {
        $pattern = '{' . self::SUBJECT . '} is ' . self::N;

        $formatter = new FallbackMessageFormatter();
        $result = $formatter->fallbackFormat($pattern, [], 'en-US');
        $this->assertEquals($pattern, $result, $formatter->getErrorMessage());
    }

    public function testGridViewMessage(): void
    {
        $pattern = 'Showing <b>{begin, number}-{end, number}</b> of <b>{totalCount, number}</b> {totalCount, plural, one{item} other{items}}.';
        $formatter = new FallbackMessageFormatter();
        $result = $formatter->fallbackFormat($pattern, ['begin' => 1, 'end' => 5, 'totalCount' => 10], 'en-US');
        $this->assertEquals('Showing <b>1-5</b> of <b>10</b> items.', $result);
    }

    public function testUnsupportedPercentException(): void
    {
        $pattern = 'Number {' . self::N . ', number, percent}';
        $formatter = new FallbackMessageFormatter();
        $this->expectException('yii\base\NotSupportedException');
        $formatter->fallbackFormat($pattern, [self::N => self::N_VALUE], 'en-US');
    }

    public function testUnsupportedCurrencyException(): void
    {
        $pattern = 'Number {' . self::N . ', number, currency}';
        $formatter = new FallbackMessageFormatter();
        $this->expectException('yii\base\NotSupportedException');
        $formatter->fallbackFormat($pattern, [self::N => self::N_VALUE], 'en-US');
    }
}

class FallbackMessageFormatter extends MessageFormatter
{
    public function fallbackFormat($pattern, $args, $locale)
    {
        return parent::fallbackFormat($pattern, $args, $locale);
    }
}
