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
use stdClass;

/**
 * Tests for the pure selection helpers of lib/linked_object.lib.php
 *
 * Only the functions that need neither database nor Dolibarr runtime are covered here.
 * The database bound helpers are verified by the inspection scripts described in the plan.
 */
class LinkedObjectLibTest extends TestCase
{
    /**
     * Load linked_object.lib.php once for the entire class.
     */
    public static function setUpBeforeClass(): void
    {
        require_once __DIR__ . '/../../../lib/linked_object.lib.php';
    }

    /**
     * Each test starts from an empty constant set.
     */
    protected function setUp(): void
    {
        global $conf;

        $conf->global = new stdClass();
    }

    // ─── saturne_filter_linkable_objects ──────────────────────────────────────

    public function testFilterDropsAliasEntries(): void
    {
        $objectsMetadata = [
            'contract' => ['link_name' => 'contrat', 'table_element' => 'contrat'],
            'contrat'  => ['link_name' => 'contrat', 'table_element' => 'contrat', 'alias_of' => 'contract'],
        ];

        $result = saturne_filter_linkable_objects($objectsMetadata);

        $this->assertSame(['contract'], array_keys($result));
    }

    public function testFilterDeduplicatesOnTableElement(): void
    {
        $objectsMetadata = [
            'task'         => ['link_name' => 'project_task', 'table_element' => 'projet_task'],
            'project_task' => ['link_name' => 'project_task', 'table_element' => 'projet_task'],
        ];

        $result = saturne_filter_linkable_objects($objectsMetadata);

        $this->assertSame(['task'], array_keys($result));
    }

    public function testFilterDropsExcludedLinkNamePrefixes(): void
    {
        $objectsMetadata = [
            'product'          => ['link_name' => 'product', 'table_element' => 'product'],
            'digiquali_survey' => ['link_name' => 'digiquali_survey', 'table_element' => 'digiquali_survey'],
        ];

        $result = saturne_filter_linkable_objects($objectsMetadata, ['digiquali_']);

        $this->assertSame(['product'], array_keys($result));
    }

    public function testFilterDropsEntriesWithoutTableElement(): void
    {
        $objectsMetadata = [
            'product' => ['link_name' => 'product', 'table_element' => 'product'],
            'broken'  => ['link_name' => 'broken'],
        ];

        $result = saturne_filter_linkable_objects($objectsMetadata);

        $this->assertSame(['product'], array_keys($result));
    }

    public function testFilterKeepsMetadataUntouched(): void
    {
        $objectsMetadata = [
            'product' => ['link_name' => 'product', 'table_element' => 'product', 'picto' => 'product'],
        ];

        $result = saturne_filter_linkable_objects($objectsMetadata);

        $this->assertSame('product', $result['product']['picto']);
    }

    // ─── saturne_get_enabled_linked_object_types ──────────────────────────────

    public function testEnabledTypesKeepsOnlyConstantsSetToOne(): void
    {
        global $conf;

        $conf->global->DIGIQUALI_SHEET_LINK_PRODUCT = 1;
        $conf->global->DIGIQUALI_SHEET_LINK_TICKET  = 0;

        $linkableObjects = [
            'product' => ['link_name' => 'product', 'table_element' => 'product'],
            'ticket'  => ['link_name' => 'ticket', 'table_element' => 'ticket'],
        ];

        $result = saturne_get_enabled_linked_object_types($linkableObjects, 'DIGIQUALI_SHEET_LINK_');

        $this->assertSame(['product'], $result);
    }

    public function testEnabledTypesUppercasesCompositeObjectType(): void
    {
        global $conf;

        $conf->global->DIGIQUALI_SHEET_LINK_DOLIMEET_TRAINSESS = 1;

        $linkableObjects = [
            'dolimeet_trainsess' => ['link_name' => 'dolimeet_trainsess', 'table_element' => 'dolimeet_session'],
        ];

        $result = saturne_get_enabled_linked_object_types($linkableObjects, 'DIGIQUALI_SHEET_LINK_');

        $this->assertSame(['dolimeet_trainsess'], $result);
    }

    public function testEnabledTypesTreatsMissingConstantAsDisabled(): void
    {
        $linkableObjects = ['bom' => ['link_name' => 'bom', 'table_element' => 'bom']];

        $result = saturne_get_enabled_linked_object_types($linkableObjects, 'DIGIQUALI_SHEET_LINK_');

        $this->assertSame([], $result);
    }

    // ─── saturne_get_export_extrafield_aliases ────────────────────────────────

    public function testExportAliasesMapEachExtrafieldsJoin(): void
    {
        $sql  = ' FROM llx_commande as c LEFT JOIN llx_societe as s ON s.rowid = c.fk_soc';
        $sql .= ' LEFT JOIN llx_societe_extrafields as extra4 ON s.rowid = extra4.fk_object';
        $sql .= ' LEFT JOIN llx_commande_extrafields as extra ON c.rowid = extra.fk_object';
        $sql .= ' LEFT JOIN llx_product_lot_extrafields AS extra3 on pl.rowid = extra3.fk_object';

        $result = saturne_get_export_extrafield_aliases($sql, 'llx_');

        $this->assertSame(['extra4' => 'societe', 'extra' => 'commande', 'extra3' => 'product_lot'], $result);
    }

    public function testExportAliasesAcceptJoinWithoutAs(): void
    {
        $result = saturne_get_export_extrafield_aliases(' LEFT JOIN llx_product_extrafields extra ON p.rowid = extra.fk_object', 'llx_');

        $this->assertSame(['extra' => 'product'], $result);
    }

    public function testExportAliasesIgnoreJoinWithoutAlias(): void
    {
        $result = saturne_get_export_extrafield_aliases(' LEFT JOIN llx_product_extrafields ON llx_product.rowid = fk_object', 'llx_');

        $this->assertSame([], $result);
    }

    public function testExportAliasesUseTheGivenPrefix(): void
    {
        $result = saturne_get_export_extrafield_aliases(' LEFT JOIN llxhk_societe_extrafields as extra ON s.rowid = extra.fk_object', 'llxhk_');

        $this->assertSame(['extra' => 'societe'], $result);
    }

    // ─── saturne_count_export_model_extrafield_references ─────────────────────

    public function testExportReferencesResolveTheAliasOfEachModel(): void
    {
        $exportModels = [
            ['type' => 'commande_1', 'field' => 'c.rowid,c.ref,extra.qc_frequency,extra4.qc_frequency'],
            ['type' => 'societe_1', 'field' => 's.nom,extra.qc_frequency'],
        ];
        $aliasesByDataset = [
            'commande_1' => ['extra' => 'commande', 'extra4' => 'societe'],
            'societe_1'  => ['extra' => 'societe'],
        ];

        $result = saturne_count_export_model_extrafield_references($exportModels, $aliasesByDataset, ['qc_frequency']);

        $this->assertSame(['qc_frequency' => ['commande' => 1, 'societe' => 2]], $result);
    }

    public function testExportReferencesSkipMainTableAliasesAndOtherFields(): void
    {
        $exportModels     = [['type' => 'produit_1', 'field' => 'p.qc_frequency,extra.other_field,p.ref']];
        $aliasesByDataset = ['produit_1' => ['extra' => 'product']];

        $result = saturne_count_export_model_extrafield_references($exportModels, $aliasesByDataset, ['qc_frequency']);

        $this->assertSame([], $result);
    }

    public function testExportReferencesCountUnknownDatasetUnderStar(): void
    {
        $exportModels = [['type' => 'mymodule_1', 'field' => 't.ref,extra.qc_frequency']];

        $result = saturne_count_export_model_extrafield_references($exportModels, [], ['qc_frequency']);

        $this->assertSame(['qc_frequency' => ['*' => 1]], $result);
    }

    public function testExportReferencesCountAModelOncePerTable(): void
    {
        $exportModels     = [['type' => 'facture_1', 'field' => 'extra.qc_frequency,extra.qc_frequency,f.ref']];
        $aliasesByDataset = ['facture_1' => ['extra' => 'facture']];

        $result = saturne_count_export_model_extrafield_references($exportModels, $aliasesByDataset, ['qc_frequency']);

        $this->assertSame(['qc_frequency' => ['facture' => 1]], $result);
    }

    public function testExportReferencesKeepCommasInsideParentheses(): void
    {
        $exportModels     = [['type' => 'facture_1', 'field' => 'CONCAT(f.ref, f.label) as x,extra.qc_frequency']];
        $aliasesByDataset = ['facture_1' => ['extra' => 'facture']];

        $result = saturne_count_export_model_extrafield_references($exportModels, $aliasesByDataset, ['qc_frequency']);

        $this->assertSame(['qc_frequency' => ['facture' => 1]], $result);
    }
}
