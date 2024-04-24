@extends('layouts.app')

@section('content')
<style type="text/css">
    /* Chat container styles */
    .chat-container {
        width: 100%;
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #ccc;
        padding: 10px;
        margin-bottom: 20px;
    }

    /* Message styles */
    .message {
        background-color: #f1f0f0;
        padding: 10px;
        margin-bottom: 10px;
        border-radius: 5px;
    }

    /* Sender ID styles */
    .sender {
        font-weight: bold;
    }

    /* Message form styles */
    .message-form {
        display: flex;
        margin-bottom: 20px;
    }

    .message-input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px 0 0 5px;
    }

    .send-button {
        padding: 10px 20px;
        background-color: #007bff;
        color: #fff;
        border: none;
        border-radius: 0 5px 5px 0;
        cursor: pointer;
    }

    .send-button:hover {
        background-color: #0056b3;
    }
</style>
<h1>Chat with User</h1>
<div id="chat-messages" class="chat-container">
    <!-- Messages will be displayed here -->
</div>
<form id="message-form" class="message-form">
    <input type="text" id="message-input" class="message-input" placeholder="Type your message">
    <button type="submit" class="send-button">Send</button>
</form>

<script>
    const socket = io('http://localhost:3000');
        const urlParams = new URLSearchParams(window.location.search);
        const friendId = urlParams.get('friend_id');
        const userId = {{ auth()->user()->id }};

        socket.emit('login', userId, function(data) {
            console.log('login', data)
        });

        socket.emit('conversation_users', userId, function(data) {

            frendId = friendId;
            usrId = userId;
    
            socket.emit('messages', userId, friendId, function(messages) {
                console.log("Received messages:", messages);

                const messagesContainer = document.getElementById('chat-messages');
                messagesContainer.innerHTML = '';

                if (messages && messages.length > 0) {
                    messages.forEach(message => {
                        const messageElement = document.createElement('div');
                        messageElement.classList.add('message');
                        messageElement.innerHTML = `
                <p class="sender">${message.user_name}</p>
                <p>${message.body}</p>`;
                        messagesContainer.appendChild(messageElement);
                    });
                } else {
                    const noMessageElement = document.createElement('div');
                    noMessageElement.textContent = "No messages found.";
                    messagesContainer.appendChild(noMessageElement);
                }
            });

        });

        document.getElementById('message-form').addEventListener('submit', function(event) {
            event.preventDefault();
            const messageInput = document.getElementById('message-input');
            const message = messageInput.value.trim();
            if (message !== '') {
                socket.emit('message', {
                    sender_id: userId,
                    receiver_id: friendId,
                    message: message
                }, function(data) {
                    console.log('Message sent:', data);
                });
                messageInput.value = '';
            }
        });
</script>
@endsection