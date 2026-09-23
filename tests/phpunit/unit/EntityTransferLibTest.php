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
 * Tests for the statement rewriting of lib/entity_transfer.lib.php
 *
 * Those functions read the SQL of a dump: a value holding a comma, a parenthesis or an
 * escaped quote must survive the rewriting untouched.
 */
class EntityTransferLibTest extends TestCase
{
    /**
     * Load entity_transfer.lib.php once for the entire class.
     */
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../../lib/entity_transfer.lib.php';
    }

    // ─── saturne_entity_transfer_split_values ─────────────────────────────────

    public function testSplitValuesReadsEveryTuple(): void
    {
        $tuples = saturne_entity_transfer_split_values("('1', 'a'), ('2', 'b')");

        $this->assertCount(2, $tuples);
        $this->assertSame(["'1'", "'a'"], $tuples[0]);
        $this->assertSame(["'2'", "'b'"], $tuples[1]);
    }

    public function testSplitValuesKeepsCommasInsideStrings(): void
    {
        $tuples = saturne_entity_transfer_split_values("('1', 'Dupont, Jean'), ('2', 'x')");

        $this->assertSame(["'1'", "'Dupont, Jean'"], $tuples[0]);
        $this->assertSame(["'2'", "'x'"], $tuples[1]);
    }

    public function testSplitValuesKeepsParenthesesInsideStrings(): void
    {
        $tuples = saturne_entity_transfer_split_values("('Atelier (nord)', NULL)");

        $this->assertSame(["'Atelier (nord)'", 'NULL'], $tuples[0]);
    }

    public function testSplitValuesKeepsEscapedQuotes(): void
    {
        $tuples = saturne_entity_transfer_split_values("('L\\'atelier', 'b')");

        $this->assertSame(["'L\\'atelier'", "'b'"], $tuples[0]);
    }

    // ─── saturne_entity_transfer_drop_unknown_columns ─────────────────────────

    public function testDropUnknownColumnsRemovesTheColumnAndItsValues(): void
    {
        $statement = "INSERT INTO llx_societe (`rowid`, `nom`, `ref_int`) VALUES ('1', 'ACME', 'x'), ('2', 'Globex', NULL)";
        $rewritten = saturne_entity_transfer_drop_unknown_columns($statement, ['rowid', 'nom']);

        $this->assertSame("INSERT INTO llx_societe (`rowid`, `nom`) VALUES ('1', 'ACME'), ('2', 'Globex')", $rewritten);
    }

    public function testDropUnknownColumnsLeavesAKnownStatementAlone(): void
    {
        $statement = "INSERT INTO llx_societe (`rowid`, `nom`) VALUES ('1', 'ACME')";

        $this->assertSame($statement, saturne_entity_transfer_drop_unknown_columns($statement, ['rowid', 'nom', 'tms']));
    }

    public function testDropUnknownColumnsKeepsValuesHoldingSeparators(): void
    {
        $statement = "INSERT INTO llx_societe (`nom`, `note`, `ref_int`) VALUES ('Dupont, Jean', 'Atelier (nord)', 'x')";
        $rewritten = saturne_entity_transfer_drop_unknown_columns($statement, ['nom', 'note']);

        $this->assertSame("INSERT INTO llx_societe (`nom`, `note`) VALUES ('Dupont, Jean', 'Atelier (nord)')", $rewritten);
    }

    public function testDropUnknownColumnsHandlesReplaceAndIgnore(): void
    {
        $replace = saturne_entity_transfer_drop_unknown_columns("REPLACE INTO llx_c_x (`rowid`, `dead`) VALUES ('1', '2')", ['rowid']);
        $ignore  = saturne_entity_transfer_drop_unknown_columns("INSERT IGNORE INTO llx_c_x (`rowid`, `dead`) VALUES ('1', '2')", ['rowid']);

        $this->assertSame("REPLACE INTO llx_c_x (`rowid`) VALUES ('1')", $replace);
        $this->assertSame("INSERT IGNORE INTO llx_c_x (`rowid`) VALUES ('1')", $ignore);
    }

    public function testDropUnknownColumnsLeavesAStatementWithNoKnownColumnAlone(): void
    {
        $statement = "INSERT INTO llx_societe (`dead_one`, `dead_two`) VALUES ('1', '2')";

        $this->assertSame($statement, saturne_entity_transfer_drop_unknown_columns($statement, ['rowid']));
    }

    public function testDropUnknownColumnsLeavesWhatItDoesNotUnderstandAlone(): void
    {
        $statement = 'DELETE FROM llx_societe WHERE entity IN (1)';

        $this->assertSame($statement, saturne_entity_transfer_drop_unknown_columns($statement, ['rowid']));
    }
}
