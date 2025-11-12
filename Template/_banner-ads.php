<!-- Field Information Section -->
<section id="field_info" class="py-5">
    <div class="container">
        <div class="row">
            <!-- Thông tin liên hệ -->
            <div class="col-md-6 mb-4">
                <div class="contact-info-card">
                    <h2 class="section-title">Thông Tin Liên Hệ</h2>
                    <div class="info-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="info-content">
                            <h4>Địa Chỉ</h4>
                            <p>Sân bóng Đầm Hồng, Đường Đầm Hồng, Phường Định Công, Hoàng Mai, Hà Nội</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <div class="info-content">
                            <h4>Hotline</h4>
                            <p>0941201816</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-clock"></i>
                        <div class="info-content">
                            <h4>Giờ Hoạt Động</h4>
                            <p>06:00 - 23:00 (Tất cả các ngày trong tuần)</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <div class="info-content">
                            <h4>Email</h4>
                            <p>mchienfootball@gmail.com</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bản đồ -->
            <div class="col-md-6 mb-4">
                <div class="map-card">
                    <h2 class="section-title">Bản Đồ</h2>
                    <div class="map-container">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3724.9711319565823!2d105.83399187479573!3d20.99307318850183!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135ac71fb98d741%3A0x241748e4626b02b9!2zU8OibiBiw7NuZyDEkOG6p20gSOG7k25nIDE!5e0!3m2!1svi!2s!4v1709655391044!5m2!1svi!2s"
                            width="100%" 
                            height="400" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .section-title {
            color: #1B4D3E;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 2px solid #28a745;
            display: inline-block;
        }

        .contact-info-card, .map-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            height: 100%;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .info-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }

        .info-item i {
            font-size: 24px;
            color: #28a745;
            margin-right: 15px;
            width: 40px;
            height: 40px;
            background: rgba(40, 167, 69, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .info-content h4 {
            font-size: 18px;
            color: #1B4D3E;
            margin-bottom: 5px;
        }

        .info-content p {
            color: #666;
            margin: 0;
        }

        .map-container {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .contact-info-card, .map-card {
                margin-bottom: 20px;
            }
        }
    </style>
</section>
<!-- !Field Information Section -->
