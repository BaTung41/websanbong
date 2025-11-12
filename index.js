$(document).ready(function(){

    // banner owl carousel
    $("#banner-area .owl-carousel").owlCarousel({
        dots: true,
        items: 1
    });

    // top sale owl carousel
    $("#top-sale .owl-carousel").owlCarousel({
        loop: true,
        nav: true,
        dots: false,
        responsive : {
            0: {
                items: 1
            },
            600: {
                items: 3
            },
            1000 : {
                items: 5
            }
        }
    });

    // isotope filter
    var $grid = $(".grid").isotope({
        itemSelector : '.grid-item',
        layoutMode : 'fitRows'
    });

    // filter items on button click
    $(".button-group").on("click", "button", function(){
        var filterValue = $(this).attr('data-filter');
        $grid.isotope({ filter: filterValue});
    })


    // new phones owl carousel
    $("#new-phones .owl-carousel").owlCarousel({
        loop: true,
        nav: false,
        dots: true,
        responsive : {
            0: {
                items: 1
            },
            600: {
                items: 3
            },
            1000 : {
                items: 5
            }
        }
    });

    // blogs owl carousel
    $("#blogs .owl-carousel").owlCarousel({
        loop: true,
        nav: false,
        dots: true,
        responsive : {
            0: {
                items: 1
            },
            600: {
                items: 3
            }
        }
    })

    // product qty section
let $qty_up = $(".qty .qty-up");
let $qty_down = $(".qty .qty-down");
let $deal_price = $("#deal-price");

// click on qty up button
$qty_up.click(function(e){
    let $input = $(`.qty_input[data-id='${$(this).data("id")}']`);
    let $price = $(`.product_price[data-id='${$(this).data("id")}']`);
    let item_price = parseInt($price.data('price')); // Lấy giá trị item_price từ data-price

    if ($input.val() >= 1 && $input.val() < 9) { // Giới hạn số lượng từ 1 đến 9
        $input.val(function(i, oldval) {
            return ++oldval; // Tăng số lượng
        });

        // Tính giá mới của sản phẩm
        let new_price = item_price * parseInt($input.val());
        $price.text(new_price.toLocaleString() + ' đ'); // Hiển thị giá mới với định dạng

        // Cập nhật lại tổng tiền
        let subtotal = parseInt($deal_price.text().replace(/,/g, '')) + item_price; // Thêm vào tổng tiền
        $deal_price.text(subtotal.toLocaleString() + ' đ'); // Hiển thị tổng tiền
    }
}); // closing qty up button

// click on qty down button
$qty_down.click(function(e){
    let $input = $(`.qty_input[data-id='${$(this).data("id")}']`);
    let $price = $(`.product_price[data-id='${$(this).data("id")}']`);
    let item_price = parseInt($price.data('price')); // Lấy giá trị item_price từ data-price

    if ($input.val() > 1 && $input.val() <= 10) { // Giới hạn số lượng từ 1 đến 10
        $input.val(function(i, oldval) {
            return --oldval; // Giảm số lượng
        });

        // Tính giá mới của sản phẩm
        let new_price = item_price * parseInt($input.val());
        $price.text(new_price.toLocaleString() + ' đ'); // Hiển thị giá mới với định dạng

        // Cập nhật lại tổng tiền
        let subtotal = parseInt($deal_price.text().replace(/,/g, '')) - item_price; // Trừ ra tổng tiền
        $deal_price.text(subtotal.toLocaleString() + ' đ'); // Hiển thị tổng tiền
    }
}); // closing qty down button



});