<?php

declare(strict_types=1);

/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiunit\framework\helpers;

use function extension_loaded;
use yii\helpers\Inflector;
use yiiunit\TestCase;

/**
 * @group helpers
 *
 * @internal
 * @coversNothing
 */
final class InflectorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // destroy application, Helper must work without Yii::$app
        $this->destroyApplication();
    }

    public function testPluralize(): void
    {
        $testData = [
            'move' => 'moves',
            'foot' => 'feet',
            'child' => 'children',
            'human' => 'humans',
            'man' => 'men',
            'staff' => 'staff',
            'tooth' => 'teeth',
            'person' => 'people',
            'mouse' => 'mice',
            'touch' => 'touches',
            'hash' => 'hashes',
            'shelf' => 'shelves',
            'potato' => 'potatoes',
            'bus' => 'buses',
            'test' => 'tests',
            'car' => 'cars',
            'netherlands' => 'netherlands',
            'currency' => 'currencies',
            'software' => 'software',
            'hardware' => 'hardware',
        ];

        foreach ($testData as $testIn => $testOut) {
            $this->assertSame($testOut, Inflector::pluralize($testIn));
            $this->assertSame(ucfirst($testOut), ucfirst(Inflector::pluralize($testIn)));
        }
    }

    public function testSingularize(): void
    {
        $testData = [
            'moves' => 'move',
            'feet' => 'foot',
            'children' => 'child',
            'humans' => 'human',
            'men' => 'man',
            'staff' => 'staff',
            'teeth' => 'tooth',
            'people' => 'person',
            'mice' => 'mouse',
            'touches' => 'touch',
            'hashes' => 'hash',
            'shelves' => 'shelf',
            'potatoes' => 'potato',
            'buses' => 'bus',
            'tests' => 'test',
            'cars' => 'car',
            'Netherlands' => 'Netherlands',
            'currencies' => 'currency',
            'software' => 'software',
            'hardware' => 'hardware',
        ];

        foreach ($testData as $testIn => $testOut) {
            $this->assertSame($testOut, Inflector::singularize($testIn));
            $this->assertSame(ucfirst($testOut), ucfirst(Inflector::singularize($testIn)));
        }
    }

    public function testTitleize(): void
    {
        $this->assertSame('Me my self and i', Inflector::titleize('MeMySelfAndI'));
        $this->assertSame('Me My Self And I', Inflector::titleize('MeMySelfAndI', true));
        $this->assertSame('Треба Більше Тестів!', Inflector::titleize('ТребаБільшеТестів!', true));
    }

    public function testCamelize(): void
    {
        $this->assertSame('MeMySelfAndI', Inflector::camelize('me my_self-andI'));
        $this->assertSame('QweQweEwq', Inflector::camelize('qwe qwe^ewq'));
        $this->assertSame('ВідомоЩоТестиЗберігатьНашіНЕРВИ', Inflector::camelize('Відомо, що тести зберігать наші НЕРВИ! 🙃'));
    }

    public function testUnderscore(): void
    {
        $this->assertSame('me_my_self_and_i', Inflector::underscore('MeMySelfAndI'));
        $this->assertSame('кожний_тест_особливий', Inflector::underscore('КожнийТестОсобливий'));
    }

    public function testCamel2words(): void
    {
        $this->assertSame('Camel Case', Inflector::camel2words('camelCase'));
        $this->assertSame('Camel Case', Inflector::camel2words('CamelCase'));
        $this->assertSame('Lower Case', Inflector::camel2words('lower_case'));
        $this->assertSame('Tricky Stuff It Is Testing', Inflector::camel2words(' tricky_stuff.it-is testing... '));
        $this->assertSame('І Це Дійсно Так!', Inflector::camel2words('ІЦеДійсноТак!'));
        $this->assertSame('Test', Inflector::camel2words('TEST'));
        $this->assertSame('X Foo', Inflector::camel2words('XFoo'));
        $this->assertSame('Foo Bar Baz', Inflector::camel2words('FooBARBaz'));
        $this->assertSame('Generate Csrf', Inflector::camel2words('generateCSRF'));
        $this->assertSame('Generate Csrf Token', Inflector::camel2words('generateCSRFToken'));
        $this->assertSame('Csrf Token Generator', Inflector::camel2words('CSRFTokenGenerator'));
        $this->assertSame('Foo Bar', Inflector::camel2words('foo bar'));
        $this->assertSame('Foo Bar', Inflector::camel2words('foo BAR'));
        $this->assertSame('Foo Bar', Inflector::camel2words('Foo Bar'));
        $this->assertSame('Foo Bar', Inflector::camel2words('FOO BAR'));
    }

    public function testCamel2id(): void
    {
        $this->assertSame('post-tag', Inflector::camel2id('PostTag'));
        $this->assertSame('post_tag', Inflector::camel2id('PostTag', '_'));
        $this->assertSame('єдиний_код', Inflector::camel2id('ЄдинийКод', '_'));

        $this->assertSame('post-tag', Inflector::camel2id('postTag'));
        $this->assertSame('post_tag', Inflector::camel2id('postTag', '_'));
        $this->assertSame('єдиний_код', Inflector::camel2id('єдинийКод', '_'));

        $this->assertSame('foo-ybar', Inflector::camel2id('FooYBar', '-', false));
        $this->assertSame('foo_ybar', Inflector::camel2id('fooYBar', '_', false));
        $this->assertSame('невже_іце_працює', Inflector::camel2id('НевжеІЦеПрацює', '_', false));

        $this->assertSame('foo-y-bar', Inflector::camel2id('FooYBar', '-', true));
        $this->assertSame('foo_y_bar', Inflector::camel2id('fooYBar', '_', true));
        $this->assertSame('foo_y_bar', Inflector::camel2id('fooYBar', '_', true));
        $this->assertSame('невже_і_це_працює', Inflector::camel2id('НевжеІЦеПрацює', '_', true));
    }

    public function testId2camel(): void
    {
        $this->assertSame('PostTag', Inflector::id2camel('post-tag'));
        $this->assertSame('PostTag', Inflector::id2camel('post_tag', '_'));
        $this->assertSame('ЄдинийСвіт', Inflector::id2camel('єдиний_світ', '_'));

        $this->assertSame('PostTag', Inflector::id2camel('post-tag'));
        $this->assertSame('PostTag', Inflector::id2camel('post_tag', '_'));
        $this->assertSame('НевжеІЦеПрацює', Inflector::id2camel('невже_і_це_працює', '_'));

        $this->assertSame('ShouldNotBecomeLowercased', Inflector::id2camel('ShouldNotBecomeLowercased', '_'));

        $this->assertSame('FooYBar', Inflector::id2camel('foo-y-bar'));
        $this->assertSame('FooYBar', Inflector::id2camel('foo_y_bar', '_'));
    }

    public function testHumanize(): void
    {
        $this->assertSame('Me my self and i', Inflector::humanize('me_my_self_and_i'));
        $this->assertSame('Me My Self And I', Inflector::humanize('me_my_self_and_i', true));
        $this->assertSame('Але й веселі ці ваші тести', Inflector::humanize('але_й_веселі_ці_ваші_тести'));
    }

    public function testVariablize(): void
    {
        $this->assertSame('customerTable', Inflector::variablize('customer_table'));
        $this->assertSame('ひらがなHepimiz', Inflector::variablize('ひらがな_hepimiz'));
    }

    public function testTableize(): void
    {
        $this->assertSame('customer_tables', Inflector::tableize('customerTable'));
    }

    public function testSlugCommons(): void
    {
        $data = [
            '' => '',
            'hello world 123' => 'hello-world-123',
            'remove.!?[]{}…symbols' => 'removesymbols',
            'minus-sign' => 'minus-sign',
            'mdash—sign' => 'mdash-sign',
            'ndash–sign' => 'ndash-sign',
            'áàâéèêíìîóòôúùûã' => 'aaaeeeiiiooouuua',
            'älä lyö ääliö ööliä läikkyy' => 'ala-lyo-aalio-oolia-laikkyy',
        ];

        foreach ($data as $source => $expected) {
            if (extension_loaded('intl')) {
                $this->assertSame($expected, FallbackInflector::slug($source));
            }
            $this->assertSame($expected, Inflector::slug($source));
        }
    }

    public function testSlugReplacements(): void
    {
        $this->assertSame('dont_replace_replacement', Inflector::slug('dont replace_replacement', '_'));
        $this->assertSame('remove_trailing_replacements', Inflector::slug('_remove trailing replacements_', '_'));
        $this->assertSame('remove_excess_replacements', Inflector::slug(' _ _ remove excess _ _ replacements_', '_'));
        $this->assertSame('thisrepisreprepreplacement', Inflector::slug('this is REP-lacement', 'REP'));
        $this->assertSame('0_100_kmh', Inflector::slug('0-100 Km/h', '_'));
        $this->assertSame('testtext', Inflector::slug('test text', ''));
    }

    public function testSlugIntl(): void
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('intl extension is required.');
        }

        // Some test strings are from https://github.com/bergie/midgardmvc_helper_urlize. Thank you, Henri Bergius!
        $data = [
            // Korean
            '해동검도' => 'haedong-geomdo',
            // Hiragana
            'ひらがな' => 'hiragana',
            // Georgian
            'საქართველო' => 'sakartvelo',
            // Arabic
            'العربي' => 'alrby',
            'عرب' => 'rb',
            // Hebrew
            'עִבְרִית' => 'iberiyt',
            // Turkish
            'Sanırım hepimiz aynı şeyi düşünüyoruz.' => 'sanirim-hepimiz-ayni-seyi-dusunuyoruz',
            // Russian
            'недвижимость' => 'nedvizimost',
            'Контакты' => 'kontakty',
            // Chinese
            '美国' => 'mei-guo',
            // Estonian
            'Jääär' => 'jaaar',
        ];

        foreach ($data as $source => $expected) {
            $this->assertSame($expected, Inflector::slug($source));
        }
    }

    public function testTransliterateStrict(): void
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('intl extension is required.');
        }

        // Some test strings are from https://github.com/bergie/midgardmvc_helper_urlize. Thank you, Henri Bergius!
        $data = [
            // Korean
            '해동검도' => 'haedong-geomdo',
            // Hiragana
            'ひらがな' => 'hiragana',
            // Georgian
            'საქართველო' => 'sakartvelo',
            // Arabic
            'العربي' => 'ạlʿrby',
            'عرب' => 'ʿrb',
            // Hebrew
            'עִבְרִית' => 'ʻibĕriyţ',
            // Turkish
            'Sanırım hepimiz aynı şeyi düşünüyoruz.' => 'Sanırım hepimiz aynı şeyi düşünüyoruz.',

            // Russian
            'недвижимость' => 'nedvižimostʹ',
            'Контакты' => 'Kontakty',

            // Ukrainian
            'Українська: ґанок, європа' => 'Ukraí̈nsʹka: g̀anok, êvropa',

            // Serbian
            'Српска: ђ, њ, џ!' => 'Srpska: đ, n̂, d̂!',

            // Spanish
            '¿Español?' => '¿Español?',
            // Chinese
            '美国' => 'měi guó',
        ];

        foreach ($data as $source => $expected) {
            $this->assertSame($expected, Inflector::transliterate($source, Inflector::TRANSLITERATE_STRICT));
        }
    }

    public function testTransliterateMedium(): void
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('intl extension is required.');
        }

        // Some test strings are from https://github.com/bergie/midgardmvc_helper_urlize. Thank you, Henri Bergius!
        $data = [
            // Korean
            '해동검도' => ['haedong-geomdo'],
            // Hiragana
            'ひらがな' => ['hiragana'],
            // Georgian
            'საქართველო' => ['sakartvelo'],
            // Arabic
            'العربي' => ['alʿrby'],
            'عرب' => ['ʿrb'],
            // Hebrew
            'עִבְרִית' => ['\'iberiyt', 'ʻiberiyt'],
            // Turkish
            'Sanırım hepimiz aynı şeyi düşünüyoruz.' => ['Sanirim hepimiz ayni seyi dusunuyoruz.'],

            // Russian
            'недвижимость' => ['nedvizimost\'', 'nedvizimostʹ'],
            'Контакты' => ['Kontakty'],

            // Ukrainian
            'Українська: ґанок, європа' => ['Ukrainsʹka: ganok, evropa', 'Ukrains\'ka: ganok, evropa'],

            // Serbian
            'Српска: ђ, њ, џ!' => ['Srpska: d, n, d!'],

            // Spanish
            '¿Español?' => ['¿Espanol?'],
            // Chinese
            '美国' => ['mei guo'],
        ];

        foreach ($data as $source => $allowed) {
            $this->assertIsOneOf(Inflector::transliterate($source, Inflector::TRANSLITERATE_MEDIUM), $allowed);
        }
    }

    public function testTransliterateLoose(): void
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('intl extension is required.');
        }

        // Some test strings are from https://github.com/bergie/midgardmvc_helper_urlize. Thank you, Henri Bergius!
        $data = [
            // Korean
            '해동검도' => ['haedong-geomdo'],
            // Hiragana
            'ひらがな' => ['hiragana'],
            // Georgian
            'საქართველო' => ['sakartvelo'],
            // Arabic
            'العربي' => ['alrby'],
            'عرب' => ['rb'],
            // Hebrew
            'עִבְרִית' => ['\'iberiyt', 'iberiyt'],
            // Turkish
            'Sanırım hepimiz aynı şeyi düşünüyoruz.' => ['Sanirim hepimiz ayni seyi dusunuyoruz.'],

            // Russian
            'недвижимость' => ['nedvizimost\'', 'nedvizimost'],
            'Контакты' => ['Kontakty'],

            // Ukrainian
            'Українська: ґанок, європа' => ['Ukrainska: ganok, evropa', 'Ukrains\'ka: ganok, evropa'],

            // Serbian
            'Српска: ђ, њ, џ!' => ['Srpska: d, n, d!'],

            // Spanish
            '¿Español?' => ['Espanol?'],
            // Chinese
            '美国' => ['mei guo'],
        ];

        foreach ($data as $source => $allowed) {
            $this->assertIsOneOf(Inflector::transliterate($source, Inflector::TRANSLITERATE_LOOSE), $allowed);
        }
    }

    public function testSlugPhp(): void
    {
        $data = [
            'we have недвижимость' => 'we-have',
        ];

        foreach ($data as $source => $expected) {
            $this->assertSame($expected, FallbackInflector::slug($source));
        }
    }

    public function testClassify(): void
    {
        $this->assertSame('CustomerTable', Inflector::classify('customer_tables'));
    }

    public function testOrdinalize(): void
    {
        $this->assertSame('21st', Inflector::ordinalize('21'));
        $this->assertSame('22nd', Inflector::ordinalize('22'));
        $this->assertSame('23rd', Inflector::ordinalize('23'));
        $this->assertSame('24th', Inflector::ordinalize('24'));
        $this->assertSame('25th', Inflector::ordinalize('25'));
        $this->assertSame('111th', Inflector::ordinalize('111'));
        $this->assertSame('113th', Inflector::ordinalize('113'));
    }

    public function testSentence(): void
    {
        $array = [];
        $this->assertSame('', Inflector::sentence($array));

        $array = ['Spain'];
        $this->assertSame('Spain', Inflector::sentence($array));

        $array = ['Spain', 'France'];
        $this->assertSame('Spain and France', Inflector::sentence($array));

        $array = ['Spain', 'France', 'Italy'];
        $this->assertSame('Spain, France and Italy', Inflector::sentence($array));

        $array = ['Spain', 'France', 'Italy', 'Germany'];
        $this->assertSame('Spain, France, Italy and Germany', Inflector::sentence($array));

        $array = ['Spain', 'France'];
        $this->assertSame('Spain or France', Inflector::sentence($array, ' or '));

        $array = ['Spain', 'France', 'Italy'];
        $this->assertSame('Spain, France or Italy', Inflector::sentence($array, ' or '));

        $array = ['Spain', 'France'];
        $this->assertSame('Spain and France', Inflector::sentence($array, ' and ', ' or ', ' - '));

        $array = ['Spain', 'France', 'Italy'];
        $this->assertSame('Spain - France or Italy', Inflector::sentence($array, ' and ', ' or ', ' - '));
    }
}
