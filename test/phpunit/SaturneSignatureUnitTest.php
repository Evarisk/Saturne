<?php
/* Copyright (C) 2024 EVARISK <technique@evarisk.com>
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

/**
 * \file    test/phpunit/SaturneSignatureUnitTest.php
 * \ingroup test
 * \brief   PHPUnit test
 * \remarks To run this script as CLI: phpunit filename.php
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php using relative path
if (!$res && file_exists("../../../htdocs/master.inc.php")) {
    $res = require_once dirname(__FILE__).'/../../../htdocs/master.inc.php';
}
if (!$res && file_exists("../../../../htdocs/master.inc.php")) {
    $res = require_once dirname(__FILE__).'/../../../../htdocs/master.inc.php';
}
if (!$res && file_exists("../../../../../htdocs/master.inc.php")) {
    $res = require_once dirname(__FILE__).'/../../../../../htdocs/master.inc.php';;
}
if (!$res) {
    die("Include of main fails");
}

// Load Saturne libraries
require_once __DIR__ . '/../../class/saturnesignature.class.php';

use PHPUnit\Framework\TestCase;

/**
 * Class for SaturneSignatureUnitTest
 *
 * @backupGlobals          disabled
 * @backupStaticAttributes enabled
 * @remarks                backupGlobals must be disabled to have db, conf, user and lang not erased
 */
class SaturneSignatureUnitTest extends TestCase
{
    /**
     * @var Conf Conf handler
     */
    protected Conf $savConf;

    /**
     * @var User User handler
     */
    protected User $savUser;

    /**
     * @var Translate Database handler
     */
    protected Translate $savLangs;

    /**
     * @var DoliDB Database handler
     */
    protected DoliDB $savDb;

    /**
     * @var SaturneSignature SaturneSignature handler
     */
    private SaturneSignature $signatory;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return void
     */
    public function __construct()
    {
        global $conf, $db, $langs, $user;

        parent::__construct();

        $this->savConf  = $conf;
        $this->savDb    = $db;
        $this->savLangs = $langs;
        $this->savUser  = $user;

        $this->savLangs->load('error@saturne');

        print __METHOD__ . ' db->type=' . $db->type . ' user->id=' . $user->id . "\n";
        print __METHOD__ . ' ok' . "\n";
    }

    /**
     * Global test setup
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        global $db;

        if (!isModEnabled('saturne')) {
            print __METHOD__ . ' module digiriskdolibarr must be enabled.' . "\n";
            die(1);
        }

        $db->begin(); // This is to have all actions inside a transaction even if test launched without suite

        print __METHOD__ . "\n";
    }

    /**
     * Init phpunit tests
     *
     * @return void
     */
    protected function setUp(): void
    {
        global $conf, $db, $langs, $user;

        $conf            = $this->savConf;
        $db              = $this->savDb;
        $langs           = $this->savLangs;
        $user            = $this->savUser;
        $this->signatory = new SaturneSignature($db);

        print __METHOD__ . "\n";
    }

    /**
     * Unit test teardown
     *
     * @return  void
     */
    protected function tearDown(): void
    {
        print __METHOD__ . "\n";
    }

    /**
     * Global test teardown
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        global $db;
        $db->rollback();

        print __METHOD__ . "\n";
    }

    /**
     * @test
     * @covers SaturneSignature::fetchSignatories
     * @return void
     * @throws Exception
     */
    public function testFetchSignatoriesWithValidParameters()
    {
        $fkObject   = 1;
        $objectType = 'document';
        $result     = $this->signatory->fetchSignatories($fkObject, $objectType);

        $this->assertIsArray($result, 'Expected fetchSignatories to return an array with valid parameters.');
    }

    /**
     * @test
     * @covers SaturneSignature::fetchSignatories
     * @return void
     * @throws Exception
     */
    public function testFetchSignatoriesWithInvalidFkObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($this->savLangs->transnoentities('ErrorArgumentMustBePositiveInteger', '$fkObject'));

        $this->signatory->fetchSignatories(0, 'document');
    }

    /**
     * @test
     * @covers SaturneSignature::fetchSignatories
     * @return void
     * @throws Exception
     */
    public function testFetchSignatoriesWithEmptyObjectType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($this->savLangs->transnoentities('ErrorArgumentMustBeNotEmptyString', '$objectType'));

        $this->signatory->fetchSignatories(1, '');
    }

    /**
     * @test
     * @covers SaturneSignature::fetchSignatory
     * @return void
     * @throws Exception
     */
    public function testFetchSignatoryWithRoleAndValidParameters()
    {
        $role = 'manager';
        $fkObject = 1;
        $objectType = 'document';
        $result = $this->signatory->fetchSignatory($role, $fkObject, $objectType);

        $this->assertIsArray($result, 'Expected fetchSignatory to return an array when role is specified and parameters are valid.');
    }

    /**
     * @test
     * @covers SaturneSignature::fetchSignatory
     * @return void
     * @throws Exception
     */
    public function testFetchSignatoryWithInvalidFkObject()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($this->savLangs->transnoentities('ErrorArgumentMustBePositiveInteger', '$fkObject'));

        $this->signatory->fetchSignatory('manager', -1, 'document');
    }

    /** @test */
    public function testFetchSignatoryWithEmptyResults()
    {
        $signatoryMock = $this->createMock(SaturneSignature::class);
        $signatoryMock->method('fetchAll')->willReturn([]);

        $result = $signatoryMock->fetchSignatory('manager', 1, 'document');
        $this->assertEquals(-1, $result, 'Expected fetchSignatory to return -1 when no results are found.');
    }

    /** @test */
    public function testFetchSignatoryWithValidRoleAndResults()
    {
        $signatoryMock = $this->createMock(SaturneSignature::class);

        // Mock de retour avec des données de signataire
        $mockResults = [
            (object) ['role' => 'manager', 'id' => 1],
            (object) ['role' => 'manager', 'id' => 2]
        ];
        $signatoryMock->method('fetchAll')->willReturn($mockResults);

        $result = $signatoryMock->fetchSignatory('manager', 1, 'document');

        // Vérifier la structure du tableau de retour
        $this->assertIsArray($result);
        $this->assertArrayHasKey('manager', $result);
        $this->assertCount(2, $result['manager'], 'Expected two signatories under the role "manager".');
        $this->assertEquals(1, $result['manager'][1]->id);
        $this->assertEquals(2, $result['manager'][2]->id);
    }

    /** @test */
    public function testFetchSignatoryWithNonexistentRole()
    {
        $signatoryMock = $this->createMock(SaturneSignature::class);
        $signatoryMock->method('fetchAll')->willReturn([]);

        $result = $signatoryMock->fetchSignatory('nonexistent', 1, 'document');
        $this->assertEquals(NULL, $result, 'Expected fetchSignatory to return -1 when no results are found for a nonexistent role.');
    }

    /** @test */
    public function testFetchSignatoriesWithMultipleRoles()
    {
        $signatoryMock = $this->createMock(SaturneSignature::class);

        // Simule le retour de plusieurs signataires avec des rôles différents
        $mockResults = [
            (object) ['role' => 'manager', 'id' => 1],
            (object) ['role' => 'supervisor', 'id' => 2],
            (object) ['role' => 'manager', 'id' => 3],
        ];
        $signatoryMock->method('fetchAll')->willReturn($mockResults);

        $result = $signatoryMock->fetchSignatories(1, 'document');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('manager', $result);
        $this->assertArrayHasKey('supervisor', $result);
        $this->assertCount(2, $result['manager'], 'Expected two signatories under the role "manager".');
        $this->assertCount(1, $result['supervisor'], 'Expected one signatory under the role "supervisor".');
    }

    /**
     * @test
     * @covers SaturneSignature::fetchSignatory
     * @return void
     * @throws Exception
     */
    public function testFetchSignatoryWithEmptyObjectType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($this->savLangs->transnoentities('ErrorArgumentMustBeNotEmptyString', '$objectType'));

        $this->signatory->fetchSignatory('manager', 1, '');
    }
}
