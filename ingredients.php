<?php

require_once 'db_connect.php';
require_once 'auth_function.php';

checkAdminLogin();

$confData = getConfigData($pdo);

include('header.php');
?>

<h1 class="mt-4">Ingredient Management</h1>
<ol class="breadcrumb mb-4">
    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
    <li class="breadcrumb-item active">Ingredient Management</li>
</ol>

<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col col-md-6"><b>Ingredient List</b></div>
            <div class="col col-md-6">
                <a href="add_ingredients.php" class="btn btn-success btn-sm float-end">Add</a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <table id="ingredientTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Category</th>
                    <th>Ingredient Name</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<?php include('footer.php'); ?>

<script>
$(document).ready(function() {
    $('#ingredientTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "ingredients_ajax.php",
            "type": "GET"
        },
        "columns": [
            { "data": "ingredient_id" },
            { "data": "category_name" },
            { "data": "ingredient_name" },
            { "data": "ingredient_quantity" },
            { "data": "ingredient_unit" },
            {
                "data": null,
                "render": function(data, type, row) {
                    return `
                        <div class="text-center">
                            <a href="edit_ingredient.php?id=${row.ingredient_id}" class="btn btn-warning btn-sm">Edit</a>
                            <button class="btn btn-danger btn-sm delete-btn" data-id="${row.ingredient_id}">Delete</button>
                        </div>`;
                }
            }
        ]
    });

    // Handle Delete Button Click
    $(document).on('click', '.delete-btn', function() {
        let ingredientId = $(this).data('id');
        if (confirm("Are you sure you want to delete this ingredient?")) {
            $.ajax({
                url: 'delete_ingredient.php',
                type: 'POST',
                data: { id: ingredientId },
                success: function(response) {
                    alert(response);
                    $('#ingredientTable').DataTable().ajax.reload();
                }
            });
        }
    });
});
</script>
