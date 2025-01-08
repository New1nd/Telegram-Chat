<template>
    <div class="chat-container">
        <div class="chat-window" ref="chatWindow">
            <div class="chat-window__body">
                <div
                    class="message"
                    v-for="(msg, index) in messages"
                    :key="index"
                >
                    <div class="message-body"
                         :class="{
                          'my-message': msg.name === user.name,
                          'other-message': msg.name !== user.name
                        }"
                    >
                        <div class="message-author">{{ msg.name }}</div>
                        <div class="message-text">{{ msg.message }}</div>
                        <div class="reaction-buttons">
                            <button @click="setReaction(msg.id, 'like')">
                                👍
                            </button>
                            <button @click="setReaction(msg.id, 'dislike')">
                                👎
                            </button>
                            <button @click="setReaction(msg.id, 'heart')">
                                ❤️
                            </button>
                        </div>

                        <ul class="reactions-list">
                            <li v-for="(r, i) in msg.reactions" :key="i">
                                {{ r.user_name }} →
                                <span v-if="r.reaction === 'like'">👍</span>
                                <span v-else-if="r.reaction === 'dislike'">👎</span>
                                <span v-else-if="r.reaction === 'heart'">❤️</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="active-users">
            <ul>
                <li v-for="(u, i) in activeUsers" :key="i">{{ u.name }}</li>
            </ul>
        </div>

        <div class="chat-controls">
            <div class="typing-indicator">
                <span v-if="isActive">{{ isActive.name }} набирает сообщение...</span>
            </div>
            <input
                type="text"
                class="form-control chat-input"
                v-model="textMessage"
                @keyup.enter="sendMessage"
                @keydown="actionUser"
                placeholder="Введите сообщение..."
            />
        </div>
    </div>
</template>

<script>
export default {
    props: ['room', 'user'],
    data() {
        return {
            messages: [],
            name: '',
            textMessage: '',
            isActive: false,
            typingTimer: null,
            activeUsers: []
        }
    },
    computed: {
        channel() {
            return Echo.join('room.' + this.room.id);
        }
    },
    mounted() {
        this.getMessages();

        this.channel
            .here((users) => {
                this.activeUsers = users;
            })
            .joining((user) => {
                this.activeUsers.push(user);
            })
            .leaving((user) => {
                this.activeUsers.splice(this.activeUsers.indexOf(user), 1);
            })
            .listen('PrivateChat', ({ data }) => {
                this.getMessages();
                this.isActive = false;
            })
            .listen('ReactionMessage', (payload) => {
                // payload = { messageId, roomId, reactions: [{user_id, user_name, reaction}, ...]}
                // Находим сообщение и обновляем reactions
                const msg = this.messages.find(m => m.id === payload.message_id);
                if (msg) {
                    console.log(payload);

                    msg.reactions = payload.reactions;
                }
            })
            .listenForWhisper('typing', (e) => {
                this.isActive = e;
                if (this.typingTimer) clearTimeout(this.typingTimer);
                this.typingTimer = setTimeout(() => {
                    this.isActive = false;
                }, 2000);
            });
    },
    methods: {
        countReactions(message, type) {
            return message.reactions.filter(r => r.reaction === type).length;
        },
        getMessages() {
            axios
                .get('/getMessage/' + this.room.id)
                .then(response => {
                    // Предположим, что response.data.messages - это массив объектов { name, text }
                    this.messages = response.data.messages || [];


                    this.$nextTick(() => {
                        const chatWindow = this.$refs.chatWindow;
                        if (chatWindow) {
                            chatWindow.scrollTop = chatWindow.scrollHeight;
                        }
                    });
                });
        },
        // Отправляем сообщение на сервер
        sendMessage() {
            if (!this.textMessage.trim()) return; // если пустое - не отправляем

            axios.post('/messages', {
                name: this.user.name,
                message: this.textMessage,
                room_id: this.room.id
            });

            // Добавляем сообщение локально, чтобы сразу отобразилось
            this.messages.push({
                name: this.user.name,
                message: this.textMessage
            });

            console.log(this.user.name);
            console.log(this.textMessage);

            // Очищаем поле
            this.textMessage = '';

            // this.getMessages()

            // Прокрутка вниз
            this.$nextTick(() => {
                const chatWindow = this.$refs.chatWindow;
                if (chatWindow) {
                    chatWindow.scrollTop = chatWindow.scrollHeight;
                }
            });
        },
        // Отправляем whisper, что мы печатаем
        actionUser() {
            this.channel.whisper('typing', {
                name: this.user.name
            });
        },
        setReaction(msg, reactionType) {
            axios
                .post('/messages/reaction', {
                    message_id: msg,
                    user_id: this.user.id,
                    reaction: reactionType
                })
                .then(() => {
                    // Можно локально поменять, если хотим мгновенно показать
                    // (или можно дождаться только события ReactionUpdated)
                    // Пример локального обновления:
                    // 1. Найти реакцию текущего пользователя в msg.reactions
                    const existingIndex = msg.reactions.findIndex(r => r.user_id === this.user.id);
                    if (existingIndex !== -1) {
                        // Обновляем
                        msg.reactions[existingIndex].reaction = reactionType;
                    } else {
                        // Добавляем новую
                        msg.reactions.push({
                            user_id: this.user.id,
                            user_name: this.user.name,
                            reaction: reactionType
                        });
                    }
                });
        }
    }
}
</script>

<style scoped>
/* Общий контейнер чата */
.chat-container {
    display: flex;
    flex-direction: column;
    width: 100%;
    max-width: 1200px;
    height: 100px;      /* фиксированная высота для примера */
    margin: 0 auto;
    border: 1px solid #ccc;
    position: relative;
}

/* Окно с сообщениями (левая основная часть) */
.chat-window {
    flex: 1;
    padding: 10px;
    overflow-y: auto;
    border-bottom: 1px solid #ccc;
    border-right: 1px solid #ccc;
    /* Можно добавить ширину, если хотите вынести список пользователей сбоку:
       width: calc(100% - 200px);
       Но здесь мы можем сделать через position: relative в контейнере
    */
}

.message {
    display: block;
    max-width: 100%;
    margin-bottom: 10px;
    padding: 10px;
    border-radius: 6px;
    clear: both;
}

.message-body{
    border-radius: 10px;
    padding: 5px;
}

.message-author {
    font-size: 0.9em;
    color: #666;
    margin-bottom: 4px;
}

.message-text {
    font-size: 1em;
    line-height: 1.4em;
}

.my-message {
    background-color: #cce5ff;
    margin-left: auto;
    text-align: right;
    padding-right: 10px;
    display: inline-block;
    float: right;
}

.other-message {
    background-color: #efefef;
    margin-right: auto;
    text-align: left;
    padding-left: 10px;
    display: inline-block;
    float: left;
}

.chat-window__body{
    width: 70%;
}

.active-users {
    position: absolute;
    right: 0;
    top: 0;
    bottom: 50px;
    width: 30%;
    border-left: 1px solid #ccc;
    padding: 10px;
    overflow-y: auto;
    background: #fff;
}

.chat-controls {
    position: absolute;
    bottom: 0;
    left: 0;
    //right: 200px; /* с учётом ширины .active-users */
    border-top: 1px solid #ccc;
    padding: 10px;
    background: #fff;
    width: 100%;
}

.typing-indicator {
    min-height: 20px;
    color: #999;
    font-style: italic;
}

.chat-input {
    width: 100%;
    margin-top: 8px;
}

/* Кнопки реакций */
.reaction-buttons {
    display: flex;
    gap: 5px;
    margin-bottom: 4px;
}

.reaction-buttons button {
    cursor: pointer;
    border: 1px solid #ccc;
    background: #fff;
    font-size: 16px;
    padding: 3px 8px;
    border-radius: 4px;
}

/* Текущая реакция */
.current-reaction {
    font-size: 14px;
    color: #555;
}
</style>


<!--<template>-->
<!--    <div class="container">-->
<!--        <hr>-->
<!--        <div class="row">-->
<!--            <div class="col-sm-8">-->
<!--                <textarea class="form-control" id="chat-window" rows="30" readonly="">{{ messages.join('\n') }}</textarea>-->
<!--                <hr>-->
<!--                <input type="text" class="form-control" v-model="textMessage" @keyup.enter="sendMessage" @keydown="actionUser">-->
<!--                <span v-if="isActive">{{ isActive.name }} набирает сообщение...</span>-->
<!--            </div>-->
<!--            <div class="col-sm-4">-->
<!--                <ul>-->
<!--                    <li v-for="user in activeUsers">{{ user.name }}</li>-->
<!--                </ul>-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
<!--</template>-->

<!--<script>-->
<!--export default {-->
<!--    props: ['room', 'user'],-->
<!--    data() {-->
<!--        return {-->
<!--            messages: [],-->
<!--            messagesAxios: '',-->
<!--            textMessage: '',-->
<!--            isActive: false,-->
<!--            typingTimer: false,-->
<!--            activeUsers: []-->
<!--        }-->
<!--    },-->
<!--    computed: {-->
<!--        channel() {-->
<!--            return Echo.join('room.' + this.room.id);-->
<!--        }-->
<!--    },-->
<!--    mounted() {-->

<!--        var textarea = document.getElementById('chat-window');-->
<!--        setInterval(function(){-->
<!--            textarea.scrollTop = textarea.scrollHeight;-->
<!--        }, 500);-->

<!--        this.getMessages()-->

<!--        this.channel-->
<!--            .here((users) => {-->
<!--                this.activeUsers = users;-->
<!--            })-->
<!--            .joining((user) => {-->
<!--                this.activeUsers.push(user);-->
<!--            })-->
<!--            .leaving((user) => {-->
<!--                this.activeUsers.splice(this.activeUsers.indexOf(user), 1);-->
<!--            })-->
<!--            .listen('PrivateChat', ({data}) => {-->
<!--                console.log(data);-->
<!--                this.getMessages();-->
<!--                this.isActive = false;-->
<!--            })-->
<!--            .listenForWhisper('typing', (e) => {-->
<!--                this.isActive = e;-->

<!--                if(this.typingTimer) clearTimeout(this.typingTimer);-->

<!--                this.typingTimer = setTimeout(() => {-->
<!--                    this.isActive = false;-->
<!--                }, 2000);-->
<!--            });-->

<!--    },-->
<!--    methods: {-->
<!--        getMessages() {-->
<!--            axios-->
<!--                .get('/getMessage/' + this.room.id)-->
<!--                .then(response => {-->
<!--                    this.messages = response.data.messages;-->
<!--                });-->
<!--        },-->
<!--        sendMessage() {-->
<!--            axios.post('/messages', {name: this.user.name, message: this.textMessage, room_id: this.room.id });-->

<!--            this.messages.push(this.user.name + ': ' + this.textMessage);-->

<!--            this.textMessage = '';-->
<!--        },-->
<!--        actionUser() {-->
<!--            this.channel-->
<!--                .whisper('typing', {-->
<!--                    name: this.user.name-->
<!--                });-->
<!--        }-->
<!--    }-->
<!--}-->
<!--</script>-->
