<?php
// Database config
$host = "localhost";
$user = "root";
$pass = "";
$db   = "foodybuzz_db";

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Decide what action to perform
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) ORDER from menu (cart checkout)
    if (isset($_POST['action']) && $_POST['action'] === 'order') {
        handleOrder($conn);
        $conn->close();
        exit;
    }

    // 2) TABLE RESERVATION (from reservations form)
    if (
        isset($_POST['name'], $_POST['phone'], $_POST['guests'], $_POST['date'], $_POST['time'])
    ) {
        handleReservation($conn);
        $conn->close();
        exit;
    }
}

// Close connection at end if nothing was processed
$conn->close();


// ================= FUNCTIONS ================= //

/**
 * Handle table reservations
 */
function handleReservation(mysqli $conn)
{
    $name   = trim($_POST['name'] ?? '');
    $phone  = trim($_POST['phone'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $guests = intval($_POST['guests'] ?? 0);
    $date   = $_POST['date'] ?? '';
    $time   = $_POST['time'] ?? '';

    if ($name === '' || $phone === '' || $guests <= 0 || $date === '' || $time === '') {
        echo "<h2 style='text-align:center;color:red;margin-top:50px;'>
                ❌ Invalid reservation data. Please go back and try again.
              </h2>";
        return;
    }

    $stmt = $conn->prepare(
        "INSERT INTO reservations (name, phone, email, guests, date, time)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        echo "Error preparing statement: " . $conn->error;
        return;
    }

    $stmt->bind_param("sssiss", $name, $phone, $email, $guests, $date, $time);

    if ($stmt->execute()) {
        echo "<h2 style='text-align:center;color:green;margin-top:50px;'>
                ✅ Reservation Confirmed!<br>Thank you for choosing Foody Buzz.
              </h2>
              <p style='text-align:center;margin-top:20px;'>
                <a href='index.html#reservations' style='color:#c59d5f;text-decoration:none;'>
                  ⬅ Back to Foody Buzz
                </a>
              </p>";
    } else {
        echo "Error saving reservation: " . $stmt->error;
    }

    $stmt->close();
}


/**
 * Handle online order from cart
 * Expected POST fields:
 *   action = "order"
 *   name, phone, address, cart (JSON string)
 */
function handleOrder(mysqli $conn)
{
    $name     = trim($_POST['name'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $cartJson = $_POST['cart'] ?? '';

    if ($name === '' || $phone === '' || $address === '' || $cartJson === '') {
        echo "❌ Invalid order data.";
        return;
    }

    $cart = json_decode($cartJson, true);
    if (!is_array($cart)) {
        echo "❌ Invalid cart format.";
        return;
    }

    $total = 0;
    foreach ($cart as $item) {
        $price = isset($item['price']) ? floatval($item['price']) : 0;
        $qty   = isset($item['qty']) ? intval($item['qty']) : 0;
        $total += $price * $qty;
    }

    $itemsEncoded = json_encode($cart, JSON_UNESCAPED_UNICODE);

    $stmt = $conn->prepare(
        "INSERT INTO orders (name, phone, address, items, total)
         VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        echo "Error preparing statement: " . $conn->error;
        return;
    }

    $stmt->bind_param("ssssd", $name, $phone, $address, $itemsEncoded, $total);

    if ($stmt->execute()) {
        echo "✅ Order Placed Successfully! Total Amount: ₹" . number_format($total, 2);
    } else {
        echo "Error saving order: " . $stmt->error;
    }

    $stmt->close();
}
?>
