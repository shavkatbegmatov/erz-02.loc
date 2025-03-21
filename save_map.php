<?php
// --- MySQL bog'lanish sozlamalari ---
$host = "localhost";
$dbname = "erz";    // O'zingizning bazangiz nomi
$user = "root";         // MySQL foydalanuvchi
$pass = "";             // Parol (agar bo'lsa)

// PDO orqali ulanib ko‘ramiz
try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["message" => "Bazaga ulanish xatosi: " . $e->getMessage()]);
    exit;
}

// Kelayotgan JSON ma'lumotni o'qiymiz
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["message" => "Hech qanday ma'lumot kelmadi."]);
    exit;
}

// global_map jadvali yaratilmagan bo'lsa, yaratish (demo maqsadida)
try {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS global_map (
            id INT AUTO_INCREMENT PRIMARY KEY,
            x INT NOT NULL,
            y INT NOT NULL,
            r INT NOT NULL,
            g INT NOT NULL,
            b INT NOT NULL
        )
    ");
} catch (PDOException $e) {
    echo json_encode(["message" => "Jadval yaratishda xatolik: " . $e->getMessage()]);
    exit;
}

// Ma'lumotlarni jadvalga yozamiz
try {
    // Katta tranzaksiyada ishlaymiz
    $conn->beginTransaction();

    // Tayyorlangan so'rov
    $stmt = $conn->prepare("
        INSERT INTO global_map (x, y, r, g, b)
        VALUES (:x, :y, :r, :g, :b)
    ");

    foreach ($data as $pixel) {
        $stmt->execute([
            ':x' => $pixel['x'],
            ':y' => $pixel['y'],
            ':r' => $pixel['color']['r'],
            ':g' => $pixel['color']['g'],
            ':b' => $pixel['color']['b']
        ]);
    }

    $conn->commit();
    echo json_encode(["message" => "Ma'lumotlar muvaffaqiyatli saqlandi"]);
} catch (PDOException $e) {
    $conn->rollBack();
    echo json_encode(["message" => "Ma'lumot saqlashda xatolik: " . $e->getMessage()]);
}
