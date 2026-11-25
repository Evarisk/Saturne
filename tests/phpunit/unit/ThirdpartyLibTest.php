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

/**
 * Tests for lib/thirdparty.lib.php
 *
 * A thirdparty merge must find every table of the modules and every column pointing at the
 * absorbed thirdparty, and nothing else.
 */
class ThirdpartyLibTest extends TestCase
{
    /**
     * Load thirdparty.lib.php once for the entire class.
     */
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../../lib/thirdparty.lib.php';
    }

    // ─── saturne_thirdparty_created_tables ────────────────────────────────────

    public function testCreatedTablesReadsEveryCreateStatement(): void
    {
        $sql = "CREATE TABLE llx_dolimeet_session(\n  rowid integer\n) ENGINE=innodb;\n"
            . "create table IF NOT EXISTS `llx_element_workinghours` (rowid integer);";

        $this->assertSame(['dolimeet_session', 'element_workinghours'], saturne_thirdparty_created_tables($sql));
    }

    public function testCreatedTablesIgnoresAlterAndInsert(): void
    {
        $sql = "ALTER TABLE llx_dolimeet_session ADD COLUMN fk_soc integer;\n"
            . "INSERT INTO llx_c_dolimeet_type (ref) VALUES ('x');";

        $this->assertSame([], saturne_thirdparty_created_tables($sql));
    }

    // ─── saturne_thirdparty_link_columns ──────────────────────────────────────

    public function testLinkColumnsFindsTheThirdpartyColumns(): void
    {
        $columns = ['rowid' => 'integer', 'fk_soc' => 'integer', 'element_type' => 'varchar(50)', 'fk_element' => 'integer'];

        $this->assertSame([
            ['column' => 'fk_soc', 'type' => ''],
            ['column' => 'fk_element', 'type' => 'element_type'],
        ], saturne_thirdparty_link_columns($columns));
    }

    public function testLinkColumnsReadsEveryTypeAndIdPair(): void
    {
        $columns = ['element_type' => 'varchar(255)', 'element_id' => 'integer', 'object_type' => 'varchar(255)', 'fk_object' => 'integer'];

        $this->assertSame([
            ['column' => 'element_id', 'type' => 'element_type'],
            ['column' => 'fk_object', 'type' => 'object_type'],
        ], saturne_thirdparty_link_columns($columns));
    }

    public function testLinkColumnsNeedsTheTypeBesideAnElementId(): void
    {
        // A fk_element without element_type points at an object of the module itself, never at a thirdparty
        $columns = ['rowid' => 'integer', 'fk_element' => 'integer', 'entity' => 'integer'];

        $this->assertSame([], saturne_thirdparty_link_columns($columns));
    }
}
