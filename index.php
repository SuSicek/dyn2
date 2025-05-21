<?php
$servername = "localhost";
$username = "leteckyj";
$password = "cisco123";
$dbname = "leteckyj_jdm";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$pageTitle = "Japonská sportovní auta";

function getAllCars($conn) {
    $sql = "SELECT * FROM sports_cars ORDER BY manufacturer, model";
    $result = $conn->query($sql);
    $cars = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $cars[] = $row;
        }
    }
    return $cars;
}

function getManufacturersFromCars($cars) {
    $manufacturers = [];
    foreach ($cars as $car) {
        if (!in_array($car['manufacturer'], $manufacturers)) {
            $manufacturers[] = $car['manufacturer'];
        }
    }
    return $manufacturers;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_car"])) {
    $model = $_POST["model"];
    $year = $_POST["year"];
    $horsepower = $_POST["horsepower"];
    $price = $_POST["price"];
    $manufacturer = $_POST["manufacturer"];

    if ($year < 1960 || $year > date("Y")) {
        $error = "Neplatný rok výroby";
    } elseif ($horsepower < 50 || $horsepower > 2000) {
        $error = "Neplatný výkon motoru";
    } else {
        $stmt = $conn->prepare("INSERT INTO sports_cars (manufacturer, model, year, horsepower, price) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiii", $manufacturer, $model, $year, $horsepower, $price);
        if ($stmt->execute()) {
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit;
        } else {
            $error = "Chyba při přidávání auta: " . $conn->error;
        }
        $stmt->close();
    }
}

$cars = getAllCars($conn);
$manufacturers = getManufacturersFromCars($cars);

$topPowerfulCars = $cars;
usort($topPowerfulCars, fn($a, $b) => $b['horsepower'] - $a['horsepower']);
$topPowerfulCars = array_slice($topPowerfulCars, 0, 3);

function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . " Kč";
}
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h1><?= $pageTitle ?></h1>

    <?php if (isset($_GET['success'])): ?>
        <p class="success">Auto bylo úspěšně přidáno.</p>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <p class="error"><?= $error ?></p>
    <?php endif; ?>

    <div class="formular-box">
        <h2>Přidat nové auto</h2>
        <form method="post">
            <div class="form-row">
                <label for="manufacturer">Výrobce:</label>
                <input type="text" name="manufacturer" id="manufacturer" required>
            </div>
            <div class="form-row">
                <label for="model">Model:</label>
                <input type="text" name="model" id="model" required>
            </div>
            <div class="form-row">
                <label for="year">Rok výroby:</label>
                <input type="number" name="year" id="year" required>
            </div>
            <div class="form-row">
                <label for="horsepower">Výkon (HP):</label>
                <input type="number" name="horsepower" id="horsepower" required>
            </div>
            <div class="form-row">
                <label for="price">Cena:</label>
                <input type="number" name="price" id="price" required>
            </div>
            <button type="submit" name="add_car">Přidat auto</button>
        </form>
    </div>

    <h2>Auta podle výrobce</h2>
    <?php foreach ($manufacturers as $maker): ?>
        <div class="kategorie-box">
            <h2><?= htmlspecialchars($maker) ?></h2>
            <div class="cars-container">
                <?php foreach ($cars as $car): ?>
                    <?php if ($car['manufacturer'] === $maker): ?>
                        <div class="card">
                            <h3><?= htmlspecialchars($car['model']) ?></h3>
                            <p><?= htmlspecialchars($car['manufacturer']) ?></p>
                            <p>Rok: <?= $car['year'] ?></p>
                            <p><?= $car['horsepower'] ?> HP</p>
                            <p><?= formatPrice($car['price']) ?></p>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <h2>Top 3 nejvýkonnější auta</h2>
    <div class="cars-container">
        <?php foreach ($topPowerfulCars as $car): ?>
            <div class="card">
                <h3><?= htmlspecialchars($car['model']) ?></h3>
                <p><?= htmlspecialchars($car['manufacturer']) ?></p>
                <p><?= $car['horsepower'] ?> HP</p>
                <p><?= formatPrice($car['price']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>

<?php $conn->close(); ?>
