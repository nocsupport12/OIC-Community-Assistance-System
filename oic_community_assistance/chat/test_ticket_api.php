<?php
// chat/test_ticket_api.php
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Ticket API</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h2>Test Ticket API Connection</h2>
    
    <?php
    // Test database connection
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'power2connect';
    
    echo "<h3>1. Testing Database Connection:</h3>";
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        echo "<p class='error'>❌ Connection failed: " . $conn->connect_error . "</p>";
    } else {
        echo "<p class='success'>✅ Database connected successfully!</p>";
        
        // Check if call_requests table exists
        $result = $conn->query("SHOW TABLES LIKE 'call_requests'");
        if ($result->num_rows > 0) {
            echo "<p class='success'>✅ Table 'call_requests' exists</p>";
            
            // Count total tickets
            $count = $conn->query("SELECT COUNT(*) as total FROM call_requests")->fetch_assoc();
            echo "<p>📊 Total tickets in database: " . $count['total'] . "</p>";
            
            // Show sample tickets
            echo "<h3>2. Sample Tickets from Database:</h3>";
            $sample = $conn->query("SELECT id, name, mobile_number, status, requested_at FROM call_requests ORDER BY id DESC LIMIT 5");
            
            if ($sample->num_rows > 0) {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Ticket #</th><th>Name</th><th>Mobile</th><th>Status</th><th>Date</th></tr>";
                while($row = $sample->fetch_assoc()) {
                    $ticketNum = '#' . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
                    echo "<tr>";
                    echo "<td>" . $row['id'] . "</td>";
                    echo "<td><strong>" . $ticketNum . "</strong></td>";
                    echo "<td>" . ($row['name'] ?? 'N/A') . "</td>";
                    echo "<td>" . $row['mobile_number'] . "</td>";
                    echo "<td>" . $row['status'] . "</td>";
                    echo "<td>" . $row['requested_at'] . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>No tickets found in call_requests table</p>";
            }
        } else {
            echo "<p class='error'>❌ Table 'call_requests' does not exist!</p>";
        }
        
        $conn->close();
    }
    ?>
    
    <h3>3. Test API Endpoint:</h3>
    <div>
        <p>Enter a ticket number and phone number to test:</p>
        <input type="text" id="testTicket" placeholder="e.g., 18 or #018" value="18">
        <input type="text" id="testPhone" placeholder="Mobile number" value="09858962918">
        <button onclick="testAPI()">Test API</button>
    </div>
    <pre id="apiResult">Waiting for test...</pre>
    
    <script>
    function testAPI() {
        let ticket = document.getElementById('testTicket').value.trim();
        const phone = document.getElementById('testPhone').value.trim();
        
        // Add # if not present
        if (!ticket.startsWith('#')) {
            ticket = '#' + ticket;
        }
        
        document.getElementById('apiResult').textContent = 'Testing...';
        
        fetch('get_ticket_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                ticket_number: ticket,
                phone: phone
            })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('apiResult').textContent = JSON.stringify(data, null, 2);
        })
        .catch(error => {
            document.getElementById('apiResult').textContent = 'Error: ' + error;
        });
    }
    </script>
</body>
</html>