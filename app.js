"use strict";

const WebSocket = require('ws');
const Redis = require("redis");
const wss = new WebSocket.Server({port: 8088});
var redisClient = Redis.createClient();

wss.on('connection', function connection(ws) {
    ws.on('message', function incoming(message) {
        redisClient.lpush("commands", message);
    });
});

wss.broadcast = function broadcast(data) {
    wss.clients.forEach(function each(client) {
        if (client.readyState === WebSocket.OPEN) {
            client.send(data);
        }
    });
};

setInterval(function () {
    redisClient.get("galaxy-simple-json-Andromeda", function (err, value) {
        if (err) throw (err);
        wss.broadcast(value);
    });

}, 100);


