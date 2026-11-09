<?php

/* Copyright (C) 2026 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace Saturne\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SaturnePublishedApiCheck;

/**
 * Tests for dev/ci/published-api.php, the tokenizer of the « API Saturne publiee » check.
 *
 * Only analyse() is exercised: the git side is covered by replaying real cases (DoliMeet #953
 * against Saturne 23.2.1 must name fetchUserSignatory).
 */
class PublishedApiCheckTest extends TestCase
{
    /**
     * Load the script once: its command line block only runs when it is the invoked script.
     */
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../../dev/ci/published-api.php';
    }

    /**
     * Usages of a kind, as « key:line ».
     *
     * @param  string       $code PHP source
     * @param  string       $kind function, method, class or file
     * @return list<string>       Usages
     */
    private function usages(string $code, string $kind): array
    {
        $usages = [];
        foreach (SaturnePublishedApiCheck::analyse($code)['usages'] as $usage) {
            if ($usage['kind'] === $kind) {
                $usages[] = $usage['key'] . ':' . $usage['line'];
            }
        }
        return $usages;
    }

    // ─── Definitions ──────────────────────────────────────────────────────────

    public function testGlobalFunctionsIncludingConditionalOnes(): void
    {
        $analysis = SaturnePublishedApiCheck::analyse("<?php\nfunction saturne_a() {}\nif (!function_exists('saturne_b')) {\n    function &saturne_b() {}\n}\n");

        $this->assertSame(['saturne_a' => 'saturne_a', 'saturne_b' => 'saturne_b'], $analysis['functions']);
    }

    public function testMethodsCarryTheirVisibilityAndClass(): void
    {
        $code     = "<?php\nclass SaturneX {\n    public function a() {}\n    function b() {}\n    protected static function c() {}\n    private function d() {}\n    public static function e() {}\n}\n";
        $analysis = SaturnePublishedApiCheck::analyse($code);

        $public = [];
        foreach ($analysis['methods'] as $method) {
            $public[$method['name']] = $method['public'];
            $this->assertSame('SaturneX', $method['class']);
        }
        $this->assertSame(['a' => true, 'b' => true, 'c' => false, 'd' => false, 'e' => true], $public);
        $this->assertSame(['saturnex' => 'SaturneX'], $analysis['classes']);
        $this->assertSame([], $analysis['functions']);
    }

    public function testClosuresAndAnonymousClassesDeclareNothing(): void
    {
        $code     = "<?php\nclass A {\n    public function a() {\n        \$f = function () {};\n        \$o = new class {\n            public function hidden() {}\n        };\n    }\n}\nfunction after() {}\n";
        $analysis = SaturnePublishedApiCheck::analyse($code);

        $this->assertSame(['a'], array_column($analysis['methods'], 'name'));
        $this->assertSame(['after' => 'after'], $analysis['functions']);
    }

    public function testClassConstantDoesNotOpenAClass(): void
    {
        $analysis = SaturnePublishedApiCheck::analyse("<?php\n\$c = Foo::class;\nfunction f() {}\n");

        $this->assertSame([], $analysis['classes']);
        $this->assertSame(['f' => 'f'], $analysis['functions']);
    }

    // ─── Usages ───────────────────────────────────────────────────────────────

    public function testFunctionCallsSkipDefinitionsCommentsAndStrings(): void
    {
        $code = "<?php\n// saturne_a();\n/* saturne_a(); */\n\$s = 'saturne_a()';\nfunction saturne_a() {}\nsaturne_a ();\n\\saturne_b();\n";

        $this->assertSame(['saturne_a:6', 'saturne_b:7'], $this->usages($code, 'function'));
    }

    public function testMethodCallsAndParentCalls(): void
    {
        $code     = "<?php\n\$o->fetchUserSignatory(1);\n\$o?->other();\nSaturneX::make();\nparent::init();\n";
        $analysis = SaturnePublishedApiCheck::analyse($code);

        $this->assertSame(['fetchusersignatory:2', 'other:3', 'make:4', 'init:5'], $this->usages($code, 'method'));
        $parent = array_values(array_filter($analysis['usages'], fn (array $u): bool => $u['parent']));
        $this->assertSame('init', $parent[0]['key']);
        $this->assertCount(1, $parent);
    }

    public function testClassUsages(): void
    {
        $code = "<?php\nclass A extends SaturneXlsxExport implements I1, I2 {}\n\$x = new SaturneSignature(\$db);\n\$y = new \\Other;\nif (\$x instanceof SaturneSignature) {}\nSaturneTool::run();\nself::run();\n";

        $this->assertSame(['saturnexlsxexport:2', 'i1:2', 'i2:2', 'saturnesignature:3', 'other:4', 'saturnesignature:5', 'saturnetool:6'], $this->usages($code, 'class'));
    }

    public function testSaturneFilePathsInStrings(): void
    {
        $code = "<?php\nrequire_once dol_buildpath('/saturne/core/tpl/medias/photo_editor_modal.tpl.php');\ndol_include_once('/saturne/class/saturnexlsxexport.class.php');\n";

        $this->assertSame(['core/tpl/medias/photo_editor_modal.tpl.php:2', 'class/saturnexlsxexport.class.php:3'], $this->usages($code, 'file'));
    }

    public function testLineNumbersFollowMultiLineTokens(): void
    {
        $code = "<?php\n/**\n * Doc\n */\n\$a = 'x\ny';\nsaturne_a();\n";

        $this->assertSame(['saturne_a:7'], $this->usages($code, 'function'));
    }

    // ─── Guards ───────────────────────────────────────────────────────────────

    public function testGuardsCollectTheirStringArguments(): void
    {
        $code     = "<?php\nif (function_exists('saturne_a')) {}\nif (method_exists(\$o, 'fetchUserSignatory')) {}\nif (class_exists('\\\\SaturneX')) {}\nif (is_callable('SaturneY::make')) {}\nif (file_exists(dol_buildpath('/saturne/lib/asset.lib.php'))) {}\n";
        $analysis = SaturnePublishedApiCheck::analyse($code);

        $this->assertSame(
            ['saturne_a', 'fetchusersignatory', 'saturnex', 'make', 'lib/asset.lib.php'],
            array_keys($analysis['guards'])
        );
    }
}
