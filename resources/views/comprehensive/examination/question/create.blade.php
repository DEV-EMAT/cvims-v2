@extends('layouts.app2')
@section('location')
    {{$title}}
@endsection
@section('content')
<div class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12 col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-10">
                                <h4 class="card-title"><b>Question Form</b></h4>
                                <p class="category">Create New Question</p>
                            </div>
                            <div class="col-lg-2">

                            </div>
                        </div>
                    </div>
                    <div class="card-content">
                        <form id="create_form">
                            @csrf
                            @method("POST")

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Subject *</label>
                                        <a class="pull-right" href="{{ route('exam-subject.index')}}"><i class="fa fa-plus"></i> Add new</a>
                                        <select class="form-control selectpicker" name="subject">
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="">Question</label>
                                        <textarea class="form-control" name="question" id="" rows="5"></textarea>
                                        <input type="hidden" name="examtype" id="examtype" value="1">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12 col-md-12">
                                    <ul class="nav nav-tabs bg-danger" id="myTab" role="tablist">
                                        <li class="nav-item active" id="multiplechoice">
                                            <a class="nav-link active" data-toggle="tab" href="#multiplechoicetab" role="tab" aria-controls="multiplechoicetab" aria-selected="false" aria-expanded="true"><i class="fa fa-plus-circle"></i> Multiple Choice</a>
                                        </li>
                                        <li class="nav-item" id="trueorfalse">
                                            <a class="nav-link" data-toggle="tab" href="#trueorfalsetab" role="tab" aria-controls="trueorfalsetab" aria-selected="false" aria-expanded="true"><i class="fa fa-plus-circle"></i> True or False</a>
                                        </li>
                                    </ul>
                                    <div class="tab-content" id="myTabContent">
                                        <div class="tab-pane fade active in" id="multiplechoicetab" role="tabpanel" aria-labelledby="multiplechoice">
                                            <div class="row">
                                                <div class="col-lg-12 col-md-12">
                                                    <div class="card">
                                                        <div class="card-content">
                                                            <div id="multiplechoice_form">
                                                                <table class="table">
                                                                    <thead>
                                                                        <th style="width: 50px;">Options</th>
                                                                        <th>Choices</th>
                                                                        <th>Answers</th>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>A</td>
                                                                            <td><input type="text" style="text-transform:uppercase" id="txtoptiona" class="form-control" name="choices[]" aria-describedby="helpId" placeholder=""></td>
                                                                            <td>
                                                                                <label class="btn btn-primary">
                                                                                    <input type="radio" name="answer" value="0" autocomplete="off ">
                                                                                </label>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>B</td>
                                                                            <td><input type="text" style="text-transform:uppercase" id="txtoptionb" class="form-control" name="choices[]" aria-describedby="helpId" placeholder=""></td>
                                                                            <td>
                                                                                <label class="btn btn-primary">
                                                                                    <input type="radio" name="answer" value="1" autocomplete="off ">
                                                                                </label>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>C</td>
                                                                            <td><input type="text" style="text-transform:uppercase" id="txtoptionc" class="form-control" name="choices[]" aria-describedby="helpId" placeholder=""></td>
                                                                            <td>
                                                                                <label class="btn btn-primary">
                                                                                    <input type="radio" name="answer" value="2" autocomplete="off ">
                                                                                </label>
                                                                            </td>
                                                                        </tr>
                                                                        <tr>
                                                                            <td>D</td>
                                                                            <td><input type="text" style="text-transform:uppercase" id="txtoptiond" class="form-control" name="choices[]" aria-describedby="helpId" placeholder=""></td>
                                                                            <td>
                                                                                <label class="btn btn-primary">
                                                                                    <input type="radio" name="answer" value="3" autocomplete="off ">
                                                                                </label>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="trueorfalsetab" role="tabpanel" aria-labelledby="trueorfalse">
                                            <div class="row">
                                                <div class="col-lg-12 col-md-12">
                                                    <div class="card">
                                                        <div class="card-content">
                                                            <div id="trueorfalse_form">
                                                                <table class="table ">
                                                                    <thead>
                                                                        <th style="width:50px">True</th>
                                                                        <th>False</th>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>
                                                                                <label class="btn btn-primary">
                                                                                    <input type="radio" name="answer" value="TRUE" id="" autocomplete="off ">
                                                                                </label>
                                                                            </td>
                                                                            <td>
                                                                                <label class="btn btn-primary">
                                                                                        <input type="radio" name="answer" value="FALSE" id="" autocomplete="off ">
                                                                                </label>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="text-center">
                                    <input type="submit" name="submit" class="btn btn-info btn-fill btn-wd" />
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            $.ajax({
                url:'{{ route('exam-subject.find-subject') }}',
                type:'GET',
                dataType:'json',
                success:function(response){
                    for (let index = 0; index < response.length; index++)
                    {
                        $('[name="subject"]').append('<option value='+response[index].id+'>'+ response[index].subject+'</option>');
                        $('.selectpicker').selectpicker('refresh');
                    }
                }
            })

            $('#trueorfalse').on('click', function(){
                $('#examtype').val('2');
                clearinputs('multiplechoice_form');
            });

            $('#multiplechoice').on('click', function(){
                $('#examtype').val('1');
                clearinputs('trueorfalse_form');
            });
            
        });

        
        $("#create_form").validate({
            rules: {
                subject:{
                    required:true
                },
                question:{
                    required:true
                }
            },
            submitHandler: function (form) {
                Swal.fire({
                    title: 'Save new question?',
                    text: "You won't be able to revert this!",
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, save it!'
                }).then((result) => {
                    if (result.value) {
                        
                        var formData = new FormData($("#create_form").get(0));

                        $.ajax({
                            url: "{{ route('exam-question.store')}}",
                            type: "POST",
                            data: formData,
                            cache:false,
                            contentType: false,
                            processData: false,
                            dataType: "JSON",
                            success: function (response) {
                                if(response.success){
                                    swal({
                                        title: "Success!",
                                        text: response.messages,
                                        type: "success"
                                    }).then(function() {
                                        $("#create_form")[0].reset();
                                    });
                                }else{
                                    swal(response.error, response.messages, "error");
                                }
                            },
                            error: function (jqXHR, textStatus, errorThrown) {
                                swal("Error", errorThrown, "warning");
                            }
                        });
                    }
                })
            }
        });

        
        $('input[type=radio][name=answer]').on('change', function() {
            if($(this).val() == '0')
            {
                if($("#txtoptiona").val().replace(/^\s+|\s+$/g, "").length == 0)
                {
                    $(this).prop('checked', false);
                    Swal.fire({
                        type: 'error',
                        title: 'Oops...',
                        text: 'Your are selecting empty field!'
                    });
                }
                
            }
            else if($(this).val() == '1')
            {
                if($("#txtoptionb").val().replace(/^\s+|\s+$/g, "").length == 0)
                {
                    $(this).prop('checked', false);
                    Swal.fire({
                        type: 'error',
                        title: 'Oops...',
                        text: 'Your are selecting empty field!'
                    });
                }
            }
            else if($(this).val() == '2')
            {
                if($("#txtoptionc").val().replace(/^\s+|\s+$/g, "").length == 0)
                {
                    $(this).prop('checked', false);
                    Swal.fire({
                        type: 'error',
                        title: 'Oops...',
                        text: 'Your are selecting empty field!'
                    });
                }
            }
            else if($(this).val() == '3')
            {
                if($("#txtoptiond").val().replace(/^\s+|\s+$/g, "").length == 0)
                {
                    $(this).prop('checked', false);
                    Swal.fire({
                        type: 'error',
                        title: 'Oops...',
                        text: 'Your are selecting empty field!'
                    });
                }
            }
        });

        
        function clearinputs(classname){
            $('#'+classname).find(':input').each(function() {
                switch(this.type) {
                    case 'text':
                        this.value ='';
                        break;
                    case 'textarea':
                        this.value ='';
                        break;
                    case 'radio':
                        this.checked = false;
                        break;
                }
            });
        }
        

    </script>
@endsection
