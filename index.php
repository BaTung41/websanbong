<?php
ob_start();

session_start();
$user_id = @$_SESSION['user_id'];
// include header.php file
include './database/DBController.php';

include('header.php');
?>
<?php

/*  include banner area  */
include('Template/_banner-area.php');
/*  include banner area  */

/*  include top sale section */
include('Template/_top-sale.php');
/*  include top sale section */

// /*  include special price section  */
// include('Template/_special-price.php');
// /*  include special price section  */

/*  include banner ads  */
include('Template/_banner-ads.php');
/*  include banner ads  */

// /*  include new phones section  */
// include('Template/_new-phones.php');
// /*  include new phones section  */

/*  include blog area  */
include('Template/_blogs.php');
/*  include blog area  */

?>
<style>
    #chat-icon:hover {
        background: #0056b3;
    }

    #chat-form label {
        font-weight: bold;
    }
</style>
<!-- Nút mở chat -->
 <?php
    if (isset($_SESSION['user_id'])) {
?>
    <div id="chat-icon" onclick="toggleChat()" style="position: fixed; bottom: 20px; right: 20px; background: #28a745; color: white; width: 60px; height: 60px; border-radius: 50%; display: flex; justify-content: center; align-items: center; cursor: pointer; z-index: 1000;">
        <i class="fas fa-comment-dots" style="font-size: 24px;"></i>
    </div>
<?php } ?>

<!-- Khung chat -->
<div id="chat-box" class="card shadow-lg border-0" style="position: fixed; bottom: 90px; right: 20px; width: 350px; display: none; z-index: 1000;">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">Chăm sóc khách hàng</h6>
        <button class="btn btn-sm btn-light" onclick="toggleChat()">&times;</button>
    </div>
    <div class="card-body" id="chat-messages" style="height: 300px; overflow-y: auto">
        <!-- Tin nhắn sẽ được hiển thị ở đây -->
    </div>
    <div class="card-footer bg-white">
        <form id="messageForm" class="d-flex">
            <textarea class="form-control me-2" id="message" name="message" rows="1" placeholder="Nhập tin nhắn..." required></textarea>
            <button type="submit" class="btn btn-primary ml-1">Gửi</button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function toggleChat() {
        let chatBox = document.getElementById("chat-box");
        chatBox.style.display = (chatBox.style.display === "none" || chatBox.style.display === "") ? "block" : "none";
    }

    function fetchMessages() {
        $.ajax({
            url: "fetch_messages.php",
            type: "GET",
            dataType: "json",
            success: function (response) {
                if (response.status === "success") {
                    $("#chat-messages").html(""); // Xóa tin nhắn cũ trước khi load mới
                    response.messages.forEach(msg => {
                        let alignment = msg.sender_id == response.current_user ? "justify-content-end" : "justify-content-start";
                        let bgColor = msg.sender_id == response.current_user ? "bg-primary text-white" : "bg-light";

                        $("#chat-messages").append(`
                            <div class="d-flex ${alignment} mb-2">
                                <div class="p-2 rounded ${bgColor}" style="max-width: 75%;">
                                    ${msg.message}
                                </div>
                            </div>
                        `);
                    });
                    $("#chat-messages").scrollTop($("#chat-messages")[0].scrollHeight);
                }
            }
        });
    }

    $("#messageForm").submit(function (event) {
        event.preventDefault();
        let message = $("#message").val().trim();
        if (message !== "") {
            $.ajax({
                url: "send_message.php",
                type: "POST",
                data: { message: message },
                dataType: "json",
                success: function (response) {
                    if (response.status === "success") {
                        fetchMessages(); // Load lại tin nhắn sau khi gửi
                        $("#message").val("").focus();
                    }
                }
            });
        }
    });

    setInterval(fetchMessages, 3000); // Cập nhật tin nhắn mỗi 3 giây
</script>


<script src="./index.js"></script>

<?php
// include footer.php file
include('footer.php');
?>