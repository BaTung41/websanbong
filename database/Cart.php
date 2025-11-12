<?php

// php cart class
class Cart
{
    public $db = null;

    public function __construct(DBController $db)
    {
        if (!isset($db->con)) return null;
        $this->db = $db;
    }

    // insert into cart table
    public function insertIntoCart($params = null, $table = "cart") {
        if ($this->db->con != null && $params != null) {
            // Chuẩn bị câu lệnh SQL với prepared statement
            $columns = implode(',', array_keys($params));
            $placeholders = str_repeat('?,', count($params) - 1) . '?';
            
            $query = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
            
            // Chuẩn bị statement
            $stmt = $this->db->con->prepare($query);
            
            if ($stmt) {
                // Bind các giá trị
                $types = str_repeat('s', count($params)); // Assume all strings for safety
                $stmt->bind_param($types, ...array_values($params));
                
                // Thực thi và trả về kết quả
                $result = $stmt->execute();
                $stmt->close();
                return $result;
            }
            return false;
        }
        return false;
    }

    public function deleteAllCart($user_id = null){
        if ($this->db->con != null){
            $result = $this->db->con->query("DELETE FROM cart WHERE user_id={$user_id}");
            return $result;
        }
    }

    // to get user_id and item_id and insert into cart table
    public function addToCart($userid, $itemid, $size, $color){
        if (isset($userid) && isset($itemid)){
            // Kiểm tra xem sản phẩm đã có trong giỏ hay chưa
            $checkCart = mysqli_query($this->db->con, "SELECT * FROM cart WHERE user_id = {$userid} AND item_id = {$itemid}");
            
            // Nếu sản phẩm đã có trong giỏ, tăng số lượng lên 1
            if (mysqli_num_rows($checkCart) > 0) {
                // Cập nhật số lượng sản phẩm trong giỏ
                $updateQuery = "UPDATE cart SET quantity = quantity + 1 WHERE user_id = {$userid} AND item_id = {$itemid}";
                $result = mysqli_query($this->db->con, $updateQuery);
            } else {
                // Nếu sản phẩm chưa có trong giỏ, thêm mới vào giỏ
                $params = array(
                    "user_id" => $userid,
                    "item_id" => $itemid,
                    "quantity" => 1, // Số lượng mặc định khi thêm mới
                    "size" => $size,
                    "color" => $color
                );
                $result = $this->insertIntoCart($params);
                if ($result) {
                    // Reload Page
                    header("Location: " . $_SERVER['PHP_SELF']);
                }
            }
        }
    }
    
    // delete cart item using cart item id
    public function deleteCart($item_id = null, $table = 'cart'){
        if($item_id != null){
            $result = $this->db->con->query("DELETE FROM {$table} WHERE item_id={$item_id}");
            if($result){
                header("Location:" . $_SERVER['PHP_SELF']);
            }
            return $result;
        }
    }

    public function getSum($cartItems){
        $sum = 0;
    
        if(isset($cartItems)){
            foreach ($cartItems as $cartItem){
                // Lấy item_id và quantity từ cart
                $itemId = $cartItem['item_id'];
                $quantity = intval($cartItem['quantity']);
    
                // Lấy giá sản phẩm từ bảng products
                $result = mysqli_query($this->db->con, "SELECT item_price FROM products WHERE item_id = {$itemId}");
                $item = mysqli_fetch_assoc($result);
    
                // Kiểm tra xem có giá trị item_price không
                if ($item) {
                    $itemPrice = floatval($item['item_price']); // Giá của sản phẩm
                    $sum += $itemPrice * $quantity; // Tính tổng tiền của sản phẩm
                }
            }
        }
    
        // Trả về tổng tiền đã định dạng
        return number_format($sum, 0, ',', '.');
    }
    

    // get item_it of shopping cart list
    public function getCartId($cartArray = null, $key = "item_id"){
        if ($cartArray != null){
            $cart_id = array_map(function ($value) use($key){
                return $value[$key];
            }, $cartArray);
            return $cart_id;
        }
    }

    // Save for later
    public function saveForLater($item_id = null, $saveTable = "wishlist", $fromTable = "cart"){
        if ($item_id != null){
            $query = "INSERT INTO {$saveTable} SELECT * FROM {$fromTable} WHERE item_id={$item_id};";
            $query .= "DELETE FROM {$fromTable} WHERE item_id={$item_id};";

            // execute multiple query
            $result = $this->db->con->multi_query($query);

            if($result){
                header("Location :" . $_SERVER['PHP_SELF']);
            }
            return $result;
        }
    }

    public function updateQuantity($item_id, $quantity) {
        // Update quantity for the given item ID
        $query = "UPDATE cart SET quantity = ? WHERE item_id = ?";
        $stmt = $this->db->con->prepare($query);
        $stmt->bind_param("ii", $quantity, $item_id);
        $stmt->execute();
    }


}