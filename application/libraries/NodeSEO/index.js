var Serp = require('./lib/serp').Serp,
    fs = require('fs'),
    mongoose = require('mongoose');

if (typeof process.argv[2] === "undefined") {
	var file = __dirname + '/../../../logs/serpRank.json';
} else {
	var file = process.argv[2];
}

mongoose.connect('mongodb://localhost:27017/stats');

fs.readFile(file, 'utf-8', function (err, contents) {
    "use strict";
    if (err) {
        console.log(err);
        return;
    }
    Serp.processFile(contents);
});
