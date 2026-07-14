<?php

namespace App\Http\Requests;

use App\Support\PrmsAccountIdentifierFormat;
use App\Support\PrmsSms;
use App\Support\StudentGenderNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminUserRequest extends FormRequest
{
    public const FORM_STUDENT_ROLE = 'student';

    /** @var list<string> */
    public const STUDENT_ROLES = ['project_student', 'research_student', 'normal_student', 'student'];

    /** @var list<string> */
    public const STAFF_FORM_ROLES = ['admin', 'hod', 'coordinator', 'supervisor'];

    /** @var list<string> */
    public const FORM_ROLES = ['admin', 'hod', 'coordinator', 'supervisor', self::FORM_STUDENT_ROLE];

    /** @var list<string> Roles allowed in CSV bulk import (students and staff only — not admin). */
    public const BULK_IMPORT_ROLES = ['hod', 'coordinator', 'supervisor', self::FORM_STUDENT_ROLE];

    public const MAX_YEAR_OF_STUDY = 4;

    public static function isStudentFormRole(string $role): bool
    {
        return $role === self::FORM_STUDENT_ROLE || in_array($role, self::STUDENT_ROLES, true);
    }

    public static function isStaffFormRole(string $role): bool
    {
        return in_array($role, self::STAFF_FORM_ROLES, true);
    }

    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    protected function prepareForValidation(): void
    {
        $merge = [
            'form_context' => $this->input('form_context', 'create'),
        ];

        if ($this->input('year_of_study') === '') {
            $merge['year_of_study'] = null;
        }

        if ($this->filled('login_id')) {
            $merge['login_id'] = PrmsAccountIdentifierFormat::normalize($this->input('login_id'));
        }

        if ($this->filled('gender')) {
            $normalizedGender = StudentGenderNormalizer::normalize($this->input('gender'));
            if ($normalizedGender !== null) {
                $merge['gender'] = $normalizedGender;
            }
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        $role = (string) $this->input('role');
        $isStudent = self::isStudentFormRole($role);
        $isStaff = self::isStaffFormRole($role);

        return [
            'form_context' => ['required', 'in:create'],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:30'],
            'login_id' => ['required', 'string', 'max:80', 'unique:users,login_id'],
            'role' => ['required', Rule::in(self::FORM_ROLES)],
            'department' => [
                Rule::requiredIf($isStudent || in_array($role, ['hod', 'coordinator', 'supervisor'], true)),
                'nullable',
                'string',
                'max:120',
            ],
            'programme' => [
                Rule::requiredIf($isStudent),
                'nullable',
                'string',
                'max:120',
            ],
            'year_of_study' => [
                Rule::requiredIf($isStudent),
                'nullable',
                'integer',
                'between:1,'.self::MAX_YEAR_OF_STUDY,
            ],
            'gender' => [
                Rule::requiredIf($isStudent || $isStaff),
                'nullable',
                Rule::in(['male', 'female']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter the full name.',
            'email.required' => 'Email is required.',
            'email.unique' => 'This email is already in use.',
            'phone_number.required' => PrmsSms::requiredPhoneMessage(),
            'login_id.required' => 'Registration number or staff ID is required.',
            'login_id.unique' => 'This registration number or staff ID is already taken.',
            'role.required' => 'Please select a system role.',
            'department.required' => 'Department is required for this role.',
            'programme.required' => 'Programme is required for student accounts.',
            'year_of_study.required' => 'Year of study is required for student accounts.',
            'gender.required' => 'Gender is required for student and staff accounts.',
            'gender.in' => 'Gender must be male or female.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            PrmsSms::validatePhoneField($validator, $this);

            $role = (string) $this->input('role');
            $loginId = trim((string) $this->input('login_id'));

            if ($loginId === '') {
                return;
            }

            if (self::isStudentFormRole($role)) {
                if (! PrmsAccountIdentifierFormat::hasValidRegistrationNumberFormat($loginId)) {
                    $validator->errors()->add(
                        'login_id',
                        PrmsAccountIdentifierFormat::STUDENT_HELP
                    );

                    return;
                }

                $programmeCode = PrmsAccountIdentifierFormat::parsedProgrammeCode($loginId);
                if (! PrmsAccountIdentifierFormat::programmeCodeIsRegistered($programmeCode)) {
                    $validator->errors()->add(
                        'login_id',
                        'Programme code «'.$programmeCode.'» in this registration number was not found in the programme register.'
                    );

                    return;
                }

                if (! PrmsAccountIdentifierFormat::registrationMatchesProgramme($loginId, $this->input('programme'))) {
                    $validator->errors()->add(
                        'login_id',
                        'The programme code in the registration number must match the selected programme (e.g. '.PrmsAccountIdentifierFormat::STUDENT_EXAMPLE.').'
                    );
                }

                $selectedDepartmentCode = PrmsAccountIdentifierFormat::resolveDepartmentCode($this->input('department'));
                $selectedProgrammeCode = PrmsAccountIdentifierFormat::resolveProgrammeCode($this->input('programme'));

                if ($selectedDepartmentCode === null) {
                    $validator->errors()->add('department', 'Please select a valid department.');

                    return;
                }

                $programme = $selectedProgrammeCode !== null
                    ? \App\Models\Program::query()->with('department')->where('programme_code', $selectedProgrammeCode)->first()
                    : null;

                if ($programme?->department && $programme->department->department_code !== $selectedDepartmentCode) {
                    $validator->errors()->add(
                        'programme',
                        'The selected programme must belong to the selected department.'
                    );
                }

                return;
            }

            if (! self::isStaffFormRole($role)) {
                return;
            }

            if ($role === 'admin') {
                if (! PrmsAccountIdentifierFormat::isValidAdminIdentifier($loginId)) {
                    $validator->errors()->add(
                        'login_id',
                        PrmsAccountIdentifierFormat::STAFF_HELP.' Legacy MoCU/ADMIN/NUMBER identifiers are also accepted for administrators.'
                    );
                }

                return;
            }

            if (! PrmsAccountIdentifierFormat::hasValidStaffIdFormat($loginId)) {
                $validator->errors()->add(
                    'login_id',
                    PrmsAccountIdentifierFormat::STAFF_HELP
                );

                return;
            }

            $departmentCode = PrmsAccountIdentifierFormat::parsedDepartmentCode($loginId);
            if (! PrmsAccountIdentifierFormat::departmentCodeIsRegistered($departmentCode)) {
                $validator->errors()->add(
                    'login_id',
                    'Department code «'.$departmentCode.'» in this staff ID was not found in the department register.'
                );

                return;
            }

            if (! PrmsAccountIdentifierFormat::staffIdMatchesDepartment($loginId, $this->input('department'))) {
                $validator->errors()->add(
                    'login_id',
                    'The department code in the staff ID must match the selected department (e.g. '.PrmsAccountIdentifierFormat::STAFF_EXAMPLE.').'
                );
            }
        });
    }

    protected function getRedirectUrl(): string
    {
        return route('admin.users.index');
    }
}
