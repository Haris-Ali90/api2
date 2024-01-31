var app = require('express')();
var http = require('http').Server(app);

var Redis = require('ioredis');
var redis = new Redis();

// app.set('port', (process.env.PORT || 3000));
const io = require("socket.io")(http, {
    cors: {
        origin:'*',
        withCredentials: true,
        methods: ["GET", "POST"],
        transports: ["websocket", "polling"],
    },
    allowEIO3: true,
});

redis.subscribe('test-channel', function(err, count) {
});
redis.on('message', function(channel, message) {
    console.log(channel, message);
    // message = JSON.parse(message);
    io.sockets.emit(channel, {
            hubId: 17,
            message: 'Your order has been dropped',
            title: 'toronto hub',
        });
    // io.emit(channel, {
    //     hubId: 17,
    //     message: 'Your order has been dropped',
    //     title: 'toronto hub',
    // });
});
http.listen(3000, function(){
    console.log('Listening on Port 3000');
});


// let url = 'http://localhost:3000/';
//
// var socket = io.connect(url, {
//     secure: true // for SSL
//
// });
//
// socket.emit('new_notification', {
//     hubId: 17,
//     message: 'Your order has been dropped',
//     title: 'toronto hub',
// });
//
// socket.on('show_notification', function(data){
//     console.log(data)
// });
