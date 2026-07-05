<?php

namespace Tests\Unit;

use App\Support\AdminUserImportReader;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminUserImportReaderTest extends TestCase
{
    public function test_reads_csv_rows_with_normalized_headers(): void
    {
        $csv = "name,email,registration_number,role,department,programme,year_of_study,sex\n"
            ."Jane Doe,jane@example.com,MoCU/BBICT/101/20,student,CICT,BBICT,2,female\n";

        $file = UploadedFile::fake()->createWithContent('users.csv', $csv);

        $rows = AdminUserImportReader::read($file);

        $this->assertCount(1, $rows);
        $this->assertSame('Jane Doe', $rows[0]['name']);
        $this->assertSame('jane@example.com', $rows[0]['email']);
        $this->assertSame('MoCU/BBICT/101/20', $rows[0]['login_id']);
        $this->assertSame('female', $rows[0]['gender']);
    }

    public function test_reads_xml_user_records(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<users>
    <user>
        <name>John Smith</name>
        <email>john@example.com</email>
        <login_id>MoCU/ACC/202/20</login_id>
        <role>supervisor</role>
        <department>ACC</department>
    </user>
</users>
XML;

        $file = UploadedFile::fake()->createWithContent('users.xml', $xml);

        $rows = AdminUserImportReader::read($file);

        $this->assertCount(1, $rows);
        $this->assertSame('John Smith', $rows[0]['name']);
        $this->assertSame('supervisor', $rows[0]['role']);
    }

    public function test_rejects_invalid_xml(): void
    {
        $file = UploadedFile::fake()->createWithContent('users.xml', '<users><user></users>');

        $this->expectException(\RuntimeException::class);
        AdminUserImportReader::read($file);
    }
}
