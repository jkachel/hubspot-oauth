<?php
    /**
     * HubSpot OAuth Client Test Mule
     *
     * This is a pretty simple test that will prompt you to authenticate into
     * your HubSpot portal, and then will pull the first page of contacts or
     * will tell you that it didn't work. Fill in your hub/portal ID and
     * client ID below, and then point the built-in PHP webserver at this. The
     * recommended command is this:
     *      $ php -S localhost:3333
     *
     * (If you're not using the built-in PHP web server, update myUri to be a
     * correct link for your setup.) Then, just go to
     *      http://localhost:3333/hubspot-oauth.php
     *
     * to run the test. You should immediately be punted into HubSpot, where it
     * will ask you to authorize your app (or ask you to log in first).
     *
     * @package jkachel/hubspot-oauth
     * @author James Kachel <james@jkachel.com>
     * @license MIT
     * @copyright 2016
     */

    require_once('vendor/autoload.php');

    use HubspotOauth\Authenticate\Authenticate;
    use Carbon\Carbon;

    /**
     * Specify your Portal ID (Hub ID) and Client ID here.
     */

    $myHubId = '';
    $myClientId = '';
    $myUri = 'http://localhost:3333/hubspot-oauth.php';

    function displayHeader() {
?><!DOCTYPE html>
<html>
<head>
    <title>Hubspot OAuth Tester</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" integrity="sha384-1q8mTJOASx8j1Au+a5WDVnPi2lkFfwwEAa8hDDdjZlpLegxhjVME1fgjWPGmkzs7" crossorigin="anonymous">
</head>
<body>
<nav class="navbar navbar-default">
    <div class="container-fluid">
        <!-- Brand and toggle get grouped for better mobile display -->
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
        </div>

        <!-- Collect the nav links, forms, and other content for toggling -->
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav">
                <li class="active"><a href="/hubspot-oauth.php">Hubspot OAuth Tester <span class="sr-only">(current)</span></a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
<?php
    }

    function displayFooter() {
?>
</div>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.12.3/jquery.min.map"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js" integrity="sha384-0mSbJDEHialfmuBBQP6A4Qrprq5OVfW37PRR3j5ELqxss1yVqOtnepnHVP9aJ7xS" crossorigin="anonymous"></script>
</body>
</html><?php
    }

    function displayError($e) {
?><div class="container-fluid">
    <div class="row">
        <div class="col-md-10 col-md-offset-1 text-center">
            <h1 class="text-danger">It Didn't Work</h1>

            <p>The error was: <?php echo $e; ?></p>
        </div>
    </div>
</div>
<?php
    }

    $auth = new Authenticate($myHubId, $myClientId);

    if(empty($_GET) && empty($_POST)) {
        $url = $auth->initiate($myUri, 'contacts-ro+offline');
        trigger_error('Redirecting to: '.$url, E_USER_NOTICE);
        header('Location: '.$url);
        return;
    }

    displayHeader();

    if(empty($_POST) && $auth->process($_GET)) {
        try {
            $resp = $auth->call('get', '/contacts/v1/lists/all/contacts/all');
            $data = $resp['response']['contacts'];
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-10 col-md-offset-1">
            <h1 class="text-success">It Worked!</h1>

            <table class="table table-compact table-bordered table-striped">
                <thead>
                <tr>
                    <th>First Name</th>
                    <th>Last Nmae</th>
                    <th>VID</th>
                    <th>Added At</th>
                </tr>
                </thead>
                <tbody>
                <?php for($i = 0; $i < count($data); $i++): ?>
                    <tr>
                        <td>
                            <?php echo $data[$i]['properties']['firstname']['value']; ?>
                        </td>
                        <td>
                            <?php echo $data[$i]['properties']['lastname']['value']; ?>
                        </td>
                        <td>
                            <?php echo $data[$i]['vid']; ?>
                        </td>
                        <td>
                            <?php echo Carbon::createFromTimestamp($data[$i]['addedAt'])->format('Y-m-d H:i:s'); ?>
                        </td>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>

            <p>Raw data:<br />
            <pre>
                <?php var_dump($data); ?>
                <?php var_dump($resp); ?>
            </pre></p>
        </div>
    </div>
</div>
<?php
        } catch(\Exception $e) {
            displayError($e);
        }
    } else {
        ?><div class="container-fluid">
        <div class="row">
            <div class="col-md-10 col-md-offset-1 text-center">
                <h1 class="text-danger">It Didn't Work</h1>
            </div>
        </div>
        </div>
        <?php

    }

    displayFooter();