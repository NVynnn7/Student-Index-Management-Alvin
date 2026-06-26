@extends('layouts.app')

@section('body_class', 'dashboard-page')
@section('shell_class', 'dashboard-shell')

@section('content')
    @php
        $dashboardProps = [
            'csrf' => csrf_token(),
            'students' => $students,
            'stats' => $stats,
            'analysis' => $analysis,
            'complexities' => $complexities,
            'user' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
            ],
            'filters' => [
                'search' => $search,
                'searchType' => $searchType,
                'sortType' => $sortType,
            ],
            'routes' => [
                'index' => route('students.index'),
                'create' => route('students.create'),
                'export' => route('students.export'),
                'upload' => route('students.upload'),
                'edit' => route('students.edit', '__ID__'),
                'destroy' => route('students.destroy', '__ID__'),
                'logout' => route('logout'),
            ],
            'flash' => [
                'success' => session('success'),
                'error' => $errors->any() ? $errors->first() : null,
                'type' => session('alert_type'),
            ],
        ];
    @endphp

    <div id="student-dashboard"></div>
    <script id="student-dashboard-props" type="application/json">
        {!! json_encode($dashboardProps, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    </script>
@endsection
