<?php

namespace App\Enums;

enum DocumentType: string
{
    case TranscriptOfRecords = 'transcript_of_records';
    case CertificateOfEnrolment = 'certificate_of_enrolment';
    case CertificateOfGraduation = 'certificate_of_graduation';
    case CertificateOfGoodMoral = 'certificate_of_good_moral';
    case CertifiedTrueCopyOfGrades = 'certified_true_copy_of_grades';

    /**
     * Get the label shown in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::TranscriptOfRecords => 'Transcript of Records',
            self::CertificateOfEnrolment => 'Certificate of Enrolment',
            self::CertificateOfGraduation => 'Certificate of Graduation',
            self::CertificateOfGoodMoral => 'Certificate of Good Moral Character',
            self::CertifiedTrueCopyOfGrades => 'Certified True Copy of Grades',
        };
    }

    /**
     * Get the fee in pesos for one copy of the document.
     */
    public function fee(): int
    {
        return match ($this) {
            self::TranscriptOfRecords => 150,
            self::CertificateOfEnrolment => 50,
            self::CertificateOfGraduation => 100,
            self::CertificateOfGoodMoral => 50,
            self::CertifiedTrueCopyOfGrades => 100,
        };
    }

    /**
     * Get the per-copy fee formatted for display, e.g. "₱150.00".
     */
    public function formattedFee(): string
    {
        return '₱'.number_format($this->fee(), 2);
    }
}
