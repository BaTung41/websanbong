<?php
class Momo
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    /**
     * Store MoMo payment information
     * @param int $user_id
     * @param int $booking_id
     * @param string $momo_order_id
     * @param int $amount
     * @param int $momo_status (0=pending, 1=success, 2=failed)
     * @param string $link_data (JSON string of MoMo response)
     * @return bool
     */
    public function storeMomoInfo($user_id, $booking_id, $momo_order_id, $amount, $momo_status, $link_data)
    {
        // Use INSERT ... ON DUPLICATE KEY UPDATE to avoid duplicate key errors
        $u = intval($user_id);
        $b = intval($booking_id);
        $m = mysqli_real_escape_string($this->conn, $momo_order_id);
        $a = intval($amount);
        $s = intval($momo_status);
        $l = mysqli_real_escape_string($this->conn, $link_data);

        $sql = "INSERT INTO momos (user_id, booking_id, momo_order_id, amount, status, link_data, created_at) VALUES ($u, $b, '$m', $a, $s, '$l', NOW()) 
                ON DUPLICATE KEY UPDATE user_id=VALUES(user_id), booking_id=VALUES(booking_id), amount=VALUES(amount), status=VALUES(status), link_data=VALUES(link_data), created_at=NOW()";

        if (mysqli_query($this->conn, $sql)) {
            return true;
        } else {
            error_log('Insert failed: ' . mysqli_error($this->conn));
            return false;
        }
    }

    /**
     * Get MoMo payment info by order ID
     */
    public function getMomoByOrderId($momo_order_id)
    {
        $query = "SELECT * FROM momos WHERE momo_order_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $momo_order_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Get all MoMo payments for a user
     */
    public function getMomosByUserId($user_id)
    {
        $query = "SELECT * FROM momos WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
