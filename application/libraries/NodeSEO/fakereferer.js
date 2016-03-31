var scraper = require('./lib/scraper');

var uri = process.argv[2],
    options = {
        keyword: uri,
        num: 100,
        language: "en",
        tld: "com"
    },
    scrape = null,
    top = null;

scrape = new scraper.Scraper(options);

try {
    scrape.getSerps(uri, function(results) {
        if (results.length === 0) { // url not indexed by google
            console.log("error");
        } else {
            top = results[0];
            console.log(top.redirect);
        }
    });
} catch (ex) {
    process.exit(1);
}
