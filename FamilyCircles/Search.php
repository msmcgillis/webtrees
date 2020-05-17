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
use Fisharebest\Webtrees\Family;
use Fisharebest\Webtrees\Services\SearchService;
use Fisharebest\Webtrees\Tree;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The control panel shows a summary of the site and links to admin functions.
 */
class Search implements RequestHandlerInterface
{
    /** @var SearchService */
    private $search_service;

    /**
     * ControlPanel constructor.
     *
     * @param SearchService $search_service
     */
    public function __construct(
        SearchService $search_service
    ) {
        $this->search_service       = $search_service;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $tree = $request->getAttribute('tree');
        assert($tree instanceof Tree);

        $params = $request->getQueryParams();
        $query = $params['search'] ?? '';

        // What to search for?
        $search_terms = $this->extractSearchTerms($query);

        // What trees to search?
        $search_trees = [$tree];

        $headers = ['Content-Type' => 'application/json',
                    'Access-Control-Allow-Origin' => '*'];

        if ($search_terms !== []) {
          $individuals = $this->search_service->searchIndividuals(
                                $search_trees, $search_terms);

          $tmp1 = $this->search_service->searchFamilies(
                         $search_trees, $search_terms);
          $tmp2 = $this->search_service->searchFamilyNames(
                         $search_trees, $search_terms);
          $families = $tmp1->merge($tmp2)->unique(
                static function (Family $family): string {
                    return $family->xref() . '@' . $family->tree()->id();
                }
          );
          
          $body=[];
          foreach ($individuals as $i) {
            $x = [];
            $x['id'] = $i->xref();
            $i->extractNames();
            #$x['text'] = print_r($i->getAllNames()[0],TRUE);
            $first='';
            $last='';
            foreach ($i->getAllNames() as $name) {
              if ($name['type']=='NAME') {
                if (!empty($name['givn'])) {
                  $first=$name['givn'];
                }
                if (!empty($name['surname'])) {
                  $last=$name['surname'];
                }
              }
              if ($name['type']=='_MARNM') {
                if ($last != '') $last.='/';
                $last.=$name['surname'];
              }
            }
            $x['text'] = $first." ".$last;
            #$x['text'] = print_r($i->getSecondaryName(),TRUE);
            #$x['text'] = $i->getAllNames()[0]['givn'];
            #$x['text'] = $i->gedcom();
            array_push($body,$x);
          }

          foreach ($families as $f) {
            $x = [];
            $x['id'] = $f->xref();
            $first='';
            $last='';
            $spouses = [];
            if (!empty($f->husband())) {
              array_push($spouses,$f->husband());
            }
            if (!empty($f->wife())) {
              array_push($spouses,$f->wife());
            }
            foreach ($spouses as $spouse) {
              foreach ($spouse->getAllNames() as $name) {
                if ($name['type']=='NAME') {
                  if (!empty($name['givn'])) {
                    if ($first != "") $first.="/";
                    $first.=$name['givn'];
                  }
                  if (!empty($name['surname'])) {
                    if ($last == '') {
                      $last.=$name['surname'];
                    }
                  }
                }
              }
            }
            $x['text'] = $last." ".$first;
            array_push($body,$x);
          }
 
          $x = response(json_encode($body),200,$headers);
          return $x;
        }

        $err = ["code"=>400,"error"=>"must specify search term"];
        $x = response(json_encode($err),400,$headers);
        return $x;
    }

    private function extractSearchTerms(string $query): array
    {
        $search_terms = [];

        // Words in double quotes stay together
        while (preg_match('/"([^"]+)"/', $query, $match)) {
            $search_terms[] = trim($match[1]);
            $query          = str_replace($match[0], '', $query);
        }

        // Treat CJK characters as separate words, not as characters.
        $query = preg_replace('/\p{Han}/u', '$0 ', $query);

        // Other words get treated separately
        while (preg_match('/[\S]+/', $query, $match)) {
            $search_terms[] = trim($match[0]);
            $query          = str_replace($match[0], '', $query);
        }

        return $search_terms;
    }
}
