<?php

namespace App\Http\Requests;

use App\Models\ProjectStage;
use App\Support\StudentStageProgress;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && in_array($user->role, ['project_student', 'research_student', 'normal_student', 'student'], true);
    }

    public function rules(): array
    {
        $stage = ProjectStage::find((int) $this->input('stage_id'));
        $isCompleteSystem = $stage && StudentStageProgress::isCompleteSystemStage($stage->stage_name);
        $isPresentation = $stage && StudentStageProgress::isPresentationStage($stage->stage_name);
        $interfaceKeys = array_keys(StudentStageProgress::systemInterfaceOptions());

        $documentRules = $isCompleteSystem
            ? ['required', 'file', 'mimes:zip,rar,7z,tar,gz,tgz', 'max:204800']
            : ($isPresentation
                ? ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx', 'max:10240']
                : ['required', 'file', 'mimes:pdf,doc,docx,zip', 'max:10240']);

        return [
            'stage_id' => ['required', 'exists:project_stages,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => $isCompleteSystem
                ? ['required', 'string', 'min:30', 'max:2000']
                : ['nullable', 'string', 'max:2000'],
            'interface_screenshots' => $isCompleteSystem
                ? ['required', 'array', 'min:1']
                : ['nullable', 'array'],
            'interface_screenshots.*.interface' => $isCompleteSystem
                ? ['required', 'string', Rule::in($interfaceKeys)]
                : ['nullable', 'string'],
            'interface_screenshots.*.custom_label' => ['nullable', 'string', 'max:120'],
            'interface_screenshots.*.image' => $isCompleteSystem
                ? ['required', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120']
                : ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'document' => $documentRules,
            'demo_url' => ['nullable', 'string', 'url', 'max:500'],
            'video_url' => ['nullable', 'string', 'url', 'max:500'],
            'documentation' => ['nullable', 'file', 'mimes:pdf,doc,docx,md,txt', 'max:20480'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Please enter a title for this submission.',
            'description.required' => 'A short system description is required for Complete System submissions.',
            'description.min' => 'The system description should be at least 30 characters.',
            'document.required' => 'Please attach the required document or archive.',
            'interface_screenshots.required' => 'Upload a home page interface screenshot.',
            'interface_screenshots.*.image.required' => 'Please choose a home page screenshot image.',
        ];
    }
}
