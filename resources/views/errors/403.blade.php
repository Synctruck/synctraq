@extends('errors::minimal')

@section('title', __('Forbidden'))
@section('code')
<h1>403</h1>
@endsection
@section('message')
<h1>You do not have permission to access this section, please contact the administrator</h1>

<a href="/" class="btn btn-success"> Back to login</a>
@endsection
