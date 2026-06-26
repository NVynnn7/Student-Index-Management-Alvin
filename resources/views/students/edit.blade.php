@extends('layouts.app')

@section('body_class', 'student-form-page')
@section('shell_class', 'student-form-shell')

@section('content')
    <div class="student-form-layout">
        <section class="student-form-hero">
            <div class="student-form-hero-bar">
                <a class="student-form-back" href="{{ route('students.index') }}">&larr; Dashboard</a>
                <span>{{ $student->student_id }}</span>
            </div>

            <div>
                <x-simdex-logo class="hero-logo" />
                <span class="auth-eyebrow">SIMDEX Profile Editor</span>
                <h1>Edit Student</h1>
                <p>Keep identity and academic information accurate with a clearer, safer editing experience.</p>
            </div>

            <div class="student-form-notes">
                <span><b>01</b><i>Protected</i><small>Student ID remains locked</small></span>
                <span><b>02</b><i>Validated</i><small>Changes checked before save</small></span>
                <span><b>03</b><i>Synced</i><small>Dashboard updates immediately</small></span>
            </div>
        </section>

        <section class="student-form-panel">
            <div class="student-form-panel-bar">
                <span class="active">Student profile</span>
                <span>Review changes</span>
                <span>Update record</span>
            </div>

            <div class="student-form-heading">
                <x-simdex-logo />
                <div>
                    <span class="react-eyebrow">Student Details</span>
                    <h2>Update {{ $student->name }}</h2>
                    <p>Review the profile below, then save your changes.</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="message error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('students.update', $student) }}">
                @method('PUT')
                @include('students._form')
            </form>
        </section>
    </div>
@endsection
