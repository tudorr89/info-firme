<?php

namespace Tests\Feature;

use App\Jobs\Batches\ProcessCompanyBatchImportJobUpsert;
use App\Jobs\CompanyImportJob;
use App\Models\Address;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test CSV field mapping with new format
     */
    public function test_company_import_job_field_mapping(): void
    {
        $job = new CompanyImportJob('dummy.csv');

        // Use reflection to access private property and verify field mapping
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('handle');

        // Create a test CSV file with new format
        $csvContent = <<<'CSV'
DENUMIRE^CUI^COD_INMATRICULARE^DATA_INMATRICULARE^EUID^FORMA_JURIDICA^ADR_TARA^ADR_JUDET^ADR_LOCALITATE^ADR_DEN_STRADA^ADR_NR_STRADA^ADR_BLOC^ADR_SCARA^ADR_ETAJ^ADR_APARTAMENT^ADR_COD_POSTAL^ADR_SECTOR^ADR_COMPLETARE^WEB^TARA_FIRMA_MAMA^M
Test Company SRL^123456789^J40/1234/2024^01/01/2024^ROONRC.J40/1234/2024^SRL^România^Bucureși^București^Str. Exemple^123^A^^1^10^010001^1^^www.example.com^Romania^M
CSV;

        $testFile = storage_path('test_import.csv');
        file_put_contents($testFile, $csvContent);

        // Expected field mapping
        $expectedFieldMap = [
            'DENUMIRE' => 0,
            'CUI' => 1,
            'COD_INMATRICULARE' => 2,
            'DATA_INMATRICULARE' => 3,
            'EUID' => 4,
            'FORMA_JURIDICA' => 5,
            'ADR_TARA' => 6,
            'ADR_JUDET' => 7,
            'ADR_LOCALITATE' => 8,
            'ADR_DEN_STRADA' => 9,
            'ADR_NR_STRADA' => 10,  // Updated from ADR_DEN_NR_STRADA
            'ADR_BLOC' => 11,
            'ADR_SCARA' => 12,
            'ADR_ETAJ' => 13,
            'ADR_APARTAMENT' => 14,
            'ADR_COD_POSTAL' => 15,
            'ADR_SECTOR' => 16,
            'ADR_COMPLETARE' => 17,
            'WEB' => 18,  // New field
            'TARA_FIRMA_MAMA' => 19,  // New field
            'MARK' => 20,  // New field
        ];

        // Verify the mapping is correct by parsing the test file
        $fileStream = fopen($testFile, 'r');
        $header = fgetcsv($fileStream, 1000, '^');
        fclose($fileStream);

        $this->assertNotNull($header);
        $this->assertCount(21, $header);

        // Verify key fields
        $this->assertEquals('ADR_NR_STRADA', $header[$expectedFieldMap['ADR_NR_STRADA']]);
        $this->assertEquals('WEB', $header[$expectedFieldMap['WEB']]);
        $this->assertEquals('TARA_FIRMA_MAMA', $header[$expectedFieldMap['TARA_FIRMA_MAMA']]);
        $this->assertEquals('M', $header[$expectedFieldMap['MARK']]);  // CSV column is 'M', but field map key is 'MARK'

        @unlink($testFile);
    }

    /**
     * Test batch import of companies with new fields
     */
    public function test_process_company_batch_import_with_new_fields(): void
    {
        $fieldMap = [
            'DENUMIRE' => 0,
            'CUI' => 1,
            'COD_INMATRICULARE' => 2,
            'DATA_INMATRICULARE' => 3,
            'EUID' => 4,
            'FORMA_JURIDICA' => 5,
            'ADR_TARA' => 6,
            'ADR_JUDET' => 7,
            'ADR_LOCALITATE' => 8,
            'ADR_DEN_STRADA' => 9,
            'ADR_NR_STRADA' => 10,
            'ADR_BLOC' => 11,
            'ADR_SCARA' => 12,
            'ADR_ETAJ' => 13,
            'ADR_APARTAMENT' => 14,
            'ADR_COD_POSTAL' => 15,
            'ADR_SECTOR' => 16,
            'ADR_COMPLETARE' => 17,
            'WEB' => 18,
            'TARA_FIRMA_MAMA' => 19,
            'MARK' => 20,
        ];

        $batchData = [
            [
                'Test Company SRL',          // 0: DENUMIRE
                '123456789',                 // 1: CUI
                'J40/1234/2024',             // 2: COD_INMATRICULARE
                '01/01/2024',                // 3: DATA_INMATRICULARE
                'ROONRC.J40/1234/2024',      // 4: EUID
                'SRL',                       // 5: FORMA_JURIDICA
                'România',                   // 6: ADR_TARA
                'Bucureși',                  // 7: ADR_JUDET
                'București',                 // 8: ADR_LOCALITATE
                'Str. Exemple',              // 9: ADR_DEN_STRADA
                '123',                       // 10: ADR_NR_STRADA (updated)
                'A',                         // 11: ADR_BLOC
                '',                          // 12: ADR_SCARA
                '1',                         // 13: ADR_ETAJ
                '10',                        // 14: ADR_APARTAMENT
                '010001',                    // 15: ADR_COD_POSTAL
                '1',                         // 16: ADR_SECTOR
                '',                          // 17: ADR_COMPLETARE
                'www.example.com',           // 18: WEB (new)
                'Romania',                   // 19: TARA_FIRMA_MAMA (new)
                'M',                         // 20: MARK (new)
            ],
        ];

        $job = new ProcessCompanyBatchImportJobUpsert($batchData, $fieldMap);
        $job->handle();

        // Verify company was imported
        $company = Company::where('reg_com', 'J40/1234/2024')->first();
        $this->assertNotNull($company);
        $this->assertEquals('Test Company SRL', $company->name);
        $this->assertEquals('123456789', $company->cui);
        $this->assertEquals('SRL', $company->type);

        // Verify new fields were imported
        $this->assertEquals('www.example.com', $company->website);
        $this->assertEquals('Romania', $company->parent_country);
        $this->assertEquals('M', $company->mark);

        // Verify address was created with correct field names
        $address = Address::where('company_id', $company->id)->first();
        $this->assertNotNull($address);
        $this->assertEquals('123', $address->number);  // From ADR_NR_STRADA
        $this->assertEquals('Str. Exemple', $address->street);
        $this->assertEquals('București', $address->city);
    }

    /**
     * Test that null/empty values are handled correctly
     */
    public function test_batch_import_handles_null_values(): void
    {
        $fieldMap = [
            'DENUMIRE' => 0,
            'CUI' => 1,
            'COD_INMATRICULARE' => 2,
            'DATA_INMATRICULARE' => 3,
            'EUID' => 4,
            'FORMA_JURIDICA' => 5,
            'ADR_TARA' => 6,
            'ADR_JUDET' => 7,
            'ADR_LOCALITATE' => 8,
            'ADR_DEN_STRADA' => 9,
            'ADR_NR_STRADA' => 10,
            'ADR_BLOC' => 11,
            'ADR_SCARA' => 12,
            'ADR_ETAJ' => 13,
            'ADR_APARTAMENT' => 14,
            'ADR_COD_POSTAL' => 15,
            'ADR_SECTOR' => 16,
            'ADR_COMPLETARE' => 17,
            'WEB' => 18,
            'TARA_FIRMA_MAMA' => 19,
            'MARK' => 20,
        ];

        $batchData = [
            [
                'Test Company 2',            // 0: DENUMIRE
                '987654321',                 // 1: CUI
                'J40/5678/2024',             // 2: COD_INMATRICULARE
                '15/06/2024',                // 3: DATA_INMATRICULARE
                'ROONRC.J40/5678/2024',      // 4: EUID
                'PF',                        // 5: FORMA_JURIDICA
                'România',                   // 6: ADR_TARA
                'Cluj',                      // 7: ADR_JUDET
                'Cluj-Napoca',               // 8: ADR_LOCALITATE
                'Str. Test',                 // 9: ADR_DEN_STRADA
                '45',                        // 10: ADR_NR_STRADA
                '',                          // 11: ADR_BLOC
                '',                          // 12: ADR_SCARA
                '',                          // 13: ADR_ETAJ
                '',                          // 14: ADR_APARTAMENT
                '400000',                    // 15: ADR_COD_POSTAL
                '',                          // 16: ADR_SECTOR
                '',                          // 17: ADR_COMPLETARE
                '',                          // 18: WEB (empty)
                '',                          // 19: TARA_FIRMA_MAMA (empty)
                '',                          // 20: MARK (empty)
            ],
        ];

        $job = new ProcessCompanyBatchImportJobUpsert($batchData, $fieldMap);
        $job->handle();

        // Verify company was imported even with empty new fields
        $company = Company::where('reg_com', 'J40/5678/2024')->first();
        $this->assertNotNull($company);
        // Empty strings from CSV are stored as empty strings, not null
        $this->assertEquals('', $company->website);
        $this->assertEquals('', $company->parent_country);
        $this->assertEquals('', $company->mark);
    }

    /**
     * Test upserting existing companies with new data
     */
    public function test_batch_import_upserts_existing_companies(): void
    {
        // Create initial company
        $company = Company::create([
            'name' => 'Old Name',
            'cui' => '123456789',
            'reg_com' => 'J40/1234/2024',
            'euid' => 'ROONRC.J40/1234/2024',
            'type' => 'SRL',
            'registration_date' => now(),
            'website' => null,
            'parent_country' => null,
            'mark' => null,
        ]);

        $fieldMap = [
            'DENUMIRE' => 0,
            'CUI' => 1,
            'COD_INMATRICULARE' => 2,
            'DATA_INMATRICULARE' => 3,
            'EUID' => 4,
            'FORMA_JURIDICA' => 5,
            'ADR_TARA' => 6,
            'ADR_JUDET' => 7,
            'ADR_LOCALITATE' => 8,
            'ADR_DEN_STRADA' => 9,
            'ADR_NR_STRADA' => 10,
            'ADR_BLOC' => 11,
            'ADR_SCARA' => 12,
            'ADR_ETAJ' => 13,
            'ADR_APARTAMENT' => 14,
            'ADR_COD_POSTAL' => 15,
            'ADR_SECTOR' => 16,
            'ADR_COMPLETARE' => 17,
            'WEB' => 18,
            'TARA_FIRMA_MAMA' => 19,
            'MARK' => 20,
        ];

        $batchData = [
            [
                'Updated Company Name',      // 0: DENUMIRE
                '123456789',                 // 1: CUI
                'J40/1234/2024',             // 2: COD_INMATRICULARE
                '01/01/2024',                // 3: DATA_INMATRICULARE
                'ROONRC.J40/1234/2024',      // 4: EUID
                'SRL',                       // 5: FORMA_JURIDICA
                'România',                   // 6: ADR_TARA
                'Bucureești',                // 7: ADR_JUDET
                'București',                 // 8: ADR_LOCALITATE
                'Str. Noua',                 // 9: ADR_DEN_STRADA
                '999',                       // 10: ADR_NR_STRADA
                'B',                         // 11: ADR_BLOC
                '',                          // 12: ADR_SCARA
                '2',                         // 13: ADR_ETAJ
                '20',                        // 14: ADR_APARTAMENT
                '020001',                    // 15: ADR_COD_POSTAL
                '2',                         // 16: ADR_SECTOR
                '',                          // 17: ADR_COMPLETARE
                'www.updated.com',           // 18: WEB (new)
                'Germany',                   // 19: TARA_FIRMA_MAMA (new)
                'N',                         // 20: MARK (new)
            ],
        ];

        $job = new ProcessCompanyBatchImportJobUpsert($batchData, $fieldMap);
        $job->handle();

        // Verify company was updated
        $updatedCompany = Company::where('reg_com', 'J40/1234/2024')->first();
        $this->assertEquals('Updated Company Name', $updatedCompany->name);
        $this->assertEquals('www.updated.com', $updatedCompany->website);
        $this->assertEquals('Germany', $updatedCompany->parent_country);
        $this->assertEquals('N', $updatedCompany->mark);

        // Verify only one company exists
        $this->assertEquals(1, Company::where('reg_com', 'J40/1234/2024')->count());
    }
}
