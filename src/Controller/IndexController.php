<?php
namespace App\Controller;

use App\Entity\Geogroup;
use App\Entity\Geooption;
use App\Entity\Location;
use App\Entity\User;
use App\MyTrait\MyReferer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\DomCrawler\Crawler;


/**
 * Class IndexController
 * @package App\Controller
 */
class IndexController extends AbstractController
{
    use MyReferer;

    /**
     * @Route("/", name="base")
     */
    public function index(TranslatorInterface $translator) {

        $em = $this->getDoctrine()->getManager();
        $user = User::getCurrentUser($em);
        $group = $user->getActiveGroup();
        if($group === null) {
            $group = $this->getDoctrine()->getRepository(Geogroup::class)->findAll()[0];
            $user->setActiveGroup($group);
            $em->persist($user);
            $em->flush();
        }

        $options = $group->getOptions();

        return $this->render('index/index.html.twig', array(
            'group' => $group,
            'options' => $options,
            'user' => $user
        ));
    }

    /**
     * @Route("/join")
     */
    public function join() {

        $user = User::getCurrentUser($this->getDoctrine()->getManager());
        $user->addPoints(50, $this->getDoctrine()->getManager());

        return $this->render('geogroup/join.html.twig', array(
            'user' => $user
        ));
    }

    /**
     * @Route("/setlocale/{locale<.+>}")
     */
    public function setlocale($locale) {
        $this->get('session')->set('_locale', $locale);
        return $this->redirectBack();
    }

    /**
     * @Route("/importmundraub")
     */
    public function importmundraub() {

        $user = User::getCurrentUser($this->getDoctrine()->getManager());
        $em = $this->getDoctrine()->getManager();
        $json = file_get_contents("https://mundraub.org/cluster/plant?bbox=1.0107421875000002,49.15296965617042,22.104492187500004,52.6030475337285&zoom=20&cat=1,2,3,4,5,6,7,8,9,10,11,12,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37");
        $data = json_decode($json, true);

        $repository = $this->getDoctrine()
            ->getRepository(Geogroup::class);

        $group = $repository->findOneBy([
            'name' => 'Mundraub'
        ]);

        foreach ($data['features'] AS $loc) {
            $location = new Location();
            $location->setLtd($loc['pos'][0]);
            $location->setLgt($loc['pos'][1]);
            $location->setGeogroup($group);
            $location->setUser($user);
            $location->setStatus(1);
            $em->persist($location);
        }
        $em->flush();
    }

    /**
     * @Route("/impressum")
     */
    public function impressum() {

        $user = User::getCurrentUser($this->getDoctrine()->getManager());
        $user->addPoints(1, $this->getDoctrine()->getManager());

        return $this->render('index/impressum.html.twig', array(
            'user' => $user
        ));
    }

    /**
     * @Route("/info")
     */
    public function info() {

        $user = User::getCurrentUser($this->getDoctrine()->getManager());
        $user->addPoints(1, $this->getDoctrine()->getManager());

        return $this->render('index/info.html.twig', array(
            'user' => $user
        ));
    }

    /**
     * @Route("/removewaste", name="removewaste")
     */
    public function removeWaste() {

        $user = User::getCurrentUser($this->getDoctrine()->getManager());
        $user->addPoints(10, $this->getDoctrine()->getManager());

        return $this->render('index/removewaste.html.twig', array(
            'user' => $user,
        ));

    }

    public function getRootDir($dir = '') {
        return (__DIR__ . '/../../'.$dir);
    }

    /**
     * @Route("/convert123")
     */
    public function convertDbImagesToFiles() {

        $user = User::getCurrentUser($this->getDoctrine()->getManager());

        $locations = $this->getDoctrine()
            ->getRepository(Location::class)
            ->findAll();

        $r = [];
        foreach ($locations AS $location) {
            $r[] = $location->convertDBimageToFile($this->getRootDir('public/images/'));
        }

        return $this->render('index/showLocations.html.twig', array(
            'user' => $user,
            'locations' => $locations
        ));

    }

    protected function xml_to_array($root) {
        $result = array();

        if ($root->hasAttributes()) {
            $attrs = $root->attributes;
            foreach ($attrs as $attr) {
                $result['@attributes'][$attr->name] = $attr->value;
            }
        }

        if ($root->hasChildNodes()) {
            $children = $root->childNodes;
            if ($children->length == 1) {
                $child = $children->item(0);
                if ($child->nodeType == XML_TEXT_NODE) {
                    $result['_value'] = $child->nodeValue;
                    return count($result) == 1
                        ? $result['_value']
                        : $result;
                }
            }
            $groups = array();
            foreach ($children as $child) {
                if (!isset($result[$child->nodeName])) {
                    $result[$child->nodeName] = xml_to_array($child);
                } else {
                    if (!isset($groups[$child->nodeName])) {
                        $result[$child->nodeName] = array($result[$child->nodeName]);
                        $groups[$child->nodeName] = 1;
                    }
                    $result[$child->nodeName][] = xml_to_array($child);
                }
            }
        }

        return $result;
    }


    /**
     * @Route("/exportcsv")
     */
    public function exportcsv() {

        $nid =

        //$html = file_get_contents('https://mundraub.org/map?nid=25320#z=18&lat=50.549945186885&lng=11.725480556488');
        $html = file_get_contents('https://mundraub.org/node/25320');



        //$crawler = new Crawler($html);

        //$xml = new \DOMDocument();
        //$xml->loadXML($html);
        //$xmlArray = $this->xml_to_array($xml);
        //print_r($xmlArray);

        //dd($xmlArray);

        /*echo "<pre>";


        $crawler = new Crawler($html);
        $crawler = $crawler->filter('body .article-main > h2');

        foreach ($crawler as $domElement) {
            var_dump($domElement->nodeName);
            var_dump($domElement->nodeValue);
        }

        exit;*/

        $user = User::getCurrentUser($this->getDoctrine()->getManager());

        $locations = $this->getDoctrine()
            ->getRepository(Location::class)
            ->findAll();

        $r = [];
        $csv = "id,ltd,lgt,group,username/-hash\n";
        foreach ($locations AS $location) {
            $as = [];
            $as['id'] = $location->getId();
            $as['ltd'] = $location->getLtd();
            $as['lgt'] = $location->getLgt();
            $as['group'] = $location->getGeogroup()->getName();
            $as['user'] = !empty($location->getUser()->getName()) ? $location->getUser()->getName() : $location->getUser()->getHash();

            $csv .= implode(',', $as) . "\n";

            $r[] = $as;
        }

        echo "<pre>";
        echo $csv;
        exit;

        return $this->render('index/showLocations.html.twig', array(
            'user' => $user,
            'locations' => $locations
        ));

    }
}