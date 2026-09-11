<?php

namespace App\Domains\Uas\Pilots\Application\DTOs;

final readonly class PilotProfileData
{
    public function __construct(
        public ?int $userId,
        public ?string $employeeNumber,
        public string $firstName,
        public string $lastName,
        public ?string $preferredName,
        public ?string $email,
        public ?string $phone,
        public ?string $nationality,
        public ?string $dateOfBirth,
        public ?string $sacaaCertificateNumber,
        public string $rpcCategory,
        public ?array $ratings,
        public string $medicalStatus,
        public string $radiotelephonyQualification,
        public ?string $languageProficiency,
        public ?array $trainingHistory,
        public ?array $examinerRecords,
        public ?array $operatorAffiliations,
        public ?array $supportingDocumentReferences,
        public string $profileStatus,
        public ?string $notes,
    ) {}

    public static function fromArray(array $input): self
    {
        return new self(
            userId: $input['user_id'] ?? null,
            employeeNumber: $input['employee_number'] ?? null,
            firstName: $input['first_name'],
            lastName: $input['last_name'],
            preferredName: $input['preferred_name'] ?? null,
            email: $input['email'] ?? null,
            phone: $input['phone'] ?? null,
            nationality: $input['nationality'] ?? null,
            dateOfBirth: $input['date_of_birth'] ?? null,
            sacaaCertificateNumber: $input['sacaa_certificate_number'] ?? null,
            rpcCategory: $input['rpc_category'] ?? 'unknown',
            ratings: self::normaliseList($input['ratings'] ?? null),
            medicalStatus: $input['medical_status'] ?? 'unverified',
            radiotelephonyQualification: $input['radiotelephony_qualification'] ?? 'unverified',
            languageProficiency: $input['language_proficiency'] ?? null,
            trainingHistory: self::normaliseRecords($input['training_history'] ?? null),
            examinerRecords: self::normaliseRecords($input['examiner_records'] ?? null),
            operatorAffiliations: self::normaliseRecords($input['operator_affiliations'] ?? null),
            supportingDocumentReferences: self::normaliseRecords($input['supporting_document_references'] ?? null),
            profileStatus: $input['profile_status'] ?? 'draft',
            notes: $input['notes'] ?? null,
        );
    }

    public function toModelAttributes(): array
    {
        return [
            'user_id' => $this->userId,
            'employee_number' => $this->employeeNumber,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'preferred_name' => $this->preferredName,
            'email' => $this->email,
            'phone' => $this->phone,
            'nationality' => $this->nationality,
            'date_of_birth' => $this->dateOfBirth,
            'sacaa_certificate_number' => $this->sacaaCertificateNumber,
            'rpc_category' => $this->rpcCategory,
            'ratings' => $this->ratings,
            'medical_status' => $this->medicalStatus,
            'radiotelephony_qualification' => $this->radiotelephonyQualification,
            'language_proficiency' => $this->languageProficiency,
            'training_history' => $this->trainingHistory,
            'examiner_records' => $this->examinerRecords,
            'operator_affiliations' => $this->operatorAffiliations,
            'supporting_document_references' => $this->supportingDocumentReferences,
            'profile_status' => $this->profileStatus,
            'notes' => $this->notes,
        ];
    }

    private static function normaliseList(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        return array_values(array_filter($value, fn (mixed $item): bool => is_string($item) && trim($item) !== ''));
    }

    private static function normaliseRecords(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        return array_values($value);
    }
}
