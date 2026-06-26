@extends('layouts.app')

@section('body_class', 'student-form-page')
@section('shell_class', 'student-form-shell')

@section('content')
    <div class="student-form-layout">
        <section class="student-form-hero">
            <div class="student-form-hero-bar">
                <a class="student-form-back" href="{{ route('students.index') }}">&larr; Dashboard</a>
                <span>New record</span>
            </div>

            <div>
                <x-simdex-logo class="hero-logo" />
                <span class="auth-eyebrow">SIMDEX Enrollment</span>
                <h1>Add Student</h1>
                <p>Create a clean academic profile that is instantly ready for search, analysis, import, and export.</p>
            </div>

            <div class="student-form-notes">
                <span><b>01</b><i>Identity</i><small>Automatic student ID</small></span>
                <span><b>02</b><i>Academic</i><small>GPA and major validation</small></span>
                <span><b>03</b><i>Ready</i><small>Available across SIMDEX</small></span>
            </div>
        </section>

        <section class="student-form-panel">
            <div class="student-form-panel-bar">
                <span class="active">Student profile</span>
                <span>Academic details</span>
                <span>Save record</span>
            </div>

            <div class="student-form-heading">
                <x-simdex-logo />
                <div>
                    <span class="react-eyebrow">Student Details</span>
                    <h2>Create a student profile</h2>
                    <p>Complete the fields below. SIMDEX will generate the ID.</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="message error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('students.store') }}">
                @include('students._form')
            </form>
        </section>
    </div>
@endsection
