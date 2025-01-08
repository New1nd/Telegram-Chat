<template>
    <div class="chat-container">
        <!-- Левая часть: окно с сообщениями -->
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
                    </div>
                </div>
            </div>
        </div>

        <!-- Правая часть: список активных пользователей -->
        <div class="active-users">
            <ul>
                <li v-for="(u, i) in activeUsers" :key="i">{{ u.name }}</li>
            </ul>
        </div>

        <!-- Нижняя часть: поле ввода и индикатор печати -->
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
            isActive: false,     // кто-то печатает
            typingTimer: null,   // таймер для сброса "печатает"
            activeUsers: []      // список онлайн
        }
    },
    computed: {
        channel() {
            // Используем Echo.join, чтобы слушать события в комнате
            return Echo.join('room.' + this.room.id);
        }
    },
    mounted() {
        // Получаем историю сообщений
        this.getMessages();

        // Подключаемся к каналу
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
            // Событие PrivateChat - новые сообщения
            .listen('PrivateChat', ({ data }) => {
                this.getMessages();
                this.isActive = false;
            })
            // Событие whisper 'typing' - кто-то печатает
            .listenForWhisper('typing', (e) => {
                this.isActive = e;
                if (this.typingTimer) clearTimeout(this.typingTimer);
                this.typingTimer = setTimeout(() => {
                    this.isActive = false;
                }, 2000);
            });
    },
    methods: {
        // Получить сообщения с сервера
        getMessages() {
            axios
                .get('/getMessage/' + this.room.id)
                .then(response => {
                    // Предположим, что response.data.messages - это массив объектов { name, text }
                    this.messages = response.data.messages || [];

                    // Прокрутка в самый низ после обновления
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
    height: 600px;      /* фиксированная высота для примера */
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

/* Каждый "пузырёк" сообщения */
.message {
    display: block;
    max-width: 60%;
    margin-bottom: 10px;
    padding: 10px;
    border-radius: 6px;
    clear: both;
}

.message-body{
    border-radius: 10px;
    padding: 5px;
}

/* Имя автора сообщения */
.message-author {
    font-size: 0.9em;
    color: #666;
    margin-bottom: 4px;
}

/* Текст сообщения */
.message-text {
    font-size: 1em;
    line-height: 1.4em;
}

/* Мои сообщения (справа) */
.my-message {
    background-color: #cce5ff;
    margin-left: auto;
    text-align: right;
}

/* Сообщения собеседников (слева) */
.other-message {
    background-color: #f8f9fa;
    margin-right: auto;
    text-align: left;
}

/* Список активных пользователей (справа) */
.active-users {
    position: absolute;
    right: 0;
    top: 0;
    bottom: 50px; /* оставим место под поле ввода */
    width: 200px;
    border-left: 1px solid #ccc;
    padding: 10px;
    overflow-y: auto;
    background: #fff;
}

/* Управляющая панель внизу */
.chat-controls {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 200px; /* с учётом ширины .active-users */
    border-top: 1px solid #ccc;
    padding: 10px;
    background: #fff;
}

/* Индикатор кто печатает */
.typing-indicator {
    min-height: 20px;
    color: #999;
    font-style: italic;
}

/* Поле ввода сообщения */
.chat-input {
    width: 100%;
    margin-top: 8px;
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
