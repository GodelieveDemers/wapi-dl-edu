import * as wa from "./server/whatsapp.js";
import fs from "fs";
import { db } from './server/database/index.js';
import { init as specsInit } from './server/lib/specs.js';
import 'dotenv/config';
import lib from "./server/lib/index.js";
import { setIO } from "./server/chat.js";
globalThis.log = lib.log;

import express from "express";
const app = express();
import http from "http";
const server = http.createServer(app);

import { Server } from "socket.io";
const io = new Server(server, {
  pingInterval: 25000,
  pingTimeout: 10000,
});

const port = process.env.PORT_NODE;
wa.setSocketIO(io);

app.use((req, res, next) => {
  res.set("Cache-Control", "no-store");
  req.io = io;
  next();
});

import bodyParser from "body-parser";

app.use(
  bodyParser.urlencoded({
    extended: false,
    limit: "50mb",
    parameterLimit: 100000,
  })
);

app.use(bodyParser.json());
app.use(express.static("src/public"));

import router from "./server/router/index.js";
app.use(router);

setIO(io);

io.on('connection', socket => {
	console.log("A user connected");
	socket.on('specs', () => {
		specsInit(socket);
	});
	socket.on('StartConnection', data => wa.connectToWhatsApp(data, io));
	socket.on('ConnectViaCode', data => wa.connectToWhatsApp(data, io, true));
	socket.on('LogoutDevice', device => wa.deleteCredentials(device, io));
	socket.on('disconnect', () => console.log('A user disconnected:', socket.id));
});

server.listen(port, () => {
    console.log(`Server running and listening on port: ${port}`);
});

db.query("SELECT * FROM devices WHERE status = 'Connected'", (err, results) => {
  if (err) {
    console.error('Error executing query:', err);
  }
  results.forEach(row => {
    const number = row.body;
    if (/^\d+$/.test(number)) {
      wa.connectToWhatsApp(number);
    }
  });
});
