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

namespace Fisharebest\Webtrees\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function explode;
use function parse_url;
use function rtrim;

use const PHP_URL_HOST;
use const PHP_URL_PATH;
use const PHP_URL_PORT;
use const PHP_URL_SCHEME;

/**
 * Middleware to set the base URL.
 */
class BaseUrl implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // The request URL, as auto-detected from the environment.
        $request_url = $request->getUri();

        // The base URL, as specified in the configuration file.
        $base_url = $request->getAttribute('base_url', '');

        if ($base_url === '') {
            // Guess the base URL from the request URL.
            $parts    = explode('index.php', $request_url->getPath());
            $base_url = rtrim($parts[0], '/');
            $request     = $request->withAttribute('base_url', $base_url);
            $base_path   = parse_url($base_url, PHP_URL_PATH) ?? '';
            $request_url = $request_url->withPath($base_path);

            # fudge in route if we have already
            if (count($parts)==2 && $parts[1] != '') {
              $query = $request->getQueryParams();
              if (!in_array('route',$query)) {
                $query['route']=$parts[1];
                # seems the request does not update its params when uri changed
                $request=$request->withQueryParams($query);
                $request_url=$request_url->withQuery(http_build_query($query));
              }
            }
            

            $request = $request->withUri($request_url);
        } else {
            // Update the request URL from the base URL.
            $base_scheme = parse_url($base_url, PHP_URL_SCHEME) ?? 'http';
            $base_host   = parse_url($base_url, PHP_URL_HOST) ?? 'localhost';
            $base_port   = parse_url($base_url, PHP_URL_PORT);

            $request_url = $request_url
                ->withScheme($base_scheme)
                ->withHost($base_host)
                ->withPort($base_port);

            $request = $request->withUri($request_url);
        }

        return $handler->handle($request);
    }
}
