@extends('front.layouts.app')

@section('content')
<section class="section-5 pt-3 pb-3 mb-3 bg-white">
    <div class="container">
        <div class="light-font">
            <ol class="breadcrumb primary-color mb-0">
                <li class="breadcrumb-item"><a class="white-text" href="#">My Account</a></li>
                <li class="breadcrumb-item">Settings</li>
            </ol>
        </div>
    </div>
</section>

<section class="section-11">
    <div class="container mt-5">
        <div class="row">
            <!-- Sidebar Column -->
            <div class="col-md-3">
                @include('front.account.common.sidebar') <!-- Include the sidebar here -->
            </div>
            <!-- Main Content Column -->
            <div class="col-md-9">
                <!-- Create Product Section -->
                <section class="content-header">
                    <div class="container-fluid my-2">
                        <div class="row mb-2">
                            <div class="col-sm-10">
                                <h1>Shipping Management</h1>
                            </div>
                            <div class="col-sm-2 text-right">
                                <a href="{{ route('books.index')}}" class="btn btn-primary">Back</a>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="content">
                @include('admin.message')
                <form action="" method="post" name="shippingForm" id="shippingForm">
                @csrf   
                     <div class="container-fluid">
                            <div class="row">
                                <div class="col-md-12">
                                    <!-- Main Product Creation Form -->
                                    <div class="card mb-3">
                                        <div class="card-body">								
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="mb-3">
                                                        <select name="county" id="county" class="form-control">
                                                        <option value="">Select a County</option>
                                                        @if ($counties->isNotEmpty())
                                                            @foreach ($counties as $county)
                                                            <option {{ ($shippingCharge->county_id == $county->id) ? 'selected' : '' }} value="{{ $county->id }}">{{ $county->name }}</option>
                                                            @endforeach
                                                            <option {{ ($shippingCharge->county_id == 'rest_of_world') ? 'selected' : '' }} value="rest_of_world">Rest of the world</option>
                                                        @endif
                                                        </select>
                                                        <p class="error"></p>
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                   <div class="mb-3">
                                                    <input value="{{ $shippingCharge->amount}}" type="text" name="amount" id="amount" class="form-control" placeholder="Amount">
                                                    <p></p>
                                                   </div>
                                                </div>
                                                <div class="col-md-4">
                                                     <div class="mb-3">
                                                <button type="submit" class="btn btn-primary">Update</button>
                                                     </div>
                                                </div>                  
                                             </div>
                                     </div>
                    </form>
                    
                </section>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
        

        <script>
$("#shippingForm").submit(function(event){
    event.preventDefault();
    var element = $(this);
    $("button[type=submit]").prop('disabled', true);

    $.ajax({
        url: '{{ route("shipping.update",$shippingCharge->id)}}',
        type: 'put',
        data: element.serializeArray(),
        dataType: 'json',
        success: function(response){
            $("button[type=submit]").prop('disabled', false);
            if(response["status"] == true){
                window.location.href="{{ route('shipping.create')}}";
                
            } else {
                var errors = response['errors'];
                if(errors['county']) {
                    $("#county").addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors['county']);
                } else {
                    $("#county").removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html("");
                }
                if(errors['amount']) {
                    $("#amount").addClass('is-invalid').siblings('p').addClass('invalid-feedback').html(errors['amount']);
                } else {
                    $("#amount").removeClass('is-invalid').siblings('p').removeClass('invalid-feedback').html("");
                }
            }
        }, error: function(jqXHR, exception){
            console.log("Something went wrong");
        }
    })
  });

 // Function to convert the name to a slug
 function generateSlug(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')           // Replace spaces with -
            .replace(/[^\w\-]+/g, '')       // Remove all non-word chars
            .replace(/\-\-+/g, '-')         // Replace multiple - with single -
            .replace(/^-+/, '')             // Trim - from start of text
            .replace(/-+$/, '');            // Trim - from end of text
    }

    // Automatically update the slug field when the name field is changed
    $('#title').on('keyup', function() {
        var name = $(this).val();
        var slug = generateSlug(name);
        $('#slug').val(slug);  // Assign the generated slug to the slug input field
    });


            $('.related-book').select2({
            ajax: {
                url: '{{ route("books.getBooks") }}',
                dataType: 'json',
                tags: true,
                multiple: true,
                minimumInputLength: 3,
                processResults: function (data) {
                    return {
                        results: data.tags
                    };
                }
            }
        });


$("#title").change(function(){
        element = $(this);
        $("button[type=submit]").prop('disabled',true);
        $.ajax({
            url: '{{ route("getSlug") }}',
            type: 'get',
            data: {title: element.val()},
            dataType: 'json',
            success: function(response){
                $("button[type=submit]").prop('disabled', false);
                if(response["status"] == true) {
                    $("#slug").val(response["slug"]);
                }
            }
        });
    });

                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });


                        $("#bookForm").submit(function(event){
                        event.preventDefault();
                        var formArray = $(this).serializeArray();
                        $("button[type='submit']").prop('disabled',true);

                        $.ajax({
                            url: '{{ route("books.store")}}',
                            type: 'post',
                            data: formArray,
                            dataType: 'json',
                            success: function(response) {
                                $("button[type='submit']").prop('disabled',false);

                                if (response['status'] == true) {
                                    $(".error").removeClass('invalid-feedback').html('');
                                    $("input[type= 'text'], select, input[type= 'number']").removeClass('is-invalid');

                                    window.location.href ="{{ route('books.index')}}";
                                } else {
                                    var errors = response['errors'];

                                    $(".error").removeClass('invalid-feedback').html('');
                                    $("input[type= 'text'], select, input[type= 'number']").removeClass('is-invalid');

                                    $.each(errors, function(key,value){
                                        $('#' + key).addClass('is-invalid')
                                        .siblings('p')
                                        .addClass('invalid-feedback')
                                        .html(value);

                                    });
                                }
                            },
                            error: function(){
                                console.log("Something Went Wrong");
                            }
                        });
                    });


                    $("#category").change(function(){
                var category_id = $(this).val();
                console.log('Selected Category ID:', category_id); // Log the selected ID

                $.ajax({
                    url: '{{ route("book-subgenres.index") }}',
                    type: 'get',
                    data: { category_id: category_id },
                    dataType: 'json',
                    success: function(response) {
                        console.log('Sub-Genres Response:', response); // Log the server response
                        $("#sub_category").find("option").not(":first").remove();
                        $.each(response.subGenres, function(key, item) {
                            $("#sub_category").append(`<option value='${item.id}'>${item.name}</option>`);
                        });
                    },
                    error: function(xhr) {
                        console.log("Error:", xhr); // Log the error response
                    }
                });
        });

        Dropzone.autoDiscover = false;
const dropzone = $("#image").dropzone({
    url: "{{ route('temp-images.create') }}",
    maxFiles: 10,
    paramName: 'image',
    addRemoveLinks: true,
    acceptedFiles: "image/jpeg,image/png,image/gif",
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    success: function(file, response) {
        console.log('Image Path:', response.ImagePath); // Log the image path
        if (response.image_id) {
            var html = `
                <div class="col-md-3" id="image-row-${response.image_id}">
                    <div class="card">
                        <input type="hidden" name="image_array[]" value="${response.image_id}">
                        <img src="${response.ImagePath}" class="card-img-top" alt="Uploaded Image">
                        <div class="card-body">
                            <a href="javascript:void(0)" onclick="deleteImage(${response.image_id})" class="btn btn-danger">Delete</a>
                        </div>
                    </div>
                </div>
            `;
            $("#product-gallery").append(html);
        } else {
            console.log("No image_id received from the server.");
            alert("File uploaded but no image ID received.");
        }
    },
    complete: function(file) {
        // Ensure that the file is removed after being successfully uploaded
        if (file.status === "success") {
            this.removeFile(file);
        }
    },
    error: function(file, errorMessage) {
        console.log("File upload error:", errorMessage);
        alert("An error occurred while uploading the file.");
    }
});

// Function to handle image deletion
function deleteImage(id) {
    $("#image-row-" + id).remove();  // Include the hyphen to match the id
}

        </script>
        
@endsection