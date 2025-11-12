<?php

    $select_blog =  mysqli_query($conn, "SELECT * FROM `blogs` order by id desc limit 3") or die('Query failed');
    $selectBlogs = mysqli_fetch_all($select_blog, MYSQLI_ASSOC);

?>
<!-- Blogs -->
<section id="blogs">
    <div class="container py-4">
        <h4 class="font-rubik font-size-20">Blogs</h4>
        <hr>

        <div class="owl-carousel owl-theme">
            <?php foreach ($selectBlogs as $item) { ?>
            <div class="item">
                <div class="card border-0 font-rale mr-5" style="width: 18rem;">
                    <h5 style="min-height: 39px;" class="card-title font-size-16"><?php echo $item['title']; ?></h5>
                    <img style="min-height: 242px;" src="./assets/blog/<?php echo $item['image']; ?>" alt="cart image" class="card-img-top">
                    <p class="card-text font-size-14 text-black-50 py-1"><?php echo $item['description']; ?></p>
                    <a href="blog_details.php?id=<?php echo $item['id']; ?>" class="color-second text-left">Xem chi tiết</a>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</section>
<!-- !Blogs -->
