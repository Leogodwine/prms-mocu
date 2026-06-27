<?php

namespace Tests\Unit;

use App\Support\SubmissionFileAccess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SubmissionFileAccessTest extends TestCase
{
    #[Test]
    public function document_icon_meta_detects_pdf_and_word(): void
    {
        $pdf = SubmissionFileAccess::documentIconMeta('application/pdf', 'chapter-one.pdf');
        $word = SubmissionFileAccess::documentIconMeta(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'chapter-one.docx'
        );

        $this->assertSame('far fa-file-pdf', $pdf['icon']);
        $this->assertSame('far fa-file-word', $word['icon']);
    }
}
