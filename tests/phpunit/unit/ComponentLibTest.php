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
 * Tests for lib/component.lib.php
 *
 * component.lib.php has no external require_once — it only uses the $langs global
 * already set up by the bootstrap. All tests run in pure stub mode.
 */
class ComponentLibTest extends TestCase
{
    /**
     * Load component.lib.php once for the entire class.
     */
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../../lib/component.lib.php';
    }

    // ─── saturne_get_badge_component_html ─────────────────────────────────────

    public function testBadgeHtmlContainsWpeoBadgeClass(): void
    {
        $html = saturne_get_badge_component_html();

        $this->assertStringContainsString('wpeo-badge', $html);
    }

    public function testBadgeHtmlContainsDefaultIconClass(): void
    {
        $html = saturne_get_badge_component_html();

        $this->assertStringContainsString('fas fa-user', $html);
    }

    public function testBadgeHtmlUsesProvidedId(): void
    {
        $html = saturne_get_badge_component_html(['id' => 'my-badge-42']);

        $this->assertStringContainsString('id="my-badge-42"', $html);
    }

    public function testBadgeHtmlUsesProvidedTitle(): void
    {
        $html = saturne_get_badge_component_html(['title' => 'TestTitle']);

        $this->assertStringContainsString('TestTitle', $html);
    }

    public function testBadgeHtmlEscapesXssInId(): void
    {
        $html = saturne_get_badge_component_html(['id' => '<script>alert(1)</script>']);

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testBadgeHtmlRendersCustomClassName(): void
    {
        $html = saturne_get_badge_component_html(['className' => 'custom-class']);

        $this->assertStringContainsString('wpeo-badge custom-class', $html);
    }

    public function testBadgeHtmlRendersDetailsLines(): void
    {
        $html = saturne_get_badge_component_html(['details' => ['Detail A', 'Detail B']]);

        $this->assertStringContainsString('Detail A', $html);
        $this->assertStringContainsString('Detail B', $html);
    }

    public function testBadgeHtmlRendersActionLink(): void
    {
        $html = saturne_get_badge_component_html([
            'actions' => [
                ['label' => 'Edit', 'iconClass' => 'fas fa-edit', 'href' => '/edit/1'],
            ],
        ]);

        $this->assertStringContainsString('badge__actions', $html);
        $this->assertStringContainsString('/edit/1', $html);
    }

    public function testBadgeHtmlRendersActionButtonWhenNoHref(): void
    {
        $html = saturne_get_badge_component_html([
            'actions' => [
                ['label' => 'Delete', 'iconClass' => 'fas fa-trash'],
            ],
        ]);

        $this->assertStringContainsString('<button', $html);
        $this->assertStringContainsString('type="button"', $html);
    }

    // ─── saturne_get_button_component_html ────────────────────────────────────

    public function testButtonHtmlIsString(): void
    {
        $html = saturne_get_button_component_html([]);

        $this->assertIsString($html);
    }

    public function testButtonHtmlContainsButtonComponentClass(): void
    {
        $html = saturne_get_button_component_html(['label' => 'Save']);

        $this->assertStringContainsString('button-component', $html);
    }
}
