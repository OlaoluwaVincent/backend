<?php
error_reporting(E_ALL);
require_once __DIR__ . "/../vendor/autoload.php";

use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;

Flight::map(
    'notFound',
    function () {
        if (file_exists(__DIR__ . '/index.html')) {
            echo file_get_contents(__DIR__ . '/index.html');
        } else {
            Flight::halt(404);
        }
    }
);

Flight::route(
    '/',
    function () {
        if (file_exists(__DIR__ . '/index.html')) {
            echo file_get_contents(__DIR__ . '/index.html');
        } else {
            Flight::halt(500);
        }
    }
);

Flight::route(
    '/api/predict',
    function () {
        if (empty($_FILES["image"])) {
            Flight::json(["error" => 'Image not sent'], 400);
            Flight::stop();
            return;
        }

        // reject images larger than 5MB
        if ($_FILES["image"]["size"] > 5242880) {
            Flight::json(["error" => 'Image too large'], 400);
            Flight::stop();
            return;
        }

        switch ($_FILES["image"]["type"]) {
            case 'image/png':
                $function = "imagecreatefrompng";
                $_FILES["image"]["extension"] = ".png";
                break;
            case 'image/jpeg':
                $function = "imagecreatefromjpeg";
                $_FILES["image"]["extension"] = ".jpeg";
                break;
            default:
                Flight::json(["error" => 'Invalid image type'], 415);
                Flight::stop();
                return;
                break;
        }

        $samples = [[$function($_FILES["image"]["tmp_name"])]];
        $dataset = new Unlabeled($samples);
        $estimator = PersistentModel::load(new Filesystem(__DIR__ . '/../cifar10.rbx'));
        $predictions = $estimator->predict($dataset);
        Flight::json(["prediction" => $predictions[0]]);
        move_uploaded_file(
            $_FILES["image"]["tmp_name"],
            __DIR__ . '/../uploaded/' . uniqid($predictions[0] . "-") . $_FILES["image"]["extension"]
        );
    }
);
Flight::start();
