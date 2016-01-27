var request = require('request'),
    cheerio = require('cheerio');

var results = [];

/*
 *PARAMETERS : {keyword : '', language : '', num : '', tld: 'com'}
 */
function Scraper(options) {
    "use strict";
    this.searchEngine = 'google';

    this.options = options;
    this.options.tld = (options.tld || "com");
    this.options.ua = options.ua || 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.153 Safari/537.36';
}

/*
 * Public Method
 * Scrapes the given search engine for the keyword and
 * return the results
 */
Scraper.prototype.getSerps = function (link, callback) {
    "use strict";
    var self = this;

    self._query(function (body) {
        self._parse(link, body, function () {
            callback(results);
        });
    });

};

/*
 * @private
 * Builds the search url + query string
 */
Scraper.prototype._searchURL = function () {
    "use strict";
    switch (this.searchEngine) {
    case 'google':
        return {
            url: 'http://www.google.' + this.options.tld + '/search',
            qs: {
                hl: this.options.language,
                num: this.options.num,
                q: this.options.keyword
            }
        };

    case 'bing':
        break;

    default:
        return {
            url: 'http://www.google.' + this.options.tld + '/search',
            qs: {
                hl: this.options.language,
                num: this.options.num,
                q: this.options.keyword
            }
        };
    }
};

/*
 * @private
 * Sends get request to google to search for the keyword
 */
Scraper.prototype._query = function (callback) {
    "use strict";
    var se = this._searchURL(),
        reqOpts = {
            url: se.url,
            method: 'GET',
            qs: se.qs,
            headers: {
                'Host': 'www.google.' + this.options.tld,
                'Connection': 'keep-alive',
                'Cache-Control': 'max-age=0',
                'User-Agent': this.options.ua,
                'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Referer': 'www.google' + this.options.tld,
                'Accept-Language': this.options.language,
                'Accept-Charset': 'ISO-8859-1,utf-8;q=0.7,*;q=0.7'
            }
        };
    request(reqOpts, function (error, response, body) {
        if (error) {
            throw (error);
        }
        if (response.statusCode === 200) {
            callback(body);
        } else {
            throw new Error("Something went wrong! Status Code: " + response.statusCode);
        }
    });
};

/*
 * @private
 * Extracts the results from the page
 */
Scraper.prototype._parse = function (website, html, callback) {
    "use strict";
    results = [];
    var $ = cheerio.load(html),
        links = $('h3.r a'),
        i = 0,
        href, title, redirect, push;

    if ($(links).length === 0) {
        throw new Error("Captcha Exception");
    }

    $(links).each(function (i, link) {
        ++i;
        href = $(link).attr('href');
        title = $(link).html();
        redirect = $(link).attr('onmousedown');
        push = false;

        if (website) {
            if (href.match(website)) {
                push = true;
            }
        } else {
            push = true;
        }

        if (push) {
            results.push({
                position: i,
                href: href,
                title: title,
                redirect: redirect
            });
        }
    });
    callback();
};

exports.Scraper = Scraper;
