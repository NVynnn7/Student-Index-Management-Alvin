@csrf

@php
    $isEditing = isset($student) && $student->exists;
    $studentIdValue = old('student_id', $student->student_id ?? ($nextStudentId ?? ''));
@endphp

<div class="form-grid student-record-fields">
    <div class="student-field student-field-id">
        <label for="student_id">Student ID</label>
        <input id="student_id" name="student_id" value="{{ $studentIdValue }}" readonly>
        <p class="field-hint">
            {{ $isEditing ? 'ID stays locked while editing.' : 'Generated automatically when you save.' }}
        </p>
    </div>

    <div class="student-field student-field-name">
        <label for="name">Name</label>
        <input id="name" name="name" value="{{ old('name', $student->name ?? '') }}" placeholder="Full student name" required>
    </div>

    <div class="student-field student-field-email">
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $student->email ?? '') }}" placeholder="student@example.com" required>
    </div>

    <div class="student-field student-field-gpa">
        <label for="gpa">GPA</label>
        <input id="gpa" type="number" step="0.01" min="0" max="4" name="gpa" value="{{ old('gpa', $student->gpa ?? '') }}" placeholder="0.00 - 4.00" required>
    </div>

    <div class="student-field student-field-major">
        <label for="major">Major</label>
        <input id="major" name="major" value="{{ old('major', $student->major ?? '') }}" placeholder="Information Systems">
    </div>
</div>

<div class="actions student-form-actions">
    <a class="button secondary" href="{{ route('students.index') }}">Cancel</a>
    <button class="button" type="submit">{{ $isEditing ? 'Save Changes' : 'Create Student' }}</button>
</div>
