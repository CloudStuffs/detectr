var scraper = require('./scraper'),
    Rank = require('../models/rank'),
    mongoose = require('mongoose');

var Serp = (function (Rank, scraper, mongoose) {
    'use strict';
    var Serp = (function () {
        var fmtDate = function () {
            var date = new Date(),
                day = Number(date.getDate()),
                month = Number(date.getMonth()) + 1,
                today;

            if (day < 9) {
                day = "0" + String(day);
            }
            if (month < 9) {
                month = "0" + String(month);
            }

            today = date.getFullYear() + '-' + month + '-' + day;
            today = String(today);
            return today;
        }
        function Serp() {
            this.options = {
                language: "en",
                num: 100,
                tld: "com"
            };
            this.scraper = new scraper.Scraper(this.options);
            this.total = 0;
            this.counter = 0;
            this.today = fmtDate();
        }

        Serp.prototype = {
            processFile: function (contents) {
                var self = this;
                contents = JSON.parse(contents);
                self.total = contents.length;

                contents.forEach(function (el) {
                    Rank.find({
                        keyword_id: Number(el.keyword_id),
                        user_id: Number(el.user_id),
                        created: self.today
                    }, function (err, rank) {
                        if (err) {
                            throw err;
                        }

                        // no record found for today
                        if (rank.length === 0) {
                            self._findRank(el);
                            console.log("No record for " + el.keyword + " on date => " + self.today);
                        } else {
                            self.counter++;
                            if (self.counter === self.total) {
                                mongoose.disconnect();
                            }
                            console.log("Record already saved");
                        }
                    });
                });
            },
            _findRank: function (keyword) {
                var self = this,
                    top;
                // set keyword before scraping
                self.scraper.options.keyword = keyword.keyword;
                try {
                    self.scraper.getSerps(keyword.link, function (results) {
                        self.counter++;
                        if (results.length === 0) {
                            self._saveRank(-1, keyword);
                            return;
                        }

                        top = results[0];   // we are only storing the first result in db
                        self._saveRank(top.position, keyword);
                    });
                } catch (ex) {
                    self.counter++;
                    self._saveRank(-2, keyword);
                    console(ex);
                }
            },
            _saveRank: function (rank, keyword) {
                var self = this;
                var r = new Rank({
                    keyword_id: Number(keyword.keyword_id),
                    position: Number(rank),
                    created: self.today,
                    user_id: Number(keyword.user_id),
                    live: true
                });

                r.save(function (err) {
                    if (err) {
                        throw err;
                    }

                    console.log('Rank saved');
                    // disconnect if all the records are saved
                    if (self.counter === self.total) {
                        mongoose.disconnect();
                    }
                });
            }
        };

        return new Serp();
    }());

    return Serp;
}(Rank, scraper, mongoose));

exports.Serp = Serp;
