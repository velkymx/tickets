@extends('layouts.app')
@section('title')
Edit User
@stop
@section('content')

<h1><span class="pull-right"><a href="/user/add" class="btn btn-default btn-sm">Add User</a></span>Manage Users</h1>
<table class="table table-striped">
<thead>
    <tr>
        <th>Id</th>
        <th>Name</th>
        <th>Email</th>
        <th>Status</th>
        <th></th>
    </tr>
</thead>
<tbody>
@foreach ($users as $user)
    <tr>
        <td>{{ $user->id }}</td>
        <td>{{ $user->name }}</td>
        <td>{{ $user->email }}</td>
        <td><?php echo array(0=>'Inactive',1=>'Active')[ $user->active ]; ?></td>
        <td><a href='/user/edit/{{ $user->id }}' class="btn btn-sm btn-primary">Edit</a></td>
    </tr>
@endforeach
</tbody>
</table>
@stop
@section('javascript') 
@stop