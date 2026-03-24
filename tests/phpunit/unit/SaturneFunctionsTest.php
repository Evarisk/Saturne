<?php

/* Copyright (C) 2022-2026 EVARISK <technique@evarisk.com>
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

/**
 * Tests for pure utility functions in lib/saturne_functions.lib.php.
 *
 * NOTE: saturne_functions.lib.php requires Dolibarr core class files via
 * DOL_DOCUMENT_ROOT. In CI, set up a Dolibarr checkout and export
 * DOL_DOCUMENT_ROOT to its htdocs/ directory before running these tests.
 * The tested functions themselves (saturne_show_notice, saturne_css_for_field)
 * have no runtime dependency on $db/$conf and pass in pure stub mode.
 */
class SaturneFunctionsTest extends TestCase
{
    /**
     * Load only the functions we need via targeted require_once.
     * saturne_functions.lib.php chains several requires; we rely on
     * DOL_DOCUMENT_ROOT pointing to a real (or stubbed) Dolibarr htdocs.
     */
    public static function setUpBeforeClass(): void
    {
        if (!function_exists('saturne_show_notice')) {
            require_once __DIR__ . '/../../../lib/saturne_functions.lib.php';
        }
    }

    // ─── saturne_show_notice ──────────────────────────────────────────────────

    public function testShowNoticeReturnsString(): void
    {
        $result = saturne_show_notice('Title', 'Message', 'error');

        $this->assertIsString($result);
    }

    public function testShowNoticeContainsWpeoNoticeClass(): void
    {
        $html = saturne_show_notice('Title', 'Message', 'info');

        $this->assertStringContainsString('wpeo-notice', $html);
        $this->assertStringContainsString('notice-info', $html);
    }

    public function testShowNoticeIsHiddenByDefault(): void
    {
        $html = saturne_show_notice();

        $this->assertStringContainsString('hidden', $html);
    }

    public function testShowNoticeIsVisibleWhenFlagSet(): void
    {
        $html = saturne_show_notice('T', 'M', 'error', 'my-id', true);

        $this->assertStringNotContainsString('hidden', $html);
    }

    public function testShowNoticeContainsTitleAndMessage(): void
    {
        $html = saturne_show_notice('My Title', 'My Message');

        $this->assertStringContainsString('My Title', $html);
        $this->assertStringContainsString('My Message', $html);
    }

    public function testShowNoticeContainsCloseButtonByDefault(): void
    {
        $html = saturne_show_notice();

        $this->assertStringContainsString('notice-close', $html);
    }

    public function testShowNoticeOmitsCloseButtonWhenDisabled(): void
    {
        $html = saturne_show_notice('T', 'M', 'error', 'id', false, false);

        $this->assertStringNotContainsString('notice-close', $html);
    }

    public function testShowNoticeContainsCustomId(): void
    {
        $html = saturne_show_notice('T', 'M', 'error', 'custom-notice-id');

        $this->assertStringContainsString('id="custom-notice-id"', $html);
    }

    public function testShowNoticeRendersTranslationInputs(): void
    {
        $html = saturne_show_notice('T', 'M', 'error', 'id', false, true, '', ['key1' => 'val1']);

        $this->assertStringContainsString('name="key1"', $html);
        $this->assertStringContainsString('value="val1"', $html);
    }

    // ─── saturne_css_for_field ────────────────────────────────────────────────

    public function testStatusKeyReturnsCenterClass(): void
    {
        $result = saturne_css_for_field([], 'status');

        $this->assertSame('center', $result);
    }

    public function testRefKeyReturnsNowraponallClass(): void
    {
        $result = saturne_css_for_field([], 'ref');

        $this->assertSame('nowraponall', $result);
    }

    public function testDateTypeReturnsCenterClass(): void
    {
        $result = saturne_css_for_field(['type' => 'date'], 'myfield');

        $this->assertSame('center', $result);
    }

    public function testDatetimeTypeReturnsCenterClass(): void
    {
        $result = saturne_css_for_field(['type' => 'datetime'], 'myfield');

        $this->assertSame('center', $result);
    }

    public function testIntegerTypeReturnsRightClass(): void
    {
        $result = saturne_css_for_field(['type' => 'integer', 'label' => 'Count'], 'quantity');

        $this->assertSame('right', $result);
    }

    public function testPriceTypeReturnsRightClass(): void
    {
        $result = saturne_css_for_field(['type' => 'price', 'label' => 'Amount'], 'amount');

        $this->assertSame('right', $result);
    }

    public function testIntegerTypeWithIdKeyReturnsEmpty(): void
    {
        // 'id' and 'rowid' keys are excluded from the right alignment.
        $result = saturne_css_for_field(['type' => 'integer', 'label' => 'ID'], 'id');

        $this->assertSame('', $result);
    }

    public function testIntegerTypeWithTechnicalIDLabelReturnsEmpty(): void
    {
        $result = saturne_css_for_field(['type' => 'integer', 'label' => 'TechnicalID'], 'myfield');

        $this->assertSame('', $result);
    }

    public function testCsslistAppendedToResult(): void
    {
        $result = saturne_css_for_field(['csslist' => 'bold'], 'ref');

        $this->assertStringContainsString('bold', $result);
    }

    public function testFallbackCssAppendedWhenNoCsslist(): void
    {
        $result = saturne_css_for_field(['css' => 'italic'], 'ref');

        $this->assertStringContainsString('italic', $result);
    }

    public function testUnknownKeyAndNoTypeReturnsEmpty(): void
    {
        $result = saturne_css_for_field([], 'unknown_field');

        $this->assertSame('', $result);
    }

    // ─── saturne_load_list_parameters ────────────────────────────────────────

    public function testLoadListParametersReturnsArrayWithExpectedKeys(): void
    {
        $result = saturne_load_list_parameters('testcontext');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('confirm', $result);
        $this->assertArrayHasKey('contextpage', $result);
        $this->assertArrayHasKey('optioncss', $result);
        $this->assertArrayHasKey('mode', $result);
    }

    public function testLoadListParametersDefaultContextpage(): void
    {
        // Without $_REQUEST['contextpage'], falls back to '{contextName}list'.
        unset($_REQUEST['contextpage']);

        $result = saturne_load_list_parameters('myobject');

        $this->assertSame('myobjectlist', $result['contextpage']);
    }

    public function testLoadListParametersCustomContextpage(): void
    {
        $_REQUEST['contextpage'] = 'myCustomContext';

        $result = saturne_load_list_parameters('myobject');

        $this->assertSame('myCustomContext', $result['contextpage']);

        unset($_REQUEST['contextpage']);
    }
}
