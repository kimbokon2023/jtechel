<!DOCTYPE html>
<html>
<head>
    <title>WebSocket Client</title>
</head>
<body>
    <input type="text" id="messageInput" placeholder="Enter a message">
    <button onclick="sendMessage()">Send</button>

    <script>
    const socket = new WebSocket('ws://j-techel.co.kr:8080');

    socket.onopen = function(event) {
        console.log('WebSocket connection established');
    };

    socket.onmessage = function(event) {
        console.log('Received message: ' + event.data);
    };

    socket.onclose = function(event) {
        console.log('WebSocket connection closed');
    };

    function sendMessage() {
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value;

        socket.send(message);
        messageInput.value = '';
    }
    </script>
</body>
</html>
