<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Chat UI</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss/dist/tailwind.min.css">
    <script src="https://cdn.jsdelivr.net/npm/socket.io-client@4.7.2/dist/socket.io.min.js"></script>
</head>

<body>
    <div class="container mx-auto py-8">
        <div class="flex flex-col border border-gray-300 rounded-md">
            <div class="px-4 py-2 bg-gray-200">
                <h2 class="text-lg font-bold">Chat Room</h2>
            </div>
            <div class="p-4 flex flex-col h-64 overflow-y-auto">
            </div>
            <div class="flex items-center px-4 py-2 border-t border-gray-300">
                <input type="text" class="flex-grow mr-2 focus:outline-none" id="message-input" placeholder="Type your message...">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md" id="send-button">Send</button>
            </div>
        </div>
    </div>
    <script>
        // Connect to the WebSocket server and push msg in the ui using js websocket
        const socket = new WebSocket('wss://sock.mdriaz.com/ws');
        const messageInput = document.getElementById('message-input');
        const sendButton = document.getElementById('send-button');
        const chatBox = document.querySelector('.flex.flex-col.h-64.overflow-y-auto');
        socket.onopen = () => {
            console.log('Connected to the server');
        };

        socket.onmessage = (event) => {
            const message = JSON.parse(event.data);

            if (message.type === 'message') {
                const messageElement = document.createElement('div');
                messageElement.textContent = `${message.user}: ${message.content}`;
                chatBox.appendChild(messageElement);
                chatBox.scrollTop = chatBox.scrollHeight;
            } else {
                console.error('Unknown message type:', message.type);
            }
        };

        sendButton.addEventListener('click', () => {
            const message = messageInput.value;
            socket.send(JSON.stringify({
                type: 'message',
                user: Math.random(),
                content: message
            }));
            messageInput.value = '';
        });

        socket.onclose = () => {
            console.log('Disconnected from the server');
        };

        socket.onerror = (error) => {
            console.error('Something went wrong', error);
        };
    </script>
</body>

</html>