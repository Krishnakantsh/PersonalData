@extends('Frontend/Pages/User/main')

@section('dashboard-content')


    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
        }

        .dashboard-header p {
            color: #e0e0e0;
        }
    </style>



    <style>
        .dashboard-header {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
            border-radius: 16px;
            padding: 25px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: "";
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 50%;
        }

        .dashboard-header::after {
            content: "";
            position: absolute;
            bottom: -40px;
            left: -40px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .dashboard-title {
            font-size: 22px;
            font-weight: 700;
        }

        .dashboard-subtitle {
            font-size: 14px;
            opacity: 0.9;
        }

        .ebook-count {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            padding: 10px 18px;
            border-radius: 12px;
            font-weight: 600;
        }

        .search-box {
            margin-top: 15px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border-radius: 10px;
            border: none;
            outline: none;
        }

        .search-box i {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
    </style>

    <style>
        .no-results-box {
            width: 100%;
            padding: 60px 20px;
            text-align: center;
            animation: fadeIn 0.4s ease-in-out;
        }

        .no-results-content {
            max-width: 400px;
            margin: auto;
            background: #f9fafc;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .no-results-content .icon {
            font-size: 50px;
            margin-bottom: 10px;
        }

        .no-results-content h4 {
            font-weight: 700;
            margin-bottom: 8px;
        }

        .no-results-content p {
            color: #777;
            font-size: 14px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    <section class="dashboard-header mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center">

            <!-- Left -->
            <div>
                <div class="dashboard-title">📚 My E-Books</div>
                <div class="dashboard-subtitle">
                    Explore, read and manage your digital library
                </div>
            </div>

            <!-- Right -->
            <div class="ebook-count mt-3 mt-md-0">
                {{ $ebooks->count() }} Books
            </div>

        </div>



        <form method="GET" id="ebook_search">
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search your e-books...">
            </div>
        </form>
    </section>

    <section class="product-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">



                    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">

                        @foreach ($ebooks as $ebook)
                            <div class="col">
                                <div class="book-card h-100 border">
                                    <img src="{{ asset($ebook->product->thumbnail) }}" class="book-img" />

                                    <div class="book-title">{{ $ebook->product->title }}</div>


                                    <div class="product-actions">
                                        <a href="{{ route('details', ['product' => $ebook->product->slug]) }}"
                                            class="btn-view-details">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>


                </div>
            </div>
        </div>
    </section>

    <div id="noResults" class="no-results-box" style="display:none;">
        <div class="no-results-content">
            <div class="icon">📚</div>
            <h4>No E-Books Found</h4>
            <p>We couldn’t find anything matching your search.<br>Try different keywords.</p>
        </div>
    </div>

    @if ($ebooks->lastPage() > 1)
        <div class="d-flex justify-content-center mt-4">
            <ul class="custom-pagination">

                <li class="{{ $ebooks->onFirstPage() ? 'disabled' : '' }}">
                    <a href="{{ $ebooks->previousPageUrl() }}">
                        &laquo;
                    </a>
                </li>

                @php
                    $current = $ebooks->currentPage();
                    $last = $ebooks->lastPage();
                @endphp

                @if ($current > 2)
                    <li><a href="{{ $ebooks->url(1) }}">1</a></li>
                @endif

                @if ($current > 3)
                    <li class="dots">...</li>
                @endif

                @for ($i = max(1, $current - 1); $i <= min($last, $current + 1); $i++)
                    <li class="{{ $i == $current ? 'active' : '' }}">
                        <a href="{{ $ebooks->url($i) }}">{{ $i }}</a>
                    </li>
                @endfor

                @if ($current < $last - 2)
                    <li class="dots">...</li>
                @endif

                @if ($current < $last - 1)
                    <li><a href="{{ $ebooks->url($last) }}">{{ $last }}</a></li>
                @endif

                <li class="{{ !$ebooks->hasMorePages() ? 'disabled' : '' }}">
                    <a href="{{ $ebooks->nextPageUrl() }}">
                        &raquo;
                    </a>
                </li>

            </ul>
        </div>
    @endif



    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {

            let query = this.value.toLowerCase();
            let cards = document.querySelectorAll('.book-card');
            let visible = 0;

            cards.forEach(card => {
                let title = card.querySelector('.book-title').innerText.toLowerCase();

                if (query.length < 2 || title.includes(query)) {
                    card.parentElement.style.display = "block";
                    visible++;
                } else {
                    card.parentElement.style.display = "none";
                }
            });

            document.getElementById('noResults').style.display = (visible === 0 && query.length >= 2) ? 'block' :
                'none';

        });
    </script>

@endsection
