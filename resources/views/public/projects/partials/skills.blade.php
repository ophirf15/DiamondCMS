@php
    $skills = is_array($project->skills ?? null) ? $project->skills : [];
    $skillsStyle = $skillsStyle ?? 'chips';
@endphp
@if ($skills !== [] && $skillsStyle !== 'hidden')
    @if ($skillsStyle === 'chips')
        <div class="dc-project-skills dc-project-skills--chips" aria-label="Skills">
            @foreach ($skills as $skill)
                <span class="dc-project-skill-chip">{{ $skill }}</span>
            @endforeach
        </div>
    @else
        <p class="dc-project-skills dc-project-skills--inline"><strong>Skills:</strong> {{ implode(', ', $skills) }}</p>
    @endif
@endif
