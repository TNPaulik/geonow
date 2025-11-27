<?php

namespace App\GlobalClass;

use App\Entity\Location;
use App\Form\Location1Type;
use App\Repository\LocationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use App\Entity\Geooption;

class TnpImage extends Controller
{
    public static function autoRotate($original_file, $destination_file=NULL)
    {

        $original_extension = strtolower(pathinfo($original_file, PATHINFO_EXTENSION));
        if (isset($destination_file) and $destination_file != '') {
            $destination_extension = strtolower(pathinfo($destination_file, PATHINFO_EXTENSION));
        }

        // try to auto-rotate image by gd if needed (before editing it)
        // by imagemagik it has an easy option
        if (function_exists("exif_read_data")) {

            try {
                $exif_data = exif_read_data($original_file);
            } catch (\ErrorException $e) {
                return false;
            }

            $exif_orientation = isset($exif_data['Orientation']) ? $exif_data['Orientation'] : 0;

            // value 1 = normal ?! skip it ?!

            if (true or $exif_orientation == '3' or $exif_orientation == '6' or $exif_orientation == '8') {

                $new_angle[3] = 180;
                $new_angle[6] = -90;
                $new_angle[8] = 90;

                if (!isset($new_angle[$exif_orientation]))
                    $new_angle[$exif_orientation] = 0;

                // load the image
                if ($original_extension == "jpg" or $original_extension == "jpeg") {
                    $original_image = imagecreatefromjpeg($original_file);
                }
                if ($original_extension == "gif") {
                    $original_image = imagecreatefromgif($original_file);
                }
                if ($original_extension == "png") {
                    $original_image = imagecreatefrompng($original_file);
                }

                $rotated_image = imagerotate($original_image, $new_angle[$exif_orientation], 0);

                // if no destination file is set, then show the image
                if (!$destination_file) {
                    header('Content-type: image/jpeg');
                    imagejpeg($rotated_image, NULL, 100);
                }

                // save the smaller image FILE if destination file given
                if ($destination_extension == "jpg" or $destination_extension == "jpeg") {
                    imagejpeg($rotated_image, $destination_file, 100);
                }
                if ($destination_extension == "gif") {
                    imagegif($rotated_image, $destination_file);
                }
                if ($destination_extension == "png") {
                    imagepng($rotated_image, $destination_file, 9);
                }

                imagedestroy($original_image);
                imagedestroy($rotated_image);

            }
        }
        return true;
    }
}