<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

include('header.php');
?>

<h1 class="mt-4">User Management</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">User Management</li>
</ol>

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col col-md-6"><b>User List</b></div>
            <div class="col col-md-6">
                <a href="add_user.php" class="btn btn-success btn-sm float-end">Add</a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <table id="userTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<?php
include('footer.php');
?>

<script>
$(document).ready(function() {
    $('#userTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "user_ajax.php",
            "type": "GET",
            "dataSrc": function(json) {
                console.log('Data received from server:', json);
                return json.data;
            }
        },
        "columns": [
            { "data": "user_id" },
            { "data": "user_name" },
            { "data": "user_email" },
            { 
                "data": "user_type",
                "render": function(data, type, row) {
                    console.log('Rendering user type:', data, 'for user:', row.user_name);
                    if (data === 'Admin') {
                        return '<span class="badge bg-primary">Admin</span>';
                    } else if (data === 'Cashier') {
                        return '<span class="badge bg-info">Cashier</span>';
                    } else if (data === 'Stockman') {
                        return '<span class="badge bg-warning">Stockman</span>';
                    }
                    return data || '';
                }
            },
            { 
                "data": null,
                "render": function(data, type, row){
                    if(row.user_status === 'Active'){
                        return `<span class="badge bg-success">Active</span>`;
                    } else {
                        return `<span class="badge bg-danger">Inactive</span>`;
                    }
                }
            },
            {
                "data": null,
                "render": function(data, type, row){
                    return `
                    <div class="text-center">
                        <a href="edit_user.php?id=${row.user_id}" class="btn btn-warning btn-sm">Edit</a>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="${row.user_id}">Delete</button>
                    </div>`;
                }
            }
        ]
    });

    // Handle Delete Button Click
    $(document).on('click', '.delete-btn', function() {
        let userId = $(this).data('id');
        if (confirm("Are you sure you want to delete this user?")) {
            $.ajax({
                url: 'delete_user.php',
                type: 'POST',
                data: { id: userId },
                success: function(response) {
                    alert(response);
                    $('#userTable').DataTable().ajax.reload();
                }
            });
        }
    });
});
</script>
