<!--   product  -->
<?php
$user_id = $_SESSION['user_id'] ?? 1;
    $item_id = $_GET['item_id'] ?? 1;
    foreach ($product->getData() as $item) :
        if ($item['item_id'] == $item_id) :
?>
<section id="product" class="py-3">
    <div class="container">
        <div class="row">
            <div class="col-sm-6">
                <img src="./assets/products/<?php echo $item['item_image'] ?>" alt="product" class="img-fluid">
            </div>
            <div class="col-sm-6 py-5">
                <h5 class="font-baloo font-size-20"><?php echo $item['item_name'] ?? "Unknown"; ?></h5>
                <div class="d-flex">
                    <!-- <div class="rating text-warning font-size-12">
                        <span><i class="fas fa-star"></i></span>
                        <span><i class="fas fa-star"></i></span>
                        <span><i class="fas fa-star"></i></span>
                        <span><i class="fas fa-star"></i></span>
                        <span><i class="far fa-star"></i></span>
                    </div>
                    <a href="#" class="px-2 font-rale font-size-14">20,534 ratings | 1000+ answered questions</a> -->
                </div>
                <hr class="m-0">

                <!---    product price       -->
                <table class="my-3">
                    <!-- <tr class="font-rale font-size-14">
                        <td>M.R.P:</td>
                        <td><strike>$162.00</strike></td>
                    </tr> -->
                    <tr class="font-rale font-size-14">
                        <td>Giá:</td>
                        <td class="font-size-20 text-danger">
                            <span><?php echo number_format($item['item_price'], 0, ',', '.'); ?> đ</span><small
                                class="text-dark font-size-12">&nbsp;&nbsp;Bao gồm thuế</small></td>
                    </tr>
                    
                </table>
                <!---    !product price       -->

                <!--    #policy -->
                <div id="policy">
                    <div class="d-flex">
                        <div class="return text-center mr-5">
                            <div class="font-size-20 my-2 color-second">
                                <span class="fas fa-retweet border p-3 rounded-pill"></span>
                            </div>
                            <a href="#" class="font-rale font-size-12">3 Ngày <br> đổi trả </a>
                        </div>
                        <div class="return text-center mr-5">
                            <div class="font-size-20 my-2 color-second">
                                <span class="fas fa-truck  border p-3 rounded-pill"></span>
                            </div>
                            <a href="#" class="font-rale font-size-12">Giao hàng <br>Tận nơi</a>
                        </div>
                        <div class="return text-center mr-5">
                            <div class="font-size-20 my-2 color-second">
                                <span class="fas fa-check-double border p-3 rounded-pill"></span>
                            </div>
                            <a href="#" class="font-rale font-size-12">24h <br>Bảo hành</a>
                        </div>
                    </div>
                </div>
                <!--    !policy -->
                <hr>

                <!-- order-details -->
                <!-- <div id="order-details" class="font-rale d-flex flex-column text-dark">
                    <small>Delivery by : Mar 29  - Apr 1</small>
                    <small>Sold by <a href="#">Daily Electronics </a>(4.5 out of 5 | 18,198 ratings)</small>
                    <small><i class="fas fa-map-marker-alt color-primary"></i>&nbsp;&nbsp;Deliver to Customer - 424201</small>
                </div> -->
                <!-- !order-details -->

                <div class="form-row pt-4 font-size-16 font-baloo">
                    <form method="post">
                        <input type="hidden" name="item_id" value="<?php echo $item['item_id'] ?? '1'; ?>">
                        <input type="hidden" name="user_id" value="<?php echo $user_id ?>">
                        <div class="row">
                            <div class="col-9">
                                <div class="color my-3">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="font-baloo">Màu sắc:</h6>
                                        <div class="p-2 bg-white border rounded-circle">
                                            <input type="radio" name="color" value="Trắng" required class="btn font-size-14">
                                        </div>
                                        <div class="p-2 bg-dark rounded-circle">
                                            <input type="radio" name="color" value="Đen" class="btn font-size-14">
                                        </div>
                                        <div class="p-2 bg-primary rounded-circle">
                                            <input type="radio" name="color" value="Xanh" class="btn font-size-14">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-9">
                                <div class="size my-3">
                                    <h6 class="font-baloo">Size:</h6>
                                    <div class="d-flex justify-content-between w-75">
                                        <?php for($size = 37; $size <= 42; $size++) : ?>
                                        <div class="font-rubik border p-2">
                                            <input type="radio" name="size" value="<?php echo $size; ?>" required class="btn p-0 font-size-14">
                                            <label><?php echo $size; ?></label>
                                        </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                        if (in_array($item['item_id'], $Cart->getCartId($product->getData('cart')) ?? [])){
                            echo '<button type="submit" disabled class="btn btn-success font-size-16 form-control">Đã có trong giỏ</button>';
                        }else{
                            echo '<button type="submit" name="top_sale_submit" class="btn btn-warning font-size-16 form-control">Thêm vào giỏ</button>';
                        }
                        ?>
                    </form>
                </div>

            </div>

            <div class="col-12">
                <h6 class="font-rubik mt-4">Mô tả sản phẩm</h6>
                <hr>
                <p class="font-rale font-size-14">
                    <?php echo $item['item_desc'] ?? "Unknown"; ?>
                </p>
            </div>
        </div>
    </div>
</section>
<!--   !product  -->
<?php
        endif;
        endforeach;
?>