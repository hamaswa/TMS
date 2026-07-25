@extends('main')
<style>
    .video-container {
        position: relative;
        width: 70px;
        height: 70px;
        cursor: pointer;
    }

    .video-thumbnail {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.5);
        /* Semi-transparent background */
        opacity: 0;
        transition: opacity 0.3s;
    }

    .play-icon {
        color: white;
        font-size: 24px;
    }

    .video-container:hover .overlay {
        opacity: 1;
    }

    .overlay-content {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        /* Semi-transparent background */
        z-index: 1;
        display: none;
        align-items: center;
        justify-content: center;
    }

    .close-icon {
        position: absolute;
        top: 5%;
        right: 5%;
        color: white;
        cursor: pointer;
        font-size: 2rem;
    }

    .video-div {
        position: relative;
        width: 80%;
        max-width: 800px;
        max-height: 80%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .video-div video {
        width: 100%;
        height: 100%;
    }

    /* Responsive adjustments */
    @media (max-width: 576px) {
        .video-container {
            width: 70px;
            height: 70px;
        }

        .close-icon {
            position: absolute;
            top: 5%;
            right: 5%;
            color: white;
            cursor: pointer;
            font-size: 1rem;
        }
    }

    @media (min-width: 768px) {
        .video-div {
            position: relative;
            width: 60%;
            max-width: 600px;
            max-height: 60%;
        }

        .video-div video {
            width: 100%;
            height: 100%;
        }

        .close-icon {
            position: absolute;
            top: 5%;
            right: 5%;
            color: white;
            cursor: pointer;
            font-size: 1.2rem;
        }
    }

    @media (min-width: 992px) {
        .video-div {
            position: relative;
            width: 80%;
            max-width: 800px;
            max-height: 80%;
        }

        .video-div video {
            width: 100%;
            height: 100%;
        }

        .close-icon {
            position: absolute;
            top: 5%;
            right: 5%;
            color: white;
            cursor: pointer;
            font-size: 2rem;
        }
    }
</style>
@section('content')
    <section class="main-content">
        <div class="container">
            <div class="card col-sm-10 mx-auto">
                <div class="row">
                    <div class="col-md-12">

                        @include('inc.message')

                        <div class="bg-white px-3 py-4">
                            <p class="text-right"><a href="{{ route('admin.cloth.create') }}" class="btn btn-primary">کپڑا شامل
                                    کریں۔ +</a>
                            </p>
                            <div class="table-title  mb-4 mt-2">
                                <h5 class="text-right">کپڑوں کی فہرست</h5>
                            </div>
                            <div>
                                <button type="button" class="btn btn-green" data-toggle="modal"
                                    data-target="#clothesCsvModal"> ایکسل فائل اپ لوڈ کریں۔ </button>
                                <a href="{{ route('admin.clothscsv') }}" class="btn btn-green"> ایکسل فائل میں برآمد کریں۔
                                </a>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table js-sortable-table" id="cc-table-data-customer-list">
                                            <thead>
                                                <tr>
                                                    <th scope="col" class="no-sort"></th>
                                                    <th scope="col" class="no-sort">کپڑے کی قسم</th>
                                                    <th scope="col" class="no-sort">کپڑے کی کمپنی</th>
                                                    <th scope="col" class="no-sort">کپڑے کا رنگ</th>
                                                    <th scope="col" class="no-sort">کپڑے کی لمبائی</th>
                                                    <th scope="col" class="no-sort">ریٹ فی میٹر</th>
                                                    <th scope="col" class="no-sort">کپڑے کی قیمت</th>
                                                    <th scope="col" class="no-sort">کپڑے کی تصویر</th>
                                                    {{-- <th scope="col" class="no-sort">کپڑے کی ویڈیو</th> --}}
                                                    <th scope="col" class="no-sort">عمل</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $counter = 1; // Initialize the counter outside the loops
                                                @endphp

                                                @foreach ($cloths as $cloth)
                                                    @php
                                                        $clothRowCount = $cloth->colors->count(); // Count the number of colors for the current cloth
                                                    @endphp
                                                    @foreach ($cloth->colors as $color)
                                                        <tr>
                                                            <td style="font-size: 18px;font-weight:600;">
                                                                {{ $counter++ }}
                                                            </td>

                                                            <td style="font-size: 18px;font-weight:600;">
                                                                {{ $cloth->type->name }}</td>

                                                            <td style="font-size: 18px;font-weight:600;">
                                                                {{ $cloth->brand->name }}</td>

                                                            <td style="font-size: 18px; font-weight: 600;">
                                                                {{ $color->color }}
                                                            </td>



                                                            <td style="font-size: 18px;font-weight:600;">
                                                                {{ $color->length }}
                                                                میٹر</td>

                                                            <td style="font-size: 18px;font-weight:600;">
                                                                Rs: {{ number_format((float) $cloth->price, 2) }}
                                                            </td>

                                                            <td style="font-size: 18px;font-weight:600;">
                                                                Rs:
                                                                {{ number_format((float) $cloth->price * (float) $color->length, 2) }}
                                                            </td>


                                                            <td>
                                                                @php
                                                                    $image = $cloth->images->firstWhere(
                                                                        'image_color',
                                                                        $color->color,
                                                                    );
                                                                @endphp
                                                                @if ($image)
                                                                    <img src="{{ asset('/' . $image->images) }}"
                                                                        alt="{{ $color->color }}"
                                                                        style="width: 70px; height: 70px;">
                                                                @endif
                                                            </td>


                                                            {{-- <td>
                                                                <div class="video-container">
                                                                    @php
                                                                        $video = $cloth->videos->firstWhere(
                                                                            'video_color',
                                                                            $color->color,
                                                                        );
                                                                    @endphp
                                                                    @if ($video)
                                                                        <video width="70" height="70" controls>
                                                                            <source
                                                                                src="{{ asset('storage/' . $video->video) }}">
                                                                        </video>
                                                                    @endif

                                                                    <div class="overlay">
                                                                        <i class="fa fa-play-circle play-icon"></i>
                                                                    </div>
                                                                    <div class="overlay-content">
                                                                        <i class="fa fa-times close-icon"></i>
                                                                        <div class="video-div">
                                                                            @php
                                                                                $video = $cloth->videos->firstWhere(
                                                                                    'video_color',
                                                                                    $color->color,
                                                                                );
                                                                            @endphp
                                                                            @if ($video)
                                                                                <video width="70" height="70"
                                                                                    controls>
                                                                                    <source
                                                                                        src="{{ asset('/' . $video->video) }}">
                                                                                </video>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td> --}}


                                                            <td class="d-flex justify-content-center align-items-center"
                                                                style="height: 60px;">
                                                                <a href="{{ route('admin.edit-cloths', ['id' => $cloth->id, 'color' => $color->color]) }}"
                                                                    class=""><i class="fa fa-edit"
                                                                        aria-hidden="true"></i></a>
                                                                <div>
                                                                    <button class="delete-selected btn btn-sm pb-0"
                                                                        type="button" data-id="{{ $cloth->id }}"
                                                                        data-color="{{ $color->color }}"><i
                                                                            class="fa fa-trash-alt"
                                                                            aria-hidden="true"></i></button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            {{-- Csv Modal --}}
            <div class="modal" id="clothesCsvModal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('admin.clothescsv') }}" method="post" enctype="multipart/form-data">
                            @csrf

                            <!-- Modal Header -->
                            <div class="modal-header">
                                <h4 class="modal-title">ایکسل فائل اپ لوڈ کریں۔</h4>
                            </div>

                            <!-- Modal body -->
                            <div class="modal-body">
                                <div class="form-group">
                                    <p class="text-right">ایکسل فائل</p>
                                    <input type="file" name="csvFile" value="" class="form-control" required>
                                </div>
                            </div>

                            <!-- Modal footer -->
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary  ml-2">محفوظ کریں</button>
                                <button type="button" class="btn btn-danger" data-dismiss="modal">بند کریں</button>

                            </div>
                        </form>
                    </div>
                </div>
            </div>
    </section>
    <script>
        $(document).ready(function() {
            $('.video-container').on('click', function() {
                $(this).find('.overlay-content').css('display', 'flex');
            });

            $('.video-container').on('click', '.close-icon', function(e) {
                e.stopPropagation(); // Prevent the click from bubbling up to the .video-container
                $(this).parent('.overlay-content').css('display', 'none');
            });
        });
    </script>

    <script>
        document.querySelectorAll('.delete-selected').forEach(function(button) {
            button.addEventListener('click', async function() {
                let clothId = this.getAttribute('data-id');
                let clothColor = this.getAttribute('data-color');
                var row = $(this).closest('tr');
                if (await window.TmsConfirm.ask('کیا آپ واقعی یہ کپڑے کا ریکارڈ حذف کرنا چاہتے ہیں؟', {
                    trigger: this
                })) {
                    // console.log('clothId',clothId);
                    // console.log('clothColor',clothColor);

                    // Send AJAX request to delete record
                    $.ajax({
                        url: '{{ route('admin.delete-cloths') }}', // Make sure this route is correct
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}', // CSRF token for security
                            id: clothId,
                            color: clothColor
                        },
                        success: function(response) {
                            if (response.success) {
                                console.log('Record deleted successfully');
                                // location.reload(); // Refresh the page after successful deletion
                                // Fade out the row before removing it
                                row.fadeOut(500, function() {
                                    // After the fade out completes, remove the row
                                    row.remove();
                                });
                            } else {
                                console.log('Failed to delete record:', response
                                    .message); // Log the error message from server
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('AJAX request failed:', xhr
                                .responseText); // Log the response error message
                }
            });

                }

            });
        });

        // document.querySelectorAll('.select-record').forEach(function(checkbox) {
        //     checkbox.addEventListener('change', function() {
        //         if (this.checked) {
        //             console.log('Record ID:', this.value);
        //             console.log('Type:', this.dataset.type);
        //             console.log('Brand:', this.dataset.brand);
        //             console.log('Color:', this.dataset.color);
        //         }
        //     });
        // });
    </script>
@endsection
