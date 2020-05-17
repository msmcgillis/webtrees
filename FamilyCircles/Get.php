<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2019 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace FamilyCircles;

use function response;
use Fisharebest\Webtrees\Auth;
use Fisharebest\Webtrees\Individual;
use Fisharebest\Webtrees\Family;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The control panel shows a summary of the site and links to admin functions.
 */
class Get implements RequestHandlerInterface
{
    /**
     * ControlPanel constructor.
     *
     */
    public function __construct() {
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        $user = $request->getAttribute('user');
        $id = $request->getAttribute('id');
        $m = array_keys($request->getAttributes());

        $params = $request->getQueryParams();
        $search = $params['search'] ?? '';

        $item = $this->getItem($request,$id,$tree);
        $headers = ['Content-Type' => 'application/json',
                    'Access-Control-Allow-Origin' => '*'];
        $x = response(json_encode($item['body']),$item['code'],$headers);
        return $x;
    }

    private function getItem($request,$id,$tree): array
    {
      $result = [];
      if ($id[0] == "I") {
        $result['code']=200;
        $result['body']=$this->getIndividual($request,$id,$tree);
      } elseif ($id[0] = "F") {
        $result['code']=200;
        $result['body']=$this->getFamily($request,$id,$tree);
      } else {
        $result['code']=400;
        $result['body']=[ "code" => 400,
                          "error" => "id '".$id."' not supported"];
      }
      return $result;
    }

    private function getIndividual($request,$id,$tree): array {
      $result = [];
      $individual = Individual::getInstance($id,$tree);
      #$individual = Auth::checkIndividualAccess($individual);

      $result['id']=$individual->xref();
      $result['type']='person';
      $page=(string)$request->getUri();
      $page=preg_replace('/\?.*/','',$page);
      $query=$request->getQueryParams();
      $route=urldecode($query['route']);
      preg_match('/\/fc\/([^\/]+)\/object\/(.+)/',$query['route'],$match);
      $query['route']='/tree/'.$match[1].'/individual/'.$match[2];
      $result['page']=$page.'?'.http_build_query($query);
      #$result['name']=print_r($individual->getAllNames(),TRUE);
      $result['name']=$individual->getAllNames()[0]['givn'];   # GIVN
      #$result['name']=$individual->getAllNames()[0]['surname'];   # GIVN

      $html = html_entity_decode($individual->displayImage(200,200,
                                                           'contain',[]));
      if ($html !== null) {
        preg_match('/src="([^"]+)"/',$html,$match);
        if (count($match)==2) {
          $url = urldecode($match[1]);
          $result['avatar']=$page.$url;
        }
      }

      $x = [];
      foreach ($individual->childFamilies() as $f) {
        array_push($x,$f->xref());
      }
      if (!empty($x)) { 
        $result['family']=$x[0];                              # FAMC
      }

      $y = [];
      foreach ($individual->spouseFamilies() as $f) {
        array_push($y,$f->xref());
      }
      if (!empty($y)) {
        $result['families']=$y;                              # FAMS
      }

      return $result;
    }

    private function getFamily($request,$id,$tree): array {
      $result = [];
      $family = Family::getInstance($id,$tree);
      #$family = Auth::checkFamilyAccess($family);

      $result['id']=$family->xref();
      $result['type']='family';
      $y = [];
      foreach ($family->children() as $c) {
        array_push($y,$c->xref());
        $result['name']=$c->getAllNames()[0]['surname'];
      }
      if (!empty($y)) {
        $result['children']=$y;                                          # CHIL
      }


      $result['father']=$family->husband()->xref();                      # HUSB
      if (empty($result['name'])) {
        #$result['name']=print_r($family->getAllNames()[0],TRUE);
        $result['name']=$family->husband()->getAllNames()[0]['surname']; # SURN
      }

      $result['mother']=$family->wife()->xref();                         # WIFE
      if (empty($result['name'])) {
        $result['name']=$family->wife()->getAllNames()[0]['surname'];    # SURN
      }

      return $result;
    }
}
