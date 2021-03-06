<!DOCTYPE html>
<html>
<title>Error 500!</title>
<style>
body {
    text-align: center;
    padding: 150px;
}

h1 {
    font-size: 50px;
}

body {
    font: 20px Helvetica, sans-serif;
    color: #333;
}

article {
    display: block;
    text-align: left;
    width: 650px;
    margin: 0 auto;
}

a {
    color: #dc8100;
    text-decoration: none;
}

a:hover {
    color: #333;
    text-decoration: none;
}
</style>

<body>
    <article>
        <h1>Error 500!</h1>
        <div>
            <p>Something went wrong. If you need to you can always <a href="mailto:info@trafficmonitor.ca">Contact US</a>! or <a href="/">Home</a></p>
            <p>&mdash; TrafficMonitor Team</p>
        </div>
    </article>
    <?php if (DEBUG): ?>
        <pre><?php print_r($e); ?></pre>
    <?php endif; ?>
</body>

</html>
