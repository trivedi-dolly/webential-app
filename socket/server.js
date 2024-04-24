const app = require("express")();
const http = require("http").Server(app);
const io = require('socket.io')(http, {
    cors: {
        origin: "http://localhost:8000",
        methods: ["GET", "POST"],
        allowedHeaders: ["my-custom-header"],
        credentials: true
    }
});
const mysql = require("mysql");
const path = require("path");
const config = require("dotenv").config({
    path: path.resolve(process.cwd(), "../.env"),
}).parsed;
const bodyParser = require("body-parser");
const axios = require("axios");


global.con;

app.use(bodyParser.urlencoded({ extended: true }));

app.get("*", function (req, res) {
    res.sendFile(path.join(__dirname, "index.html"));
});

const page = 1;
const limit = 20;
const offset = limit * page;
const start = offset - limit;

function handleDisconnect() {
    con = mysql.createConnection({
        host: config.DB_HOST,
        user: config.DB_USERNAME,
        password: config.DB_PASSWORD,
        database: config.DB_DATABASE,
    });

    con.connect(function (err) {
        if (err) {
            console.log("error when connecting to db:", err);
            setTimeout(handleDisconnect, 2000);
        } else {
            console.log("db connect successfully");
        }
    });

    con.on("error", function (err) {
        console.log("db error", err);
        if (err.code === "PROTOCOL_CONNECTION_LOST") {
            handleDisconnect();
        } else {
            console.log("err", err);
            throw err;
        }
    });
}

handleDisconnect();

io.sockets.on("connection", function (socket) {

    function getLastMessage(user) {
        console.log("User --> ", user)
        return new Promise(function (resolve, reject) {
            var sql = `SELECT * FROM messages where id = ${user.id} LIMIT 0,1`;
            con.query(sql, function (err, result) {
                if (err) {
                    reject(err);
                    return;
                }

                var data = JSON.parse(JSON.stringify(result));
                if (data.length > 0) {
                    user["last_message"] = data[0];
                } else {
                    user["last_message"] = null;
                }

                resolve(user);
            });
        });
    }
    function getConversationUsers(user_id) {
        return new Promise(function (resolve, reject) {
            var sql = `SELECT t1.*,u.id,u.first_name,u.last_name,u.email
        FROM messages AS t1
        INNER JOIN
        (
            SELECT
                LEAST(user_id, friend_id) AS user_id,
                GREATEST(user_id, friend_id) AS friend_id,
                MAX(id) AS max_id
            FROM messages
            GROUP BY
                LEAST(user_id, friend_id),
                GREATEST(user_id, friend_id)
        ) AS t2
            ON LEAST(t1.user_id, t1.friend_id) = t2.user_id AND
               GREATEST(t1.user_id, t1.friend_id) = t2.friend_id AND
               t1.id = t2.max_id
JOIN users as u
ON u.id =  CASE
     WHEN t1.user_id = ${user_id}
    THEN t1.friend_id
    WHEN t1.friend_id = ${user_id}
    THEN t1.user_id
END
WHERE t1.user_id = ${user_id} OR t1.friend_id = ${user_id}`;
            con.query(sql, async function (err, result) {
                if (err) {
                    reject(err);
                    return;
                }
                var promises = [];
                var details = [];
                var users = JSON.parse(JSON.stringify(result));
                details.forEach(function (item, index) {
                    if (item.created_at != null) {
                        details[index].created_at = item.created_at;
                    }

                    details[index].updated_at = item.updated_at;

                });
                console.log("details->", details)
                let userDetails = await Promise.all(users.map(
                    async (user) =>
                        await getLastMessage(user).then((res) => {
                            console.log("res->", res)
                            return res;
                        })
                ))
                return Promise.all(userDetails).then((results) => {
                    let mergedArray = results.concat(details);
                    resolve(
                        mergedArray.sort(function (a, b) {
                            return (
                                new Date(b.created_at).getTime() -
                                new Date(a.created_at).getTime()
                            );
                        })
                    );
                });
            });
        });
    }

    //get all the messages of specified conversation between two users
    socket.on("conversation_users", async (user_id, callback) => {
        try {
            await getConversationUsers(user_id)
                .then(function (data) {
                    console.log("data", data);
                    if (typeof callback == "function") {
                        callback(data);
                    }
                })
                .catch(function (error) {
                    throw error;
                });
        } catch (error) {
            console.error("Get Conserversation User Caught!", error);
        }
    });

    function messages(user_id, friend_id) {
        return new Promise(function (resolve, reject) {
            var sql = `
                SELECT msg.*, u.first_name AS user_name
                FROM messages AS msg
                INNER JOIN users AS u ON msg.user_id = u.id
                WHERE (msg.user_id = ? AND msg.friend_id = ?) OR (msg.user_id = ? AND msg.friend_id = ?)
                ORDER BY msg.id ASC`;
            con.query(sql, [user_id, friend_id, friend_id, user_id], function (err, result) {
                if (err) {
                    reject(err);
                    return;
                }
                resolve(result);
            });
        });
    }

    // api to get all the messages of specified conversation between two users
    socket.on("messages", async (user_id, friend_id, callback) => {
        try {
            await messages(user_id, friend_id)
                .then(function (data) {
                    if (typeof callback == "function") {
                        callback(data);
                    }
                })
                .catch(function (error) {
                    throw error;
                });
        } catch (error) {
            console.error("Get All Message Caught!", error);
        }
    });

    function message(data) {
        return new Promise(function (resolve, reject) {
            console.log("sender", data);
            var date = new Date().toISOString().slice(0, 19).replace("T", " ");
            var sql = `
                INSERT INTO messages (user_id, friend_id, body, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?)`;
            var values = [data.sender_id, data.receiver_id, data.message, date, date];

            con.query(sql, values, function (error, result) {
                if (error) {
                    reject(error);
                    return;
                }
                if (result) {
                    var messageId = result.insertId;
                    var sql1 = `
                        SELECT id, user_id, friend_id, created_at, body,
                        (
                            SELECT count(id) FROM messages 
                            WHERE friend_id = ? 
                        ) as unread_count
                        FROM messages WHERE id = ?`;
                    var values1 = [data.receiver_id, messageId];

                    con.query(sql1, values1, function (error, res) {
                        if (error) {
                            reject(error);
                            return;
                        } else {
                            socket.to("room_" + data.receiver_id).emit("get_message", res);
                            resolve(res);
                        }
                    });
                }
            });
        });
    }


    // api to send message
    socket.on("message", async (data, callback) => {
        try {
            await message(data)
                .then(async function (result) {
                    if (result) {
                        await messages(data.sender_id, data.receiver_id)
                            .then(function (data) {
                                if (typeof callback == "function") {
                                    callback(data);
                                }
                            })
                            .catch(function (error) {
                                throw error;
                            });
                    }
                })
                .catch(function (error) {
                    throw error;
                });
        } catch (error) {
            console.error("Send Message Caught!", error);
        }
    });


    // get user details
    function getUser(user_id) {
        return new Promise(function (resolve, reject) {
            var sql = `SELECT * FROM users WHERE id = ? LIMIT 1`;
            con.query(sql, [user_id], function (err, result) {
                if (err) {
                    reject(err);
                    return;
                }

                resolve(result);
            });
        });
    }

    socket.on("login", async (user_id, callback) => {
        try {
            var room = `room_${user_id}`;
            socket.join(room);

            //get user
            await getUser(user_id)
                .then(function (data) {
                    if (data.length > 0) {
                        let user = data[0];
                        if (typeof callback == "function") {
                            callback({
                                data: user,
                                message: `${user.first_name} has been logged in successfully`,
                            });
                        }
                        console.log(`${user.first_name} has been logged in successfully`);
                    }
                })
                .catch(function (error) {
                    throw error;
                });
        } catch (error) {
            console.error("Login Caught!", error);
        }
    });

    // user offline/logout status
    socket.on("logout", function (user_id, callback) {
        try {
            var room = `room_${user_id}`;
            socket.leave(room);

            setTimeout(function (argument) {
                socket.emit("disconnect");
                if (typeof callback == "function") {
                    callback({
                        message: "user has been logged out successfully",
                    });
                }
            }, 100);
        } catch (error) {
            console.error("Logout Caught!", error);
        }
    });

    // all events marked as closed
    socket.on("disconnect", function () {
        console.log("server is disconnected successfully");
    });
});

const port = 3000;
http.listen(port, function (err) {
    try {
        if (err) throw err;
        console.log(
            `App listening on http://localhost:3000`
        );
    } catch {
        console.error("Caught!", error);
    }
});
