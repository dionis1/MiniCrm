@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    {{ __("Employee")}}
                    <a href="{{ route('employee.create') }}" type="button" class="btn btn-success">{{ __("Add new Employee")}}</a>
                </div>
                <div class="card-body">
                   <table class="table">
                      <thead>
                        <tr>
                          <th scope="col">#</th>
                          <th scope="col">{{ __("Name")}}</th>
                          <th scope="col">{{ __("Email")}}</th>
                          <th scope="col">{{ __("Action")}}</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($employees as $employee)
                            <tr>
                              <th scope="row">{{ $employee->id }}</th>
                              <td>{{ $employee->first_name }} {{ $employee->last_name }}</td>
                              <td>{{ $employee->email }}</td>
                              <td>
                                <a type="button" class="btn btn-info" href="{{ route('employee.edit', ['id' => $employee->id]) }}">{{ __("Edit")}}</a>
                                <a type="button" class="btn btn-warning" href="{{ route('employee.show', ['id' => $employee->id]) }}">{{ __("Show")}}</a>
                                <form action="{{ route('employee.destroy', ['id' => $employee->id]) }}" method="POST">
                                     @csrf
                                    {{ method_field('DELETE') }}
                                    <button class="btn btn-danger" type="submit" onclick="return confirm('Are you sure you want to delete this item?');">{{ __("Delete")}}</button>
                                </form>
                              </td>
                            </tr>
                        @endforeach
                      </tbody>
                    </table>
                    {{ $employees->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection