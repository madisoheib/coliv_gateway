<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupervisorProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $containers = implode(',', array_keys(config('supervisor.containers', [])));

        return [
            'container' => "required|in:{$containers}",
            'program_name' => ['required', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'command' => 'required|max:500',
            'directory' => ['nullable', 'max:255', 'regex:/^\/[a-zA-Z0-9\/_.-]+$/'],
            'user' => ['nullable', 'max:64', 'regex:/^[a-zA-Z0-9_-]+$/'],
            'numprocs' => 'integer|min:1|max:20',
            'autostart' => 'boolean',
            'autorestart' => 'boolean',
            'redirect_stderr' => 'boolean',
            'stopasgroup' => 'boolean',
            'killasgroup' => 'boolean',
            'stdout_logfile' => ['nullable', 'max:255', 'regex:/^\/[a-zA-Z0-9\/_.-]+$/'],
            'stdout_logfile_maxbytes' => ['nullable', 'max:20', 'regex:/^[0-9]+[KMG]?B$/'],
            'stdout_logfile_backups' => 'nullable|integer|min:0|max:50',
            'stopwaitsecs' => 'nullable|integer|min:0|max:600',
        ];
    }

    public function messages(): array
    {
        return [
            'program_name.regex' => 'Program name may only contain letters, numbers, hyphens, and underscores.',
            'directory.regex' => 'Directory must be an absolute path.',
        ];
    }
}
