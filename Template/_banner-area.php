<!-- Banner Area -->
<section id="banner-area">
    <div class="slider">
        <div class="item"><img src="./assets/slide-1.jpeg" alt="Banner1"></div>
        <div class="item"><img src="./assets/slide-2.jpg" alt="Banner2"></div>
        <div class="item"><img src="./assets/slide-3.jpeg" alt="Banner3"></div>
    </div>
    <div class="dots">
        <span class="dot active"></span>
        <span class="dot"></span>
        <span class="dot"></span>
    </div>
</section>
<!-- !Banner Area -->

<style>
    #banner-area {
        width: 100%;
        position: relative;
        overflow: hidden;
    }

    .slider {
        width: 300%;
        height: 600px;
        display: flex;
        transition: transform 0.5s ease-in-out;
    }

    .slider .item {
        width: 33.33%;
    }

    .slider .item img {
        width: 100%;
        height: 600px;
        object-fit: cover;
    }

    .dots {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 10px;
    }

    .dot {
        width: 12px;
        height: 12px;
        background: rgba(255, 255, 255, 0.5);
        border-radius: 50%;
        cursor: pointer;
    }

    .dot.active {
        background: rgba(255, 255, 255, 1);
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function(){
        const slider = $('.slider');
        const dots = $('.dot');
        let currentSlide = 0;
        let totalSlides = $('.slider .item').length;
        let slideInterval;

        // Hàm chuyển slide
        function goToSlide(index) {
            currentSlide = index;
            slider.css('transform', `translateX(-${33.33 * index}%)`);
            dots.removeClass('active');
            dots.eq(index).addClass('active');
        }

        // Bắt đầu tự động chuyển slide
        function startSlideShow() {
            slideInterval = setInterval(() => {
                currentSlide = (currentSlide + 1) % totalSlides;
                goToSlide(currentSlide);
            }, 5000);
        }

        // Xử lý sự kiện click vào dot
        dots.click(function() {
            clearInterval(slideInterval);
            goToSlide($(this).index());
            startSlideShow();
        });

        // Khởi động slideshow
        goToSlide(0);
        startSlideShow();
    });
</script>
