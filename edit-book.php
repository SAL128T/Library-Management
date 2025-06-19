<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$conn = mysqli_connect('localhost', 'root', '', 'library');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        
        $id = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        $first_name = filter_var($_POST['first_name'], FILTER_SANITIZE_STRING);
        $last_name = filter_var($_POST['last_name'], FILTER_SANITIZE_STRING);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $book_title = filter_var($_POST['book_title'], FILTER_SANITIZE_STRING);
        $borrow_date = $_POST['borrow_date'];
        $return_date = $_POST['return_date'];

        
        if (strtotime($return_date) < strtotime($borrow_date)) {
            throw new Exception("Return date cannot be earlier than borrow date");
        }

        $stmt = $conn->prepare("UPDATE books SET first_name=?, last_name=?, email=?, book_title=?, borrow_date=?, return_date=? WHERE id=?");
        if ($stmt->bind_param("ssssssi", $first_name, $last_name, $email, $book_title, $borrow_date, $return_date, $id) && $stmt->execute()) {
            $_SESSION['message'] = "Record updated successfully!";
            $_SESSION['message_type'] = "success";
            header("Location: library.php");
            exit;
        } else {
            throw new Exception("Error updating record: " . $conn->error);
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}


$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: library.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$book = $result->fetch_assoc();

if (!$book) {
    $_SESSION['message'] = "Record not found!";
    $_SESSION['message_type'] = "error";
    header("Location: library.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Book Record</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }

        .form-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: #4CAF50;
            outline: none;
            box-shadow: 0 0 5px rgba(76,175,80,0.2);
        }

        .error {
            color: #dc3545;
            margin-bottom: 15px;
            text-align: center;
        }

        .message {
            padding: 12px;
            margin: 10px 0;
            border-radius: 4px;
            text-align: center;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-buttons {
            margin-top: 30px;
            text-align: center;
        }

        .form-buttons button,
        .form-buttons input[type="submit"] {
            padding: 10px 24px;
            margin: 0 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .form-buttons input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
        }

        .form-buttons input[type="submit"]:hover {
            background-color: #45a049;
        }

        .form-buttons button {
            background-color: #6c757d;
            color: white;
            border: none;
        }

        .form-buttons button:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Book Record</h1>
        
        <?php if (isset($error_message)): ?>
            <div class="message error">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <form method="post" onsubmit="return validateForm()">
            <input type="hidden" name="id" value="<?= htmlspecialchars($book['id']) ?>">
            
            <div class="form-group">
                <label>First Name:
                    <input type="text" name="first_name" value="<?= htmlspecialchars($book['first_name']) ?>" required>
                </label>
            </div>

            <div class="form-group">
                <label>Last Name:
                    <input type="text" name="last_name" value="<?= htmlspecialchars($book['last_name']) ?>" required>
                </label>
            </div>

            <div class="form-group">
                <label>Email:
                    <input type="email" name="email" value="<?= htmlspecialchars($book['email']) ?>" required>
                </label>
            </div>

            <div class="form-group">
                <label>Book Title:
                    <input type="text" name="book_title" value="<?= htmlspecialchars($book['book_title']) ?>" required>
                </label>
            </div>

            <div class="form-group">
                <label>Borrow Date:
                    <input type="date" name="borrow_date" value="<?= htmlspecialchars($book['borrow_date']) ?>" required>
                </label>
            </div>

            <div class="form-group">
                <label>Return Date:
                    <input type="date" name="return_date" value="<?= htmlspecialchars($book['return_date']) ?>" required>
                </label>
            </div>

            <div class="form-buttons">
                <input type="submit" value="Update Record">
                <button type="button" onclick="confirmCancel()">Cancel</button>
            </div>
        </form>
    </div>

    <script>
    function validateForm() {
        var fields = ['first_name', 'last_name', 'email', 'book_title', 'borrow_date', 'return_date'];
        
        for (var i = 0; i < fields.length; i++) {
            var value = document.getElementsByName(fields[i])[0].value.trim();
            if (!value) {
                alert('Please fill in all fields');
                return false;
            }
        }

        
        var borrowDate = new Date(document.getElementsByName('borrow_date')[0].value);
        var returnDate = new Date(document.getElementsByName('return_date')[0].value);
        
        if (returnDate < borrowDate) {
            alert('Return date cannot be earlier than borrow date');
            return false;
        }

        return true;
    }

    function confirmCancel() {
        if (confirm('Are you sure you want to cancel? Any unsaved changes will be lost.')) {
            window.location.href = 'library.php';
        }
    }
    </script>
</body>
</html>
