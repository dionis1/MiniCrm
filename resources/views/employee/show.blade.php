@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
          <div class="card">
            <div class="card-body">
              <p class="card-text">{{ __("Name")}} : {{ $employee->first_name }} {{ $employee->last_name }}</p>
              <p class="card-text">{{ __("Email")}} : {{ $employee->email }}</p>
              <p class="card-text">{{ __("Phone")}} :{{ $employee->phone }}</p>
              <p class="card-text">{{ __("Companie Name")}} : {{ $employee->companie->name }}</p>
            </div>
          </div>
        </div>
    </div>
</div>
@endsection