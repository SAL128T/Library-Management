<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$conn = mysqli_connect('localhost', 'root', '', 'library');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name  = $_POST['last_name'];
    $email      = $_POST['email'];
    $book_title = $_POST['book_title'];
    $borrow_date = $_POST['borrow_date'];
    $return_date = $_POST['return_date'];

    $stmt = $conn->prepare("INSERT INTO books (first_name, last_name, email, book_title, borrow_date, return_date) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->bind_param("ssssss", $first_name, $last_name, $email, $book_title, $borrow_date, $return_date) && $stmt->execute()) {
        $_SESSION['message'] = "Record added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error adding record: " . $conn->error;
        $_SESSION['message_type'] = "error";
    }
    $stmt->close();
    header("Location: library.php");
    exit;
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($conn->query("DELETE FROM books WHERE id = $id")) {
        $_SESSION['message'] = "Record deleted successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting record: " . $conn->error;
        $_SESSION['message_type'] = "error";
    }
    header("Location: library.php");
    exit;
}

$books = [];
$result = $conn->query("SELECT * FROM books");
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Library Borrowing Records</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .page-layout {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex: 1;
            padding: 20px;
        }

        .sidebar {
            width: 325px;  
            background: #f8f9fa;
            padding: 20px;
            border-left: 1px solid #dee2e6;
            height: 100vh;
            position: fixed;
            right: 0;
            top: 0;
            overflow-y: auto;
        }

        .container {
            margin-right: 340px; 
        }

        
        .logout-btn {
            float: right;
            margin: 20px 0;
            padding: 8px 20px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: #c82333;
        }

        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .sidebar h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }

        .sidebar form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .sidebar label {
            display: flex;
            flex-direction: column;
            gap: 5px;
            font-weight: bold;
            color: #555;
        }

        .sidebar input {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .sidebar input[type="submit"] {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            margin-top: 10px;
        }

        .sidebar input[type="submit"]:hover {
            background: #45a049;
        }

        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            display: none;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 5px;
            width: 50%;
            max-width: 500px;
            text-align: center;
        }
        .modal-buttons {
            margin-top: 20px;
        }
        .modal-buttons button {
            margin: 0 10px;
            padding: 8px 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="page-layout">
        <div class="main-content">
            <div class="container">
                
                <div class="header-container">
                    <h1>Library Borrowing Records</h1>
                    <button class="btn logout-btn" onclick="window.location.href='logout.php'">Logout</button>
                </div>

                
                <div id="messageContainer" class="message <?= isset($_SESSION['message_type']) ? $_SESSION['message_type'] : '' ?>">
                    <?php
                    if (isset($_SESSION['message'])) {
                        echo htmlspecialchars($_SESSION['message']);
                        echo "<script>document.getElementById('messageContainer').style.display = 'block';</script>";
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    }
                    ?>
                </div>

                
                <div id="deleteModal" class="modal">
                    <div class="modal-content">
                        <h2>Confirm Delete</h2>
                        <p>Are you sure you want to delete this record?</p>
                        <div class="modal-buttons">
                            <button onclick="confirmDelete()">Yes, Delete</button>
                            <button onclick="closeModal()">Cancel</button>
                        </div>
                    </div>
                </div>

                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search records..." onkeyup="searchRecords()">
                </div>

                <table>
                    <tr>
                        <th>ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Book Title</th>
                        <th>Borrow Date</th>
                        <th>Return Date</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($books as $book): ?>
                        <tr>
                            <td><?= htmlspecialchars($book['id']) ?></td>
                            <td><?= htmlspecialchars($book['first_name']) ?></td>
                            <td><?= htmlspecialchars($book['last_name']) ?></td>
                            <td><?= htmlspecialchars($book['email']) ?></td>
                            <td><?= htmlspecialchars($book['book_title']) ?></td>
                            <td><?= htmlspecialchars($book['borrow_date']) ?></td>
                            <td><?= htmlspecialchars($book['return_date']) ?></td>
                            <td class="actions">
                                <a class="edit-btn" href="edit-book.php?id=<?= $book['id'] ?>">Edit</a>
                                <a class="delete-btn" href="#" onclick="showDeleteModal(<?= $book['id'] ?>)">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>

        <div class="sidebar">
            <h2>Add New Borrowing</h2>
            <form method="post" onsubmit="return validateForm()">
                <label>
                    First Name
                    <input type="text" name="first_name" required>
                </label>
                <label>
                    Last Name
                    <input type="text" name="last_name" required>
                </label>
                <label>
                    Email
                    <input type="email" name="email" required>
                </label>
                <label>
                    Book Title
                    <input type="text" name="book_title" required>
                </label>
                <label>
                    Borrow Date
                    <input type="date" name="borrow_date" required>
                </label>
                <label>
                    Return Date
                    <input type="date" name="return_date" required>
                </label>
                <input type="submit" value="Add Record">
            </form>
        </div>
    </div>

    <script>
    function validateForm() {
        var fields = ['first_name', 'last_name', 'email', 'book_title', 'borrow_date', 'return_date'];
        var valid = true;
        
        fields.forEach(function(field) {
            var value = document.getElementsByName(field)[0].value;
            if (!value) {
                alert('Please fill in all fields');
                valid = false;
                return false;
            }
        });

        return valid;
    }

    function searchRecords() {
        var input = document.getElementById('searchInput');
        var filter = input.value.toLowerCase();
        var table = document.querySelector('table');
        var rows = table.getElementsByTagName('tr');

        for (var i = 1; i < rows.length; i++) {
            var showRow = false;
            var cells = rows[i].getElementsByTagName('td');
            
            for (var j = 0; j < cells.length; j++) {
                var cell = cells[j];
                if (cell) {
                    var text = cell.textContent || cell.innerText;
                    if (text.toLowerCase().indexOf(filter) > -1) {
                        showRow = true;
                        break;
                    }
                }
            }
            
            rows[i].style.display = showRow ? '' : 'none';
        }
    }

    let deleteId = null;

    function showDeleteModal(id) {
        deleteId = id;
        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    function confirmDelete() {
        if (deleteId) {
            window.location.href = 'library.php?delete=' + deleteId;
        }
    }

    
    window.onclick = function(event) {
        let modal = document.getElementById('deleteModal');
        if (event.target == modal) {
            closeModal();
        }
    }

    
    setTimeout(function() {
        var messageContainer = document.getElementById('messageContainer');
        if (messageContainer) {
            messageContainer.style.display = 'none';
        }
    }, 5000);
    </script>
</body>
</html>
