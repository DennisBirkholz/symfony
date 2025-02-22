<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Intl\Tests\NumberFormatter;

use PHPUnit\Framework\Error\Warning;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Intl\Globals\IntlGlobals;
use Symfony\Component\Intl\NumberFormatter\NumberFormatter;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * Note that there are some values written like -2147483647 - 1. This is the lower 32bit int max and is a known
 * behavior of PHP.
 */
abstract class AbstractNumberFormatterTest extends TestCase
{
    /**
     * @dataProvider formatCurrencyWithDecimalStyleProvider
     */
    public function testFormatCurrencyWithDecimalStyle($value, $currency, $expected)
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $this->assertEquals($expected, $formatter->formatCurrency($value, $currency));
    }

    public function formatCurrencyWithDecimalStyleProvider()
    {
        return [
            [100, 'ALL', '100'],
            [100, 'BRL', '100'],
            [100, 'CRC', '100'],
            [100, 'JPY', '100'],
            [100, 'CHF', '100'],
            [-100, 'ALL', '-100'],
            [-100, 'BRL', '-100'],
            [-100, 'CRC', '-100'],
            [-100, 'JPY', '-100'],
            [-100, 'CHF', '-100'],
            [1000.12, 'ALL', '1,000.12'],
            [1000.12, 'BRL', '1,000.12'],
            [1000.12, 'CRC', '1,000.12'],
            [1000.12, 'JPY', '1,000.12'],
            [1000.12, 'CHF', '1,000.12'],
        ];
    }

    /**
     * @dataProvider formatCurrencyWithCurrencyStyleProvider
     */
    public function testFormatCurrencyWithCurrencyStyle($value, $currency, $expected)
    {
        IntlTestHelper::requireIntl($this, '63.1');

        $formatter = $this->getNumberFormatter('en', NumberFormatter::CURRENCY);
        $this->assertEquals($expected, $formatter->formatCurrency($value, $currency));
    }

    public function formatCurrencyWithCurrencyStyleProvider()
    {
        return [
            [100, 'ALL', "ALL\xc2\xa0100"],
            [-100, 'ALL', "-ALL\xc2\xa0100"],
            [1000.12, 'ALL', "ALL\xc2\xa01,000"],

            [100, 'JPY', '¥100'],
            [-100, 'JPY', '-¥100'],
            [1000.12, 'JPY', '¥1,000'],

            [100, 'EUR', '€100.00'],
            [-100, 'EUR', '-€100.00'],
            [1000.12, 'EUR', '€1,000.12'],
        ];
    }

    /**
     * @dataProvider formatCurrencyWithCurrencyStyleCostaRicanColonsRoundingProvider
     */
    public function testFormatCurrencyWithCurrencyStyleCostaRicanColonsRounding($value, $currency, $symbol, $expected)
    {
        IntlTestHelper::requireIntl($this, '63.1');

        $formatter = $this->getNumberFormatter('en', NumberFormatter::CURRENCY);
        $this->assertEquals(sprintf($expected, $symbol), $formatter->formatCurrency($value, $currency));
    }

    public function formatCurrencyWithCurrencyStyleCostaRicanColonsRoundingProvider()
    {
        return [
            [100, 'CRC', 'CRC', "%s\xc2\xa0100.00"],
            [-100, 'CRC', 'CRC', "-%s\xc2\xa0100.00"],
            [1000.12, 'CRC', 'CRC', "%s\xc2\xa01,000.12"],
        ];
    }

    /**
     * @dataProvider formatCurrencyWithCurrencyStyleBrazilianRealRoundingProvider
     */
    public function testFormatCurrencyWithCurrencyStyleBrazilianRealRounding($value, $currency, $symbol, $expected)
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::CURRENCY);
        $this->assertEquals(sprintf($expected, $symbol), $formatter->formatCurrency($value, $currency));
    }

    public function formatCurrencyWithCurrencyStyleBrazilianRealRoundingProvider()
    {
        return [
            [100, 'BRL', 'R', '%s$100.00'],
            [-100, 'BRL', 'R', '-%s$100.00'],
            [1000.12, 'BRL', 'R', '%s$1,000.12'],

            // Rounding checks
            [1000.121, 'BRL', 'R', '%s$1,000.12'],
            [1000.123, 'BRL', 'R', '%s$1,000.12'],
            [1000.125, 'BRL', 'R', '%s$1,000.12'],
            [1000.127, 'BRL', 'R', '%s$1,000.13'],
            [1000.129, 'BRL', 'R', '%s$1,000.13'],
            [11.50999, 'BRL', 'R', '%s$11.51'],
            [11.9999464, 'BRL', 'R', '%s$12.00'],
        ];
    }

    /**
     * @dataProvider formatCurrencyWithCurrencyStyleSwissRoundingProvider
     */
    public function testFormatCurrencyWithCurrencyStyleSwissRounding($value, $currency, $symbol, $expected)
    {
        IntlTestHelper::requireIntl($this, '62.1');

        $formatter = $this->getNumberFormatter('en', NumberFormatter::CURRENCY);
        $this->assertEquals(sprintf($expected, $symbol), $formatter->formatCurrency($value, $currency));
    }

    public function formatCurrencyWithCurrencyStyleSwissRoundingProvider()
    {
        return [
            [100, 'CHF', 'CHF', "%s\xc2\xa0100.00"],
            [-100, 'CHF', 'CHF', "-%s\xc2\xa0100.00"],
            [1000.12, 'CHF', 'CHF', "%s\xc2\xa01,000.12"],
            ['1000.12', 'CHF', 'CHF', "%s\xc2\xa01,000.12"],

            // Rounding checks
            [1000.121, 'CHF', 'CHF', "%s\xc2\xa01,000.12"],
            [1000.123, 'CHF', 'CHF', "%s\xc2\xa01,000.12"],
            [1000.125, 'CHF', 'CHF', "%s\xc2\xa01,000.12"],
            [1000.127, 'CHF', 'CHF', "%s\xc2\xa01,000.13"],
            [1000.129, 'CHF', 'CHF', "%s\xc2\xa01,000.13"],

            [1200000.00, 'CHF', 'CHF', "%s\xc2\xa01,200,000.00"],
            [1200000.1, 'CHF', 'CHF', "%s\xc2\xa01,200,000.10"],
            [1200000.10, 'CHF', 'CHF', "%s\xc2\xa01,200,000.10"],
            [1200000.101, 'CHF', 'CHF', "%s\xc2\xa01,200,000.10"],
        ];
    }

    public function testFormat()
    {
        $errorCode = IntlGlobals::U_ZERO_ERROR;
        $errorMessage = 'U_ZERO_ERROR';

        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $this->assertSame('9.555', $formatter->format(9.555));

        $this->assertSame($errorMessage, $this->getIntlErrorMessage());
        $this->assertSame($errorCode, $this->getIntlErrorCode());
        $this->assertFalse($this->isIntlFailure($this->getIntlErrorCode()));
        $this->assertSame($errorMessage, $formatter->getErrorMessage());
        $this->assertSame($errorCode, $formatter->getErrorCode());
        $this->assertFalse($this->isIntlFailure($formatter->getErrorCode()));
    }

    public function testFormatWithCurrencyStyle()
    {
        IntlTestHelper::requireIntl($this, '63.1');

        $formatter = $this->getNumberFormatter('en', NumberFormatter::CURRENCY);
        $this->assertEquals('¤1.00', $formatter->format(1));
    }

    /**
     * @dataProvider formatTypeInt32Provider
     */
    public function testFormatTypeInt32($formatter, $value, $expected, $message = '')
    {
        $formattedValue = $formatter->format($value, NumberFormatter::TYPE_INT32);
        $this->assertEquals($expected, $formattedValue, $message);
    }

    public function formatTypeInt32Provider()
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);

        $message = '->format() TYPE_INT32 formats inconsistently an integer if out of the 32 bit range.';

        return [
            [$formatter, 1, '1'],
            [$formatter, 1.1, '1'],
            [$formatter, 2147483648, '-2,147,483,648', $message],
            [$formatter, -2147483649, '2,147,483,647', $message],
        ];
    }

    /**
     * @dataProvider formatTypeInt32WithCurrencyStyleProvider
     */
    public function testFormatTypeInt32WithCurrencyStyle($formatter, $value, $expected, $message = '')
    {
        IntlTestHelper::requireIntl($this, '63.1');

        $formattedValue = $formatter->format($value, NumberFormatter::TYPE_INT32);
        $this->assertEquals($expected, $formattedValue, $message);
    }

    public function formatTypeInt32WithCurrencyStyleProvider()
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::CURRENCY);

        $message = '->format() TYPE_INT32 formats inconsistently an integer if out of the 32 bit range.';

        return [
            [$formatter, 1, '¤1.00'],
            [$formatter, 1.1, '¤1.00'],
            [$formatter, 2147483648, '-¤2,147,483,648.00', $message],
            [$formatter, -2147483649, '¤2,147,483,647.00', $message],
        ];
    }

    /**
     * The parse() method works differently with integer out of the 32 bit range. format() works fine.
     *
     * @dataProvider formatTypeInt64Provider
     */
    public function testFormatTypeInt64($formatter, $value, $expected)
    {
        $formattedValue = $formatter->format($value, NumberFormatter::TYPE_INT64);
        $this->assertEquals($expected, $formattedValue);
    }

    public function formatTypeInt64Provider()
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);

        return [
            [$formatter, 1, '1'],
            [$formatter, 1.1, '1'],
            [$formatter, 2147483648, '2,147,483,648'],
            [$formatter, -2147483649, '-2,147,483,649'],
        ];
    }

    /**
     * @dataProvider formatTypeInt64WithCurrencyStyleProvider
     */
    public function testFormatTypeInt64WithCurrencyStyle($formatter, $value, $expected)
    {
        IntlTestHelper::requireIntl($this, '63.1');

        $formattedValue = $formatter->format($value, NumberFormatter::TYPE_INT64);
        $this->assertEquals($expected, $formattedValue);
    }

    public function formatTypeInt64WithCurrencyStyleProvider()
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::CURRENCY);

        return [
            [$formatter, 1, '¤1.00'],
            [$formatter, 1.1, '¤1.00'],
            [$formatter, 2147483648, '¤2,147,483,648.00'],
            [$formatter, -2147483649, '-¤2,147,483,649.00'],
        ];
    }

    /**
     * @dataProvider formatTypeDoubleProvider
     */
    public function testFormatTypeDouble($formatter, $value, $expected)
    {
        $formattedValue = $formatter->format($value, NumberFormatter::TYPE_DOUBLE);
        $this->assertEquals($expected, $formattedValue);
    }

    public function formatTypeDoubleProvider()
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);

        return [
            [$formatter, 1, '1'],
            [$formatter, 1.1, '1.1'],
        ];
    }

    /**
     * @dataProvider formatTypeDoubleWithCurrencyStyleProvider
     */
    public function testFormatTypeDoubleWithCurrencyStyle($formatter, $value, $expected)
    {
        IntlTestHelper::requireIntl($this, '63.1');

        $formattedValue = $formatter->format($value, NumberFormatter::TYPE_DOUBLE);
        $this->assertEquals($expected, $formattedValue);
    }

    public function formatTypeDoubleWithCurrencyStyleProvider()
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::CURRENCY);

        return [
            [$formatter, 1, '¤1.00'],
            [$formatter, 1.1, '¤1.10'],
        ];
    }

    /**
     * @dataProvider formatTypeCurrencyProvider
     */
    public function testFormatTypeCurrency($formatter, $value)
    {
        if (\PHP_VERSION_ID >= 80000) {
            $this->expectException(\ValueError::class);
        } elseif (method_exists($this, 'expectWarning')) {
            $this->expectWarning();
        } else {
            $this->expectException(Warning::class);
        }

        $formatter->format($value, NumberFormatter::TYPE_CURRENCY);
    }

    /**
     * @dataProvider formatTypeCurrencyProvider
     */
    public function testFormatTypeCurrencyReturn($formatter, $value)
    {
        if (\PHP_VERSION_ID >= 80000) {
            $this->expectException(\ValueError::class);
        }

        $this->assertFalse(@$formatter->format($value, NumberFormatter::TYPE_CURRENCY));
    }

    public function formatTypeCurrencyProvider()
    {
        $df = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $cf = $this->getNumberFormatter('en', NumberFormatter::CURRENCY);

        return [
            [$df, 1],
            [$cf, 1],
        ];
    }

    /**
     * @dataProvider formatFractionDigitsProvider
     */
    public function testFormatFractionDigits($value, $expected, $fractionDigits = null, $expectedFractionDigits = 1)
    {
        IntlTestHelper::requireIntl($this, '62.1');

        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);

        $attributeRet = null;
        if (null !== $fractionDigits) {
            $attributeRet = $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, $fractionDigits);
        }

        $formattedValue = $formatter->format($value);
        $this->assertSame($expected, $formattedValue);
        $this->assertSame($expectedFractionDigits, $formatter->getAttribute(NumberFormatter::FRACTION_DIGITS));

        if (null !== $attributeRet) {
            $this->assertTrue($attributeRet);
        }
    }

    public function formatFractionDigitsProvider()
    {
        yield [1.123, '1.123', null, 0];
        yield [1.123, '1', 0, 0];
        yield [1.123, '1.1', 1, 1];
        yield [1.123, '1.12', 2, 2];
        yield [1.123, '1.123', -1, 0];

        if (\PHP_VERSION_ID < 80000) {
            // This dataset will produce a TypeError on php 8.
            yield [1.123, '1', 'abc', 0];
        }
    }

    /**
     * @dataProvider formatGroupingUsedProvider
     */
    public function testFormatGroupingUsed($value, $expected, $groupingUsed = null, $expectedGroupingUsed = 1)
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);

        $attributeRet = null;
        if (null !== $groupingUsed) {
            $attributeRet = $formatter->setAttribute(NumberFormatter::GROUPING_USED, $groupingUsed);
        }

        $formattedValue = $formatter->format($value);
        $this->assertSame($expected, $formattedValue);
        $this->assertSame($expectedGroupingUsed, $formatter->getAttribute(NumberFormatter::GROUPING_USED));

        if (null !== $attributeRet) {
            $this->assertTrue($attributeRet);
        }
    }

    public function formatGroupingUsedProvider()
    {
        yield [1000, '1,000', null, 1];
        yield [1000, '1000', 0, 0];
        yield [1000, '1,000', 1, 1];
        yield [1000, '1,000', 2, 1];
        yield [1000, '1,000', -1, 1];

        if (\PHP_VERSION_ID < 80000) {
            // This dataset will produce a TypeError on php 8.
            yield [1000, '1000', 'abc', 0];
        }
    }

    /**
     * @dataProvider formatRoundingModeRoundHalfUpProvider
     */
    public function testFormatRoundingModeHalfUp($value, $expected)
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
        $this->assertSame($expected, $formatter->format($value), '->format() with ROUND_HALFUP rounding mode.');
    }

    public function formatRoundingModeRoundHalfUpProvider()
    {
        // The commented value is differently rounded by intl's NumberFormatter in 32 and 64 bit architectures
        return [
            [1.121, '1.12'],
            [1.123, '1.12'],
            // [1.125, '1.13'],
            [1.127, '1.13'],
            [1.129, '1.13'],
            [1020 / 100, '10.20'],
        ];
    }

    /**
     * @dataProvider formatRoundingModeRoundHalfDownProvider
     */
    public function testFormatRoundingModeHalfDown($value, $expected)
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFDOWN);
        $this->assertSame($expected, $formatter->format($value), '->format() with ROUND_HALFDOWN rounding mode.');
    }

    public function formatRoundingModeRoundHalfDownProvider()
    {
        return [
            [1.121, '1.12'],
            [1.123, '1.12'],
            [1.125, '1.12'],
            [1.127, '1.13'],
            [1.129, '1.13'],
            [1020 / 100, '10.20'],
        ];
    }

    /**
     * @dataProvider formatRoundingModeRoundHalfEvenProvider
     */
    public function testFormatRoundingModeHalfEven($value, $expected)
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFEVEN);
        $this->assertSame($expected, $formatter->format($value), '->format() with ROUND_HALFEVEN rounding mode.');
    }

    public function formatRoundingModeRoundHalfEvenProvider()
    {
        return [
            [1.121, '1.12'],
            [1.123, '1.12'],
            [1.125, '1.12'],
            [1.127, '1.13'],
            [1.129, '1.13'],
            [1020 / 100, '10.20'],
        ];
    }

    /**
     * @dataProvider formatRoundingModeRoundCeilingProvider
     */
    public function testFormatRoundingModeCeiling($value, $expected)
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_CEILING);
        $this->assertSame($expected, $formatter->format($value), '->format() with ROUND_CEILING rounding mode.');
    }

    public function formatRoundingModeRoundCeilingProvider()
    {
        return [
            [1.123, '1.13'],
            [1.125, '1.13'],
            [1.127, '1.13'],
            [-1.123, '-1.12'],
            [-1.125, '-1.12'],
            [-1.127, '-1.12'],
            [1020 / 100, '10.20'],
        ];
    }

    /**
     * @dataProvider formatRoundingModeRoundFloorProvider
     */
    public function testFormatRoundingModeFloor($value, $expected)
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_FLOOR);
        $this->assertSame($expected, $formatter->format($value), '->format() with ROUND_FLOOR rounding mode.');
    }

    public function formatRoundingModeRoundFloorProvider()
    {
        return [
            [1.123, '1.12'],
            [1.125, '1.12'],
            [1.127, '1.12'],
            [-1.123, '-1.13'],
            [-1.125, '-1.13'],
            [-1.127, '-1.13'],
            [1020 / 100, '10.20'],
        ];
    }

    /**
     * @dataProvider formatRoundingModeRoundDownProvider
     */
    public function testFormatRoundingModeDown($value, $expected)
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_DOWN);
        $this->assertSame($expected, $formatter->format($value), '->format() with ROUND_DOWN rounding mode.');
    }

    public function formatRoundingModeRoundDownProvider()
    {
        return [
            [1.123, '1.12'],
            [1.125, '1.12'],
            [1.127, '1.12'],
            [-1.123, '-1.12'],
            [-1.125, '-1.12'],
            [-1.127, '-1.12'],
            [1020 / 100, '10.20'],
        ];
    }

    /**
     * @dataProvider formatRoundingModeRoundUpProvider
     */
    public function testFormatRoundingModeUp($value, $expected)
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

        $formatter->setAttribute(NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_UP);
        $this->assertSame($expected, $formatter->format($value), '->format() with ROUND_UP rounding mode.');
    }

    public function formatRoundingModeRoundUpProvider()
    {
        return [
            [1.123, '1.13'],
            [1.125, '1.13'],
            [1.127, '1.13'],
            [-1.123, '-1.13'],
            [-1.125, '-1.13'],
            [-1.127, '-1.13'],
            [1020 / 100, '10.20'],
        ];
    }

    public function testGetLocale()
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $this->assertEquals('en', $formatter->getLocale());
    }

    public function testGetSymbol()
    {
        $decimalFormatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $currencyFormatter = $this->getNumberFormatter('en', NumberFormatter::CURRENCY);

        $r = new \ReflectionClassConstant(NumberFormatter::class, 'EN_SYMBOLS');
        $expected = $r->getValue();

        for ($i = 0; $i <= 17; ++$i) {
            $this->assertSame($expected[1][$i], $decimalFormatter->getSymbol($i));
            $this->assertSame($expected[2][$i], $currencyFormatter->getSymbol($i));
        }
    }

    public function testGetTextAttribute()
    {
        IntlTestHelper::requireIntl($this, '63.1');

        $decimalFormatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $currencyFormatter = $this->getNumberFormatter('en', NumberFormatter::CURRENCY);

        $r = new \ReflectionClassConstant(NumberFormatter::class, 'EN_TEXT_ATTRIBUTES');
        $expected = $r->getValue();

        for ($i = 0; $i <= 5; ++$i) {
            $this->assertSame($expected[1][$i], $decimalFormatter->getTextAttribute($i));
            $this->assertSame($expected[2][$i], $currencyFormatter->getTextAttribute($i));
        }
    }

    /**
     * @dataProvider parseProvider
     */
    public function testParse($value, $expected, $message, $expectedPosition, $groupingUsed = true)
    {
        IntlTestHelper::requireIntl($this, '62.1');

        $position = 0;
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::GROUPING_USED, $groupingUsed);
        $parsedValue = $formatter->parse($value, NumberFormatter::TYPE_DOUBLE, $position);
        $this->assertSame($expected, $parsedValue, $message);
        $this->assertSame($expectedPosition, $position, $message);

        if (false === $expected) {
            $errorCode = IntlGlobals::U_PARSE_ERROR;
            $errorMessage = 'Number parsing failed: U_PARSE_ERROR';
        } else {
            $errorCode = IntlGlobals::U_ZERO_ERROR;
            $errorMessage = 'U_ZERO_ERROR';
        }

        $this->assertSame($errorMessage, $this->getIntlErrorMessage());
        $this->assertSame($errorCode, $this->getIntlErrorCode());
        $this->assertSame(0 !== $errorCode, $this->isIntlFailure($this->getIntlErrorCode()));
        $this->assertSame($errorMessage, $formatter->getErrorMessage());
        $this->assertSame($errorCode, $formatter->getErrorCode());
        $this->assertSame(0 !== $errorCode, $this->isIntlFailure($formatter->getErrorCode()));
    }

    public function parseProvider()
    {
        return [
            ['prefix1', false, '->parse() does not parse a number with a string prefix.', 0],
            ['prefix1', false, '->parse() does not parse a number with a string prefix.', 0, false],
            ['1.4suffix', 1.4, '->parse() parses a number with a string suffix.', 3],
            ['1.4suffix', 1.4, '->parse() parses a number with a string suffix.', 3, false],
            ['1,234.4suffix', 1234.4, '->parse() parses a number with a string suffix.', 7],
            ['1,234.4suffix', 1.0, '->parse() parses a number with a string suffix.', 1, false],
            ['-.4suffix', -0.4, '->parse() parses a negative dot float with suffix.', 3],
            ['-.4suffix', -0.4, '->parse() parses a negative dot float with suffix.', 3, false],
            [',4', false, '->parse() does not parse when invalid grouping used.', 0],
            [',4', false, '->parse() does not parse when invalid grouping used.', 0, false],
            ['123,4', false, '->parse() does not parse when invalid grouping used.', 0],
            ['123,4', 123.0, '->parse() truncates invalid grouping when grouping is disabled.', 3, false],
            ['123,a4', 123.0, '->parse() truncates a string suffix.', 3],
            ['123,a4', 123.0, '->parse() truncates a string suffix.', 3, false],
            ['-123,4', false, '->parse() does not parse when invalid grouping used.', 1],
            ['-123,4', -123.0, '->parse() truncates invalid grouping when grouping is disabled.', 4, false],
            ['-123,4567', false, '->parse() does not parse when invalid grouping used.', 1],
            ['-123,4567', -123.0, '->parse() truncates invalid grouping when grouping is disabled.', 4, false],
            ['-123,456,789', -123456789.0, '->parse() parses a number with grouping.', 12],
            ['-123,456,789', -123.0, '->parse() truncates a group if grouping is disabled.', 4, false],
            ['-123,456,789.66', -123456789.66, '->parse() parses a number with grouping.', 15],
            ['-123,456,789.66', -123.00, '->parse() truncates a group if grouping is disabled.', 4, false],
            ['-123,456789.66', false, '->parse() does not parse when invalid grouping used.', 1],
            ['-123,456789.66', -123.00, '->parse() truncates a group if grouping is disabled.', 4, false],
            ['-123456,789.66', false, '->parse() does not parse when invalid grouping used.', 1],
            ['-123456,789.66', -123456.00, '->parse() truncates a group if grouping is disabled.', 7, false],
            ['-123,456,78', false, '->parse() does not parse when invalid grouping used.', 1],
            ['-123,456,78', -123.0, '->parse() truncates a group if grouping is disabled.', 4, false],
            ['-123,45,789', false, '->parse() does not parse when invalid grouping used.', 1],
            ['-123,45,789', -123.0, '->parse() truncates a group if grouping is disabled.', 4, false],
            ['-123,,456', -123.0, '->parse() parses when grouping is duplicated.', 4],
            ['-123,,456', -123.0, '->parse() parses when grouping is disabled.', 4, false],
            ['-123,,4', -123.0, '->parse() parses when grouping is duplicated.', 4],
            ['-123,,4', -123.0, '->parse() parses when grouping is duplicated.', 4, false],
            ['239.', 239.0, '->parse() parses when string ends with decimal separator.', 4],
            ['239.', 239.0, '->parse() parses when string ends with decimal separator.', 4, false],
        ];
    }

    public function testParseTypeDefault()
    {
        if (\PHP_VERSION_ID >= 80000) {
            $this->expectException(\ValueError::class);
        } elseif (method_exists($this, 'expectWarning')) {
            $this->expectWarning();
        } else {
            $this->expectException(Warning::class);
        }

        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->parse('1', NumberFormatter::TYPE_DEFAULT);
    }

    /**
     * @dataProvider parseTypeInt32Provider
     */
    public function testParseTypeInt32($value, $expected, $message = '')
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $parsedValue = $formatter->parse($value, NumberFormatter::TYPE_INT32);
        $this->assertSame($expected, $parsedValue, $message);
    }

    public function parseTypeInt32Provider()
    {
        return [
            ['1', 1],
            ['1.1', 1],
            ['.1', 0],
            ['2,147,483,647', 2147483647],
            ['-2,147,483,648', -2147483647 - 1],
            ['2,147,483,648', false, '->parse() TYPE_INT32 returns false when the number is greater than the integer positive range.'],
            ['-2,147,483,649', false, '->parse() TYPE_INT32 returns false when the number is greater than the integer negative range.'],
        ];
    }

    public function testParseTypeInt64With32BitIntegerInPhp32Bit()
    {
        IntlTestHelper::require32Bit($this);

        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);

        $parsedValue = $formatter->parse('2,147,483,647', NumberFormatter::TYPE_INT64);
        $this->assertIsInt($parsedValue);
        $this->assertEquals(2147483647, $parsedValue);

        $parsedValue = $formatter->parse('-2,147,483,648', NumberFormatter::TYPE_INT64);
        $this->assertIsInt($parsedValue);
        $this->assertEquals(-2147483648, $parsedValue);
    }

    public function testParseTypeInt64With32BitIntegerInPhp64Bit()
    {
        IntlTestHelper::require64Bit($this);

        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);

        $parsedValue = $formatter->parse('2,147,483,647', NumberFormatter::TYPE_INT64);
        $this->assertIsInt($parsedValue);
        $this->assertEquals(2147483647, $parsedValue);

        $parsedValue = $formatter->parse('-2,147,483,648', NumberFormatter::TYPE_INT64);
        $this->assertIsInt($parsedValue);
        $this->assertEquals(-2147483647 - 1, $parsedValue);
    }

    /**
     * If PHP is compiled in 32bit mode, the returned value for a 64bit integer are float numbers.
     */
    public function testParseTypeInt64With64BitIntegerInPhp32Bit()
    {
        IntlTestHelper::require32Bit($this);

        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);

        // int 64 using only 32 bit range strangeness
        $parsedValue = $formatter->parse('2,147,483,648', NumberFormatter::TYPE_INT64);
        $this->assertIsFloat($parsedValue);
        $this->assertEquals(2147483648, $parsedValue, '->parse() TYPE_INT64 does not use true 64 bit integers, using only the 32 bit range.');

        $parsedValue = $formatter->parse('-2,147,483,649', NumberFormatter::TYPE_INT64);
        $this->assertIsFloat($parsedValue);
        $this->assertEquals(-2147483649, $parsedValue, '->parse() TYPE_INT64 does not use true 64 bit integers, using only the 32 bit range.');
    }

    /**
     * If PHP is compiled in 64bit mode, the returned value for a 64bit integer are 32bit integer numbers.
     */
    public function testParseTypeInt64With64BitIntegerInPhp64Bit()
    {
        IntlTestHelper::require64Bit($this);

        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);

        $parsedValue = $formatter->parse('2,147,483,648', NumberFormatter::TYPE_INT64);
        $this->assertIsInt($parsedValue);

        $this->assertEquals(2147483648, $parsedValue, '->parse() TYPE_INT64 uses true 64 bit integers (PHP >= 5.3.14 and PHP >= 5.4.4).');

        $parsedValue = $formatter->parse('-2,147,483,649', NumberFormatter::TYPE_INT64);
        $this->assertIsInt($parsedValue);

        $this->assertEquals(-2147483649, $parsedValue, '->parse() TYPE_INT64 uses true 64 bit integers (PHP >= 5.3.14 and PHP >= 5.4.4).');
    }

    /**
     * @dataProvider parseTypeDoubleProvider
     */
    public function testParseTypeDouble($value, $expectedValue)
    {
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $parsedValue = $formatter->parse($value, NumberFormatter::TYPE_DOUBLE);
        $this->assertEqualsWithDelta($expectedValue, $parsedValue, 0.001);
    }

    public function parseTypeDoubleProvider()
    {
        return [
            ['1', (float) 1],
            ['1.1', 1.1],
            ['9,223,372,036,854,775,808', 9223372036854775808],
            ['-9,223,372,036,854,775,809', -9223372036854775809],
        ];
    }

    public function testParseTypeCurrency()
    {
        if (\PHP_VERSION_ID >= 80000) {
            $this->expectException(\ValueError::class);
        } elseif (method_exists($this, 'expectWarning')) {
            $this->expectWarning();
        } else {
            $this->expectException(Warning::class);
        }

        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->parse('1', NumberFormatter::TYPE_CURRENCY);
    }

    public function testParseWithNotNullPositionValue()
    {
        $position = 1;
        $formatter = $this->getNumberFormatter('en', NumberFormatter::DECIMAL);
        $formatter->parse('123', NumberFormatter::TYPE_DOUBLE, $position);
        $this->assertEquals(3, $position);
    }

    /**
     * @return NumberFormatter|\NumberFormatter
     */
    abstract protected function getNumberFormatter(string $locale = 'en', string $style = null, string $pattern = null);

    abstract protected function getIntlErrorMessage(): string;

    abstract protected function getIntlErrorCode(): int;

    /**
     * @param int $errorCode
     */
    abstract protected function isIntlFailure($errorCode): bool;
}
